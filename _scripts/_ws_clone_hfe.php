<?php
/**
 * Clone EN HFE templates (header/footer/before-footer × 2) for each of the 11 missing languages.
 * 用法:
 *   php -c _cli_php.ini _ws_clone_hfe.php          # dry
 *   php -c _cli_php.ini _ws_clone_hfe.php --apply  # 寫入
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

// EN source template IDs
$EN_SOURCES = [
    'header'       => 21361, // trid=1226
    'footer'       => 21289, // trid=423
    'contact info' => 21323, // trid=85741
    'cons form'    => 21345, // trid=86076
];
$MISSING_LANGS = ['ar','de','es','hi','id','it','ja','ko','pt-br','th','vi'];

echo $apply ? "=== APPLY MODE ===\n" : "=== DRY RUN (use --apply) ===\n\n";

$created = 0; $skipped = 0;

foreach ($EN_SOURCES as $name => $en_id) {
    // get EN row
    $src_post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}posts WHERE ID=%d", $en_id), ARRAY_A);
    if (!$src_post) { echo "  ERROR: source $en_id not found\n"; continue; }
    $src_trid = (int)$wpdb->get_var($wpdb->prepare("SELECT trid FROM {$prefix}icl_translations WHERE element_id=%d AND element_type='post_elementor-hf' LIMIT 1", $en_id));
    if (!$src_trid) { echo "  ERROR: source $en_id has no trid\n"; continue; }

    echo "[$name] EN source ID=$en_id trid=$src_trid\n";

    foreach ($MISSING_LANGS as $lang) {
        // exists already?
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code=%s AND element_type='post_elementor-hf' LIMIT 1",
            $src_trid, $lang
        ));
        if ($existing) {
            echo "    [$lang] already has translation post_id=$existing → skip\n";
            $skipped++;
            continue;
        }

        if (!$apply) {
            echo "    [$lang] WOULD clone post (en_id=$en_id) → new id\n";
            $created++;
            continue;
        }

        // clone post (drop ID, set guid empty)
        $new_post = $src_post;
        unset($new_post['ID']);
        $new_post['post_status'] = 'publish';
        $new_post['post_modified'] = current_time('mysql');
        $new_post['post_modified_gmt'] = current_time('mysql', 1);
        $new_post['guid'] = '';
        $new_post['post_name'] = $src_post['post_name'] . '-' . $lang;

        // sanitize for INSERT
        $cols = array_keys($new_post);
        $vals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($mysqli, (string)$v) . "'";
        }, array_values($new_post));
        $sql = "INSERT INTO {$prefix}posts (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ")";
        if (!mysqli_query($mysqli, $sql)) {
            echo "    [$lang] INSERT FAILED: " . mysqli_error($mysqli) . "\n";
            continue;
        }
        $new_id = (int) mysqli_insert_id($mysqli);

        // copy all postmeta
        $metas = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$prefix}postmeta WHERE post_id=%d", $en_id), ARRAY_A);
        foreach ($metas as $m) {
            $wpdb->insert($prefix.'postmeta', ['post_id' => $new_id, 'meta_key' => $m['meta_key'], 'meta_value' => $m['meta_value']]);
        }

        // insert icl_translations row
        $wpdb->insert($prefix.'icl_translations', [
            'element_type' => 'post_elementor-hf',
            'element_id'   => $new_id,
            'trid'         => $src_trid,
            'language_code'=> $lang,
            'source_language_code' => 'zh-hant',
        ]);

        echo "    [$lang] cloned → new ID=$new_id (with " . count($metas) . " postmeta rows)\n";
        $created++;
    }
    echo "\n";
}

echo "Summary: created=$created skipped=$skipped\n";

if ($apply) {
    // clear elementor css cache for all HFE templates so they re-render
    $wpdb->query("DELETE FROM {$prefix}postmeta WHERE meta_key='_elementor_css'");
    echo "Also cleared all _elementor_css postmeta rows (HFE will rebuild on next render)\n";
}
