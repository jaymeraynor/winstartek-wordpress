<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';

$RESTORE = [
    'ele-custom-skin/ele-custom-skin.php',
    'ele-custom-skin-pro/ele-custom-skin-pro.php',
];

$active = (array) get_option('active_plugins', []);
echo "Active before: " . count($active) . "\n";
foreach ($RESTORE as $p) {
    if (in_array($p, $active, true)) { echo "  already active: $p\n"; continue; }
    if (!file_exists(WP_PLUGIN_DIR . '/' . $p)) { echo "  MISSING file: $p\n"; continue; }
    $active[] = $p;
    echo "  re-activated: $p\n";
}
update_option('active_plugins', $active);

// Free cache
global $wpdb;
$prefix = $wpdb->prefix;
$wpdb->query("DELETE FROM {$prefix}options WHERE option_name LIKE '_transient_%'");
$wpdb->query("DELETE FROM {$prefix}postmeta WHERE meta_key='_elementor_css'");
echo "\nCleared transients + _elementor_css postmeta\n";

$verify = (array) get_option('active_plugins', []);
echo "Active after: " . count($verify) . "\n";
foreach ($RESTORE as $p) echo "  " . (in_array($p, $verify, true) ? 'YES' : 'NO ') . "  $p\n";
