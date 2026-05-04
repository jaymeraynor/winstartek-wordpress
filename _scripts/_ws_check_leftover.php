<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// Likely leftover option name patterns from deleted plugins
$PATTERNS = [
    'ele-custom-skin'                 => ['ele\\_custom\\_skin\\_%', 'ecs\\_%', 'ele_custom_skin%', 'ecs%'],
    'astra-widgets'                   => ['astra-widget-%', 'widget_astra-widget-%'],
    'woocommerce-multilingual'        => ['wcml\\_%', 'wpml_wcml_%', '_wcml_%'],
    'modula-video'                    => ['modula\\_video\\_%', 'modula-video-%'],
    'admin-menu-editor'               => ['ws\\_menu\\_editor\\_%', 'ame\\_%'],
];

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n\n";
$grand = 0;
foreach ($PATTERNS as $plug => $pats) {
    echo "[$plug]\n";
    $total_rows = 0; $total_bytes = 0;
    foreach ($pats as $like) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, LENGTH(option_value) AS bytes FROM {$prefix}options WHERE option_name LIKE %s",
            $like
        ));
        foreach ($rows as $r) {
            echo "  - {$r->option_name} ({$r->bytes} bytes)\n";
            $total_rows++; $total_bytes += $r->bytes;
            if ($apply) $wpdb->delete($prefix.'options', ['option_name' => $r->option_name]);
        }
    }
    if ($total_rows === 0) echo "  (none)\n";
    else echo "  total: $total_rows rows, " . number_format($total_bytes/1024,1) . " KB\n";
    $grand += $total_bytes;
}

// Tables potentially created by these plugins
$TABLES = [
    'wcml_%','modula_%','ame_%','ws_menu_editor_%'
];
echo "\n[orphan tables]\n";
foreach ($TABLES as $like) {
    $found = $wpdb->get_col("SHOW TABLES LIKE '{$prefix}$like'");
    foreach ($found as $t) {
        $b = (int)$wpdb->get_var("SELECT DATA_LENGTH+INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='$t'");
        echo "  - $t (" . number_format($b/1024,1) . " KB)\n";
        if ($apply) $wpdb->query("DROP TABLE $t");
    }
}

echo "\nTotal removed: " . number_format($grand/1024,1) . " KB\n";
