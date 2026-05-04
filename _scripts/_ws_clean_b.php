<?php
/**
 * B. WPML 孤兒列清理
 *  - icl_translations: 指向已不存在的 post 的列
 *  - icl_translation_status: 沒有對應 icl_translations 的列
 *  - icl_translate: 沒有對應 icl_translate_job 的列
 *  - icl_strings + icl_string_translations: 沒有實際使用的字串
 *
 * 用法:
 *   php -c _cli_php.ini _ws_clean_b.php          # dry
 *   php -c _cli_php.ini _ws_clean_b.php --apply  # 寫入
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

function fmt($b) { return number_format($b/1024/1024, 2) . ' MB'; }

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n\n";

// ---- 1. icl_translations dangling: post_*
$cnt1_post = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translations t
    WHERE t.element_type LIKE 'post\\_%'
      AND NOT EXISTS (SELECT 1 FROM {$prefix}posts p WHERE p.ID = t.element_id)
");
echo "[1] icl_translations dangling (post_*): $cnt1_post rows\n";

// ---- 2. icl_translations dangling: tax_*
$cnt1_tax = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translations t
    WHERE t.element_type LIKE 'tax\\_%'
      AND NOT EXISTS (SELECT 1 FROM {$prefix}term_taxonomy tt WHERE tt.term_taxonomy_id = t.element_id)
");
echo "[2] icl_translations dangling (tax_*): $cnt1_tax rows\n";

// ---- 3. icl_translation_status orphan
$cnt2 = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translation_status s
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translations t WHERE t.translation_id=s.translation_id)
");
echo "[3] icl_translation_status orphan (no matching icl_translations): $cnt2 rows\n";

// ---- 4. icl_translate orphan: rows without a job
$cnt3 = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translate tr
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translate_job j WHERE j.job_id=tr.job_id)
");
echo "[4] icl_translate orphan (no matching translate_job): $cnt3 rows\n";

// ---- 5. icl_translate_job orphan: jobs whose rid points to nothing in icl_translation_status
$cnt4 = (int)$wpdb->get_var("
    SELECT COUNT(*) FROM {$prefix}icl_translate_job j
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translation_status s WHERE s.rid=j.rid)
");
echo "[5] icl_translate_job orphan (rid not in translation_status): $cnt4 rows\n";

// pre-size
$tables = ['icl_translations','icl_translation_status','icl_translate','icl_translate_job'];
echo "\nBefore sizes:\n";
foreach ($tables as $t) {
    $b = (int)$wpdb->get_var("SELECT DATA_LENGTH+INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='{$prefix}$t'");
    echo "  $t: " . fmt($b) . "\n";
}

if (!$apply) { echo "\n[dry only — no changes]\n"; exit; }

echo "\n--- applying ---\n";

// 1. delete dangling icl_translations
$d = $wpdb->query("
    DELETE t FROM {$prefix}icl_translations t
    WHERE t.element_type LIKE 'post\\_%'
      AND NOT EXISTS (SELECT 1 FROM {$prefix}posts p WHERE p.ID = t.element_id)
");
echo "[1] deleted: $d\n";

$d = $wpdb->query("
    DELETE t FROM {$prefix}icl_translations t
    WHERE t.element_type LIKE 'tax\\_%'
      AND NOT EXISTS (SELECT 1 FROM {$prefix}term_taxonomy tt WHERE tt.term_taxonomy_id = t.element_id)
");
echo "[2] deleted: $d\n";

// 3. delete orphan icl_translation_status (must run AFTER icl_translations cleanup
//    because removing translations creates more orphans here)
$d = $wpdb->query("
    DELETE s FROM {$prefix}icl_translation_status s
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translations t WHERE t.translation_id=s.translation_id)
");
echo "[3] deleted: $d (post-step-1 sweep)\n";

// 4. delete orphan icl_translate (no job)
$d = $wpdb->query("
    DELETE tr FROM {$prefix}icl_translate tr
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translate_job j WHERE j.job_id=tr.job_id)
");
echo "[4] deleted: $d\n";

// 5. delete orphan jobs
$d = $wpdb->query("
    DELETE j FROM {$prefix}icl_translate_job j
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translation_status s WHERE s.rid=j.rid)
");
echo "[5] deleted: $d\n";

// 6. cascade: now also delete icl_translate rows whose job we just deleted
$d = $wpdb->query("
    DELETE tr FROM {$prefix}icl_translate tr
    WHERE NOT EXISTS (SELECT 1 FROM {$prefix}icl_translate_job j WHERE j.job_id=tr.job_id)
");
echo "[6] post-job-cleanup icl_translate cascade: $d\n";

echo "\nOPTIMIZE TABLE...\n";
foreach ($tables as $t) $wpdb->query("OPTIMIZE TABLE {$prefix}$t");

echo "\nAfter sizes:\n";
foreach ($tables as $t) {
    $b = (int)$wpdb->get_var("SELECT DATA_LENGTH+INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='{$prefix}$t'");
    echo "  $t: " . fmt($b) . "\n";
}

$g = (int)$wpdb->get_var("SELECT SUM(DATA_LENGTH+INDEX_LENGTH) FROM information_schema.TABLES WHERE TABLE_SCHEMA='".DB_NAME."'");
echo "\nDB total now: " . fmt($g) . "\n";
