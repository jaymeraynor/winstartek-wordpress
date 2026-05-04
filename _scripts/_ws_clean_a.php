<?php
/**
 * A. 安全 DB 清理:刪除可重生 / 過期 / 孤兒資料
 * 用法:
 *   php -c _cli_php.ini _ws_clean_a.php          # dry
 *   php -c _cli_php.ini _ws_clean_a.php --apply  # 寫入
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

function fmt($b) { return number_format($b/1024/1024, 2) . ' MB'; }

$tasks = [
    'ast-block-templates options' => [
        'count' => "SELECT COUNT(*) FROM {$prefix}options WHERE option_name LIKE 'ast-block-templates%%'",
        'size'  => "SELECT IFNULL(SUM(LENGTH(option_value)),0) FROM {$prefix}options WHERE option_name LIKE 'ast-block-templates%%'",
        'del'   => "DELETE FROM {$prefix}options WHERE option_name LIKE 'ast-block-templates%%'",
    ],
    '_elementor_controls_usage postmeta' => [
        'count' => "SELECT COUNT(*) FROM {$prefix}postmeta WHERE meta_key='_elementor_controls_usage'",
        'size'  => "SELECT IFNULL(SUM(LENGTH(meta_value)),0) FROM {$prefix}postmeta WHERE meta_key='_elementor_controls_usage'",
        'del'   => "DELETE FROM {$prefix}postmeta WHERE meta_key='_elementor_controls_usage'",
    ],
    'expired transients (option + timeout pair)' => [
        'count' => "SELECT COUNT(*)*2 FROM {$prefix}options o WHERE o.option_name LIKE '\\_transient\\_timeout\\_%%' AND o.option_value < UNIX_TIMESTAMP()",
        'size'  => "SELECT IFNULL(SUM(LENGTH(o.option_value)) + IFNULL((SELECT SUM(LENGTH(o2.option_value)) FROM {$prefix}options o2 WHERE o2.option_name = REPLACE(o.option_name,'_transient_timeout_','_transient_')),0),0) FROM {$prefix}options o WHERE o.option_name LIKE '\\_transient\\_timeout\\_%%' AND o.option_value < UNIX_TIMESTAMP()",
        'del'   => null, // handled specially
    ],
    '_site_transient_feed_* options' => [
        'count' => "SELECT COUNT(*) FROM {$prefix}options WHERE option_name LIKE '\\_site\\_transient\\_feed\\_%%' OR option_name LIKE '\\_site\\_transient\\_timeout\\_feed\\_%%'",
        'size'  => "SELECT IFNULL(SUM(LENGTH(option_value)),0) FROM {$prefix}options WHERE option_name LIKE '\\_site\\_transient\\_feed\\_%%' OR option_name LIKE '\\_site\\_transient\\_timeout\\_feed\\_%%'",
        'del'   => "DELETE FROM {$prefix}options WHERE option_name LIKE '\\_site\\_transient\\_feed\\_%%' OR option_name LIKE '\\_site\\_transient\\_timeout\\_feed\\_%%'",
    ],
    'elementor_remote_info_* options' => [
        'count' => "SELECT COUNT(*) FROM {$prefix}options WHERE option_name LIKE 'elementor_remote_info_%%' OR option_name LIKE 'elementor_pro_remote_info_%%' OR option_name LIKE '_transient_elementor%%' OR option_name LIKE '_transient_timeout_elementor%%'",
        'size'  => "SELECT IFNULL(SUM(LENGTH(option_value)),0) FROM {$prefix}options WHERE option_name LIKE 'elementor_remote_info_%%' OR option_name LIKE 'elementor_pro_remote_info_%%' OR option_name LIKE '_transient_elementor%%' OR option_name LIKE '_transient_timeout_elementor%%'",
        'del'   => "DELETE FROM {$prefix}options WHERE option_name LIKE 'elementor_remote_info_%%' OR option_name LIKE 'elementor_pro_remote_info_%%' OR option_name LIKE '_transient_elementor%%' OR option_name LIKE '_transient_timeout_elementor%%'",
    ],
];

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n";
$total_rows = 0; $total_bytes = 0;
foreach ($tasks as $label => $t) {
    $c = (int)$wpdb->get_var($t['count']);
    $b = (int)$wpdb->get_var($t['size']);
    echo sprintf("  %-46s rows=%-6d size=%s\n", $label, $c, fmt($b));
    $total_rows += $c; $total_bytes += $b;

    if ($apply && $c > 0) {
        if ($label === 'expired transients (option + timeout pair)') {
            // delete both _transient_timeout_X and _transient_X for expired ones
            $expired = $wpdb->get_col("SELECT REPLACE(option_name,'_transient_timeout_','') FROM {$prefix}options WHERE option_name LIKE '\\_transient\\_timeout\\_%' AND option_value < UNIX_TIMESTAMP()");
            foreach ($expired as $name) {
                $wpdb->delete($prefix.'options', ['option_name' => "_transient_timeout_$name"]);
                $wpdb->delete($prefix.'options', ['option_name' => "_transient_$name"]);
            }
            // also expired site_transient (excluding feed ones already covered by next task)
            $exp_st = $wpdb->get_col("SELECT REPLACE(option_name,'_site_transient_timeout_','') FROM {$prefix}options WHERE option_name LIKE '\\_site\\_transient\\_timeout\\_%' AND option_value < UNIX_TIMESTAMP() AND option_name NOT LIKE '\\_site\\_transient\\_timeout\\_feed\\_%'");
            foreach ($exp_st as $name) {
                $wpdb->delete($prefix.'options', ['option_name' => "_site_transient_timeout_$name"]);
                $wpdb->delete($prefix.'options', ['option_name' => "_site_transient_$name"]);
            }
        } else {
            $wpdb->query($t['del']);
        }
    }
}
echo "\n  TOTAL: rows=$total_rows size=" . fmt($total_bytes) . "\n";

if ($apply) {
    // Optimize tables we touched
    echo "\nOPTIMIZE TABLE...\n";
    foreach (['options','postmeta'] as $t) {
        $wpdb->query("OPTIMIZE TABLE {$prefix}$t");
    }
    echo "Done.\n";
}
