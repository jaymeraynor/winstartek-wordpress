<?php
/**
 * 把每個複製的 nav_menu_item 的 _menu_item_object_id 從 EN target 改為對應語言的 target。
 * 用法:
 *   php -c _cli_php.ini _ws_relink_menu_items.php          # dry
 *   php -c _cli_php.ini _ws_relink_menu_items.php --apply  # 寫入
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

$LANGS = ['ar','de','es','hi','id','it','ja','ko','pt-br','th','vi'];
$NEW_TT_IDS = [
    'ar'=>299,'de'=>300,'es'=>301,'hi'=>302,'id'=>303,'it'=>304,
    'ja'=>305,'ko'=>306,'pt-br'=>307,'th'=>308,'vi'=>309,
];

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n\n";

$translated = 0; $fallback_zh = 0; $kept_en = 0;

foreach ($LANGS as $lang) {
    $tt = $NEW_TT_IDS[$lang];
    // get all menu items in this menu
    $item_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT object_id FROM {$prefix}term_relationships WHERE term_taxonomy_id=%d", $tt
    ));
    echo "[$lang] menu tt_id=$tt items=" . count($item_ids) . "\n";

    foreach ($item_ids as $item_id) {
        $obj_type = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='_menu_item_object' LIMIT 1", $item_id));
        $obj_id   = (int)$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='_menu_item_object_id' LIMIT 1", $item_id));
        $title    = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$prefix}posts WHERE ID=%d", $item_id));

        if (!$obj_id || !$obj_type) continue;
        // only care about post-type menu items (page/post/project)
        if (!in_array($obj_type, ['page','post','project','custom_post','product'])) continue;

        // find WPML translation of the target in this lang
        $trid = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT trid FROM {$prefix}icl_translations WHERE element_id=%d AND element_type=%s LIMIT 1",
            $obj_id, "post_$obj_type"
        ));
        if (!$trid) {
            echo "    item=$item_id [$title] obj=$obj_id type=$obj_type — no trid\n";
            $kept_en++;
            continue;
        }
        $lang_target = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code=%s AND element_type=%s LIMIT 1",
            $trid, $lang, "post_$obj_type"
        ));
        if (!$lang_target) {
            // fallback to ZH original
            $zh_target = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code='zh-hant' AND element_type=%s LIMIT 1",
                $trid, "post_$obj_type"
            ));
            if ($zh_target && $zh_target !== $obj_id) {
                echo "    item=$item_id [$title] obj=$obj_id → fallback ZH=$zh_target\n";
                if ($apply) $wpdb->update($prefix.'postmeta', ['meta_value' => $zh_target], ['post_id' => $item_id, 'meta_key' => '_menu_item_object_id']);
                $fallback_zh++;
            } else {
                $kept_en++;
            }
            continue;
        }
        if ($lang_target === $obj_id) continue; // already correct (shouldn't happen)
        echo "    item=$item_id [$title] obj=$obj_id → $lang_target ($lang)\n";
        if ($apply) $wpdb->update($prefix.'postmeta', ['meta_value' => $lang_target], ['post_id' => $item_id, 'meta_key' => '_menu_item_object_id']);
        $translated++;
    }
    echo "\n";
}

echo "Summary: relinked to lang target=$translated, fallback to ZH=$fallback_zh, kept EN=$kept_en\n";

if ($apply) {
    $wpdb->query("DELETE FROM {$prefix}postmeta WHERE meta_key='_elementor_css'");
    echo "Cleared _elementor_css.\n";
}
