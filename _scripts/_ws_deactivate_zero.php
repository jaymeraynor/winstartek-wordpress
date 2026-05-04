<?php
/**
 * 從 active_plugins 移除 0 使用的 plugin。不刪資料夾(task D 處理)。
 * 用法:
 *   php -c _cli_php.ini _ws_deactivate_zero.php          # dry
 *   php -c _cli_php.ini _ws_deactivate_zero.php --apply  # 寫入
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

$REMOVE = [
    'ele-custom-skin/ele-custom-skin.php',
    'ele-custom-skin-pro/ele-custom-skin-pro.php',
    'astra-widgets/astra-widgets.php',
];

// also drop their per-plugin option rows that won't auto-clean
$DROP_OPTIONS = [
    'widget_astra-widget-address',
    'widget_astra-widget-list-icons',
    'widget_astra-widget-social-profiles',
];

$active = (array) get_option('active_plugins', []);
echo "Active before: " . count($active) . " plugins\n";

$new = array_values(array_diff($active, $REMOVE));
$removed_actual = array_values(array_intersect($active, $REMOVE));

echo "Will deactivate (" . count($removed_actual) . "):\n";
foreach ($removed_actual as $p) echo "  - $p\n";

$missing = array_diff($REMOVE, $active);
if ($missing) {
    echo "Already inactive (skipping):\n";
    foreach ($missing as $p) echo "  - $p\n";
}

echo "\nOption rows to drop:\n";
foreach ($DROP_OPTIONS as $o) {
    $exists = $wpdb->get_var($wpdb->prepare("SELECT option_id FROM {$prefix}options WHERE option_name=%s", $o));
    echo "  - $o " . ($exists ? "(exists)" : "(not found)") . "\n";
}

if (!$apply) { echo "\n=== DRY RUN (use --apply to write) ===\n"; exit; }

// Apply
update_option('active_plugins', $new);
echo "\nactive_plugins updated. Now: " . count($new) . " plugins\n";
foreach ($DROP_OPTIONS as $o) {
    $wpdb->delete($prefix.'options', ['option_name' => $o]);
}
echo "Dropped " . count($DROP_OPTIONS) . " option rows.\n";

// verify
$verify = (array) get_option('active_plugins', []);
$still_present = array_intersect($verify, $REMOVE);
echo "\nVerify: still present after update? " . (empty($still_present) ? 'NO (good)' : 'YES (' . implode(',', $still_present) . ')') . "\n";
