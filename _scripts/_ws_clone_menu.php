<?php
/**
 * Clone EN nav_menu (term + nav_menu_items) for each missing language.
 *
 * EN source: tt_id=192 (term name "Primary Menu", slug "primary-menu", 10 items, lang=en, trid=14360)
 *
 * For each missing language X, create:
 *   - new term + term_taxonomy "Primary Menu - X"
 *   - icl_translations row linking new tt_id to trid=14360 with lang=X
 *   - clone all 10 nav_menu_item posts (with their postmeta), assigned to new menu
 *
 * 用法:
 *   php -c _cli_php.ini _ws_clone_menu.php          # dry
 *   php -c _cli_php.ini _ws_clone_menu.php --apply  # 寫入
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");
$prefix = $wpdb->prefix;

$EN_TT_ID = 192;
$TRID = 14360;
$MISSING_LANGS = ['ar','de','es','hi','id','it','ja','ko','pt-br','th','vi'];

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n\n";

// fetch source EN menu term
$src_term_id = (int)$wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$prefix}term_taxonomy WHERE term_taxonomy_id=%d", $EN_TT_ID));
$src_term = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}terms WHERE term_id=%d", $src_term_id), ARRAY_A);
echo "Source EN menu: term_id=$src_term_id name={$src_term['name']} slug={$src_term['slug']}\n";

// fetch nav_menu_items in EN menu
$menu_items = $wpdb->get_col($wpdb->prepare(
    "SELECT object_id FROM {$prefix}term_relationships WHERE term_taxonomy_id=%d", $EN_TT_ID
));
echo "Items in EN menu: " . count($menu_items) . "\n\n";

$total_created = 0; $total_skipped = 0;
foreach ($MISSING_LANGS as $lang) {
    // exists?
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code=%s AND element_type='tax_nav_menu' LIMIT 1",
        $TRID, $lang
    ));
    if ($existing) {
        echo "[$lang] menu translation already exists (tt_id=$existing) → skip\n";
        $total_skipped++;
        continue;
    }

    if (!$apply) {
        echo "[$lang] WOULD create new menu + clone " . count($menu_items) . " items\n";
        $total_created++;
        continue;
    }

    // 1. new term
    $name = "Primary Menu - " . strtoupper($lang);
    $slug = "primary-menu-" . $lang;
    $wpdb->insert($prefix.'terms', ['name' => $name, 'slug' => $slug, 'term_group' => 0]);
    $new_term_id = (int) $wpdb->insert_id;

    // 2. new term_taxonomy
    $wpdb->insert($prefix.'term_taxonomy', [
        'term_id' => $new_term_id, 'taxonomy' => 'nav_menu', 'description' => '', 'parent' => 0, 'count' => 0
    ]);
    $new_tt_id = (int) $wpdb->insert_id;

    // 3. WPML translations row
    $wpdb->insert($prefix.'icl_translations', [
        'element_type' => 'tax_nav_menu',
        'element_id'   => $new_tt_id,
        'trid'         => $TRID,
        'language_code'=> $lang,
        'source_language_code' => 'zh-hant',
    ]);

    // 4. clone nav_menu_item posts (with metas) into new menu
    $cloned_items = 0;
    foreach ($menu_items as $src_item_id) {
        $src_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}posts WHERE ID=%d", $src_item_id), ARRAY_A);
        if (!$src_item) continue;
        unset($src_item['ID']);
        $src_item['post_modified'] = current_time('mysql');
        $src_item['post_modified_gmt'] = current_time('mysql', 1);
        $src_item['guid'] = '';

        $cols = array_keys($src_item);
        $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . mysqli_real_escape_string($mysqli, (string)$v) . "'", array_values($src_item));
        mysqli_query($mysqli, "INSERT INTO {$prefix}posts (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ")");
        $new_item_id = (int) mysqli_insert_id($mysqli);

        // clone postmetas
        $metas = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$prefix}postmeta WHERE post_id=%d", $src_item_id), ARRAY_A);
        foreach ($metas as $m) {
            $wpdb->insert($prefix.'postmeta', ['post_id' => $new_item_id, 'meta_key' => $m['meta_key'], 'meta_value' => $m['meta_value']]);
        }

        // assign to new menu via term_relationships
        $wpdb->insert($prefix.'term_relationships', [
            'object_id' => $new_item_id, 'term_taxonomy_id' => $new_tt_id, 'term_order' => 0
        ]);

        // also register in WPML so the nav_menu_item posts have language assignment
        // (nav_menu_item is element_type 'post_nav_menu_item' in icl_translations)
        $src_trid = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT trid FROM {$prefix}icl_translations WHERE element_id=%d AND element_type='post_nav_menu_item' LIMIT 1", $src_item_id
        ));
        if ($src_trid) {
            // existing trid — add as new lang under same trid (won't happen if EN-only)
            $wpdb->insert($prefix.'icl_translations', [
                'element_type' => 'post_nav_menu_item',
                'element_id'   => $new_item_id,
                'trid'         => $src_trid,
                'language_code'=> $lang,
                'source_language_code' => 'en',
            ]);
        } else {
            // create new trid (orphan in WPML's eyes is fine for menu items)
            // get max trid + insert with that
            $next_trid = (int) $wpdb->get_var("SELECT MAX(trid)+1 FROM {$prefix}icl_translations");
            $wpdb->insert($prefix.'icl_translations', [
                'element_type' => 'post_nav_menu_item',
                'element_id'   => $new_item_id,
                'trid'         => $next_trid,
                'language_code'=> $lang,
                'source_language_code' => null,
            ]);
        }

        $cloned_items++;
    }

    // 5. update term_taxonomy count
    $wpdb->update($prefix.'term_taxonomy', ['count' => $cloned_items], ['term_taxonomy_id' => $new_tt_id]);

    echo "[$lang] new menu tt_id=$new_tt_id name=\"$name\" cloned $cloned_items items\n";
    $total_created++;
}

echo "\nSummary: created=$total_created skipped=$total_skipped\n";

if ($apply) {
    // clear elementor css (so headers re-render with new menus)
    $wpdb->query("DELETE FROM {$prefix}postmeta WHERE meta_key='_elementor_css'");
    echo "Cleared _elementor_css to force re-render.\n";
}
