<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

echo "=== 1. nav_menu term + WPML translation ===\n";
// nav_menu_item posts (the menu items themselves) AND the nav_menu taxonomy (the menus)
$menus = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.count
    FROM {$prefix}terms t
    JOIN {$prefix}term_taxonomy tt ON t.term_id=tt.term_id
    WHERE tt.taxonomy='nav_menu'
    ORDER BY t.term_id
");
foreach ($menus as $m) {
    $lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$prefix}icl_translations WHERE element_id=%d AND element_type='tax_nav_menu' LIMIT 1", $m->term_taxonomy_id));
    $trid = $wpdb->get_var($wpdb->prepare("SELECT trid FROM {$prefix}icl_translations WHERE element_id=%d AND element_type='tax_nav_menu' LIMIT 1", $m->term_taxonomy_id));
    echo "  [tt_id={$m->term_taxonomy_id}] term={$m->term_id} name={$m->name} slug={$m->slug} items={$m->count} lang=$lang trid=$trid\n";
}

echo "\n=== 2. WPML 對每個 menu 的 trid 群 (應該每個語言都有對應) ===\n";
$trids = $wpdb->get_col("SELECT DISTINCT trid FROM {$prefix}icl_translations WHERE element_type='tax_nav_menu'");
$expected = ['ar','de','en','es','hi','id','it','ja','ko','pt-br','th','vi','zh-hant'];
foreach ($trids as $trid) {
    $tt_ids = $wpdb->get_col($wpdb->prepare("SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND element_type='tax_nav_menu'", $trid));
    $langs  = $wpdb->get_col($wpdb->prepare("SELECT language_code FROM {$prefix}icl_translations WHERE trid=%d AND element_type='tax_nav_menu'", $trid));
    // get base menu name (zh-hant)
    $zh_tt = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code='zh-hant' LIMIT 1", $trid));
    $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$prefix}terms WHERE term_id=(SELECT term_id FROM {$prefix}term_taxonomy WHERE term_taxonomy_id=%d)", $zh_tt));
    $missing = array_diff($expected, $langs);
    echo "  trid=$trid base[$name]: " . count($langs) . " langs (" . implode(',', $langs) . ")";
    if ($missing) echo " MISSING: " . implode(',', $missing);
    echo "\n";
}

echo "\n=== 3. theme nav_menu_locations option ===\n";
$loc = get_option('theme_mods_astra-child');
if ($loc && isset($loc['nav_menu_locations'])) {
    foreach ($loc['nav_menu_locations'] as $location => $tt_id) {
        $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$prefix}terms WHERE term_id=(SELECT term_id FROM {$prefix}term_taxonomy WHERE term_taxonomy_id=%d)", $tt_id));
        echo "  location[$location] => tt_id=$tt_id name=$name\n";
    }
} else {
    echo "  theme_mods_astra-child has no nav_menu_locations\n";
}

// also check parent theme
$loc2 = get_option('theme_mods_astra');
if ($loc2 && isset($loc2['nav_menu_locations'])) {
    echo "  -- theme_mods_astra (parent):\n";
    foreach ($loc2['nav_menu_locations'] as $location => $tt_id) {
        echo "    location[$location] => tt_id=$tt_id\n";
    }
}

echo "\n=== 4. WPML menu sync settings ===\n";
$sync = get_option('icl_sitepress_settings');
if (is_array($sync)) {
    foreach (['sync_post_taxonomies','sync_taxonomy_parents','language_negotiation_type','admin_default_language'] as $k) {
        echo "  $k = " . (isset($sync[$k]) ? var_export($sync[$k],true) : 'MISSING') . "\n";
    }
}
echo "\n=== 5. WPML language switcher menu ===\n";
$ls = get_option('icl_sitepress_settings');
if (isset($ls['icl_lso_link_empty'])) echo "  icl_lso_link_empty = " . var_export($ls['icl_lso_link_empty'],true) . "\n";
if (isset($ls['icl_lso_flags'])) echo "  icl_lso_flags = " . var_export($ls['icl_lso_flags'],true) . "\n";
