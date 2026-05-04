<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// Find all Posts widget instances and their _skin value
echo "Scanning Posts widget _skin distribution...\n\n";
$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key='_elementor_data' AND meta_value LIKE '%\"widgetType\":\"posts\"%'");
echo "pages with posts widget: " . count($rows) . "\n\n";

$skin_count = [];
$skin_pages = [];
function walk($node, &$skin_count, &$skin_pages, $current) {
    if (!is_array($node)) return;
    if (isset($node['widgetType']) && $node['widgetType'] === 'posts') {
        $skin = $node['settings']['_skin'] ?? 'classic';
        if ($skin === '') $skin = 'classic';
        $skin_count[$skin] = ($skin_count[$skin] ?? 0) + 1;
        $skin_pages[$skin][$current] = true;
    }
    if (!empty($node['elements']) && is_array($node['elements'])) {
        foreach ($node['elements'] as $c) walk($c, $skin_count, $skin_pages, $current);
    }
}

foreach ($rows as $r) {
    $data = json_decode($r->meta_value, true);
    if (!is_array($data)) continue;
    foreach ($data as $section) walk($section, $skin_count, $skin_pages, $r->post_id);
}

arsort($skin_count);
echo "Posts widget _skin values:\n";
foreach ($skin_count as $skin => $count) {
    $pages = count($skin_pages[$skin]);
    echo "  _skin=\"$skin\"  : $count uses on $pages pages\n";
}

// Same for loop-carousel
echo "\nLoop-carousel _skin distribution:\n";
$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key='_elementor_data' AND meta_value LIKE '%\"widgetType\":\"loop-carousel\"%'");
$lc_skin = [];
function walk2($node, &$out, $current) {
    if (!is_array($node)) return;
    if (isset($node['widgetType']) && $node['widgetType'] === 'loop-carousel') {
        $skin = $node['settings']['_skin'] ?? 'classic';
        $out[$skin] = ($out[$skin] ?? 0) + 1;
    }
    if (!empty($node['elements']) && is_array($node['elements'])) {
        foreach ($node['elements'] as $c) walk2($c, $out, $current);
    }
}
foreach ($rows as $r) {
    $data = json_decode($r->meta_value, true);
    if (!is_array($data)) continue;
    foreach ($data as $section) walk2($section, $lc_skin, $r->post_id);
}
arsort($lc_skin);
foreach ($lc_skin as $skin => $count) echo "  _skin=\"$skin\": $count uses\n";
