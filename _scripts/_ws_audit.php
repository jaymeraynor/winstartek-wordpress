<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");
$prefix = $wpdb->prefix;
$db = DB_NAME;

function fmt_bytes($b) { return number_format($b/1024/1024, 2) . ' MB'; }

// ----- table sizes via mysqli -----
echo "=== TABLE SIZES (top 30) ===\n";
$res = mysqli_query($mysqli, "
    SELECT TABLE_NAME, DATA_LENGTH+INDEX_LENGTH AS bytes, TABLE_ROWS
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA='$db'
    ORDER BY DATA_LENGTH+INDEX_LENGTH DESC
    LIMIT 30
");
printf("%-50s %12s %10s\n", 'table', 'size', 'rows');
$top_total = 0;
while ($r = mysqli_fetch_assoc($res)) {
    printf("%-50s %12s %10s\n", $r['TABLE_NAME'], fmt_bytes($r['bytes']), number_format($r['TABLE_ROWS']));
    $top_total += $r['bytes'];
}
$grand = (int)$wpdb->get_var("SELECT SUM(DATA_LENGTH+INDEX_LENGTH) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db'");
echo "  -- top 30: " . fmt_bytes($top_total) . " / DB total: " . fmt_bytes($grand) . "\n";

// ----- installed/active plugins via filesystem + option -----
echo "\n=== PLUGINS (filesystem + active_plugins option) ===\n";
$active = (array) get_option('active_plugins', []);
$plugins_dir = WP_PLUGIN_DIR;
$dirs = is_dir($plugins_dir) ? array_filter(scandir($plugins_dir), fn($d) => $d[0] !== '.' && is_dir("$plugins_dir/$d")) : [];
$rows = [];
foreach ($dirs as $d) {
    $main = '';
    foreach (glob("$plugins_dir/$d/*.php") as $php) {
        $head = file_get_contents($php, false, null, 0, 4096);
        if (preg_match('/Plugin Name:\s*(.+)/i', $head, $m)) { $main = "$d/" . basename($php); break; }
    }
    if (!$main) continue;
    $is_active = in_array($main, $active, true) ? 'A' : ' ';
    // size
    $sz = 0;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$plugins_dir/$d", RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $f) if ($f->isFile()) $sz += $f->getSize();
    $rows[] = ['act'=>$is_active, 'slug'=>$d, 'main'=>$main, 'size'=>$sz];
}
usort($rows, fn($a,$b) => $b['size'] <=> $a['size']);
echo sprintf("%-3s %-40s %12s\n", 'on', 'slug', 'size');
$total_act = 0; $count_act = 0;
foreach ($rows as $r) {
    echo sprintf("%-3s %-40s %12s\n", $r['act'], $r['slug'], fmt_bytes($r['size']));
    if ($r['act']==='A') { $total_act += $r['size']; $count_act++; }
}
echo "  -- active: $count_act plugins, files total " . fmt_bytes($total_act) . "\n";
echo "  -- installed (with main file): " . count($rows) . "\n";

// ----- WPML dangling cleanup preview -----
echo "\n=== WPML CLEANUP PREVIEW ===\n";
$dangling_post = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translations t
    WHERE t.element_type LIKE 'post_%'
      AND NOT EXISTS (SELECT 1 FROM {$prefix}posts p WHERE p.ID = t.element_id)
");
echo "  icl_translations rows pointing to non-existent posts: $dangling_post\n";

$status_orphan = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translation_status s
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translations t WHERE t.translation_id=s.translation_id)
");
echo "  icl_translation_status rows w/o matching icl_translations: $status_orphan\n";

$translate_orphan = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translate tr
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translate_job j WHERE j.job_id=tr.job_id)
");
echo "  icl_translate rows w/o matching translate_job: $translate_orphan\n";

// ----- ast-block-templates options -----
echo "\n=== ast-block-templates (Astra Starter Templates cache) ===\n";
$ast = $wpdb->get_results("SELECT option_name, autoload, LENGTH(option_value) AS bytes FROM {$prefix}options WHERE option_name LIKE 'ast-block-templates%' ORDER BY bytes DESC");
$ast_total = 0;
foreach ($ast as $r) {
    echo "  {$r->option_name} ({$r->autoload}) " . fmt_bytes($r->bytes) . "\n";
    $ast_total += $r->bytes;
}
echo "  total: " . fmt_bytes($ast_total) . " across " . count($ast) . " rows\n";

// ----- elementor revisions/css -----
echo "\n=== Elementor _elementor_css (regenerable) ===\n";
$el_css = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}postmeta WHERE meta_key='_elementor_css'");
$el_css_b = (int)$wpdb->get_var("SELECT SUM(LENGTH(meta_value)) FROM {$prefix}postmeta WHERE meta_key='_elementor_css'");
echo "  _elementor_css: $el_css rows / " . fmt_bytes($el_css_b) . "\n";
$el_assets = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}postmeta WHERE meta_key='_elementor_page_assets'");
echo "  _elementor_page_assets: $el_assets rows\n";

// ----- Elementor pages by post_status -----
echo "\n=== Elementor _elementor_data by post status (where biggest data lives) ===\n";
$by = $wpdb->get_results("
    SELECT p.post_status, p.post_type, COUNT(*) AS n, SUM(LENGTH(pm.meta_value)) AS bytes
    FROM {$prefix}postmeta pm
    JOIN {$prefix}posts p ON p.ID=pm.post_id
    WHERE pm.meta_key='_elementor_data'
    GROUP BY p.post_status, p.post_type
    ORDER BY bytes DESC
");
foreach ($by as $r) echo "  status={$r->post_status} type={$r->post_type}: rows={$r->n} bytes=" . fmt_bytes($r->bytes) . "\n";
