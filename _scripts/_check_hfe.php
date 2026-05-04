<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// header-footer-elementor stores HF templates as post_type='elementor-hf'
echo "=== elementor-hf templates (header/footer built with Elementor) ===\n";
$rows = $wpdb->get_results("SELECT ID, post_title, post_status FROM {$prefix}posts WHERE post_type='elementor-hf' ORDER BY ID");
foreach ($rows as $r) {
    $type = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='ehf_template_type' LIMIT 1", $r->ID));
    $loc  = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='display-on-canvas-templates' LIMIT 1", $r->ID));
    $lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$prefix}icl_translations WHERE element_id=%d AND element_type LIKE 'post_%%' LIMIT 1", $r->ID));
    echo "  ID={$r->ID} status={$r->post_status} type=$type lang=$lang title={$r->post_title}\n";
}

// dump nav-menu widget settings inside header templates
echo "\n=== nav-menu widget instances + their menu IDs ===\n";
$hf_ids = $wpdb->get_col("SELECT ID FROM {$prefix}posts WHERE post_type='elementor-hf'");
foreach ($hf_ids as $id) {
    $data = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='_elementor_data' LIMIT 1", $id));
    if (!$data) continue;
    $arr = json_decode($data, true);
    if (!is_array($arr)) continue;
    $found_menus = [];
    $walk = function($node) use (&$walk, &$found_menus) {
        if (!is_array($node)) return;
        if (isset($node['widgetType']) && $node['widgetType'] === 'nav-menu') {
            $s = $node['settings'] ?? [];
            $found_menus[] = ['menu' => $s['menu'] ?? null, 'layout' => $s['layout'] ?? null];
        }
        if (!empty($node['elements'])) foreach ($node['elements'] as $c) $walk($c);
    };
    foreach ($arr as $sec) $walk($sec);
    if ($found_menus) {
        $title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$prefix}posts WHERE ID=%d", $id));
        echo "  [hf=$id $title]\n";
        foreach ($found_menus as $m) echo "    nav-menu widget: menu={$m['menu']} layout={$m['layout']}\n";
    }
}

// also Astra header builder settings
echo "\n=== astra-settings (header builder) ===\n";
$ast = get_option('astra-settings');
if (is_array($ast)) {
    foreach ($ast as $k => $v) {
        if (stripos($k, 'menu') !== false || stripos($k, 'header') !== false) {
            $val = is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            if (strlen($val) > 200) $val = substr($val,0,200).'...';
            // only show small ones
            if (strlen($val) < 250) echo "  $k = $val\n";
        }
    }
}
