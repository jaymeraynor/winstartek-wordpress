<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// astra-widgets registers classic widgets with class names starting with "Astra_Widget_"
// stored in option `widget_<slug>` and registered into option `sidebars_widgets`
$sw = get_option('sidebars_widgets', []);
echo "sidebars_widgets keys: " . implode(',', array_keys((array)$sw)) . "\n";
$total_classic = 0;
foreach ((array)$sw as $area => $list) {
  if (!is_array($list)) continue;
  if ($area === 'wp_inactive_widgets' || $area === 'array_version') continue;
  if (count($list) === 0) continue;
  echo "  area=$area: " . implode(',', $list) . "\n";
  $total_classic += count($list);
}
echo "total classic widgets in active areas: $total_classic\n\n";

// astra-widgets specific slugs: search "astra-" widget option
$astra_widget_opts = $wpdb->get_results("
  SELECT option_name, LENGTH(option_value) AS bytes
  FROM {$prefix}options
  WHERE option_name LIKE 'widget_astra-%'
");
echo "Astra widget option rows: " . count($astra_widget_opts) . "\n";
foreach ($astra_widget_opts as $o) {
  echo "  {$o->option_name}: {$o->bytes} bytes\n";
}

// Are any astra widgets actually placed?
$placed = false;
foreach ((array)$sw as $area => $list) {
  if (!is_array($list)) continue;
  foreach ($list as $w) {
    if (strpos($w, 'astra-') === 0) { echo "USED astra widget: $w in $area\n"; $placed = true; }
  }
}
if (!$placed) echo "No astra-* widget placed in any sidebar area.\n";
