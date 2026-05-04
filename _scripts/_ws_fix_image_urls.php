<?php
/**
 * QA-2 fix: 修 post_content 內的圖片 URL
 *  - https://i0.wp.com/www.winstartek.com/wp-content/...   → /wp-content/...
 *  - http(s)://winstartek.local/wp-content/...            → /wp-content/...  (relative,適合多語/多域名)
 *  - 同樣處理 i1.wp.com / i2.wp.com / wp.com 變體
 *
 * 用法:
 *   php -c _cli_php.ini _ws_fix_image_urls.php          # dry
 *   php -c _cli_php.ini _ws_fix_image_urls.php --apply  # 寫入
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");
$prefix = $wpdb->prefix;

// regex patterns and their replacements
// pattern → replacement (sub-callback uses captured $1)
$RULES = [
    // i0/i1/i2.wp.com Jetpack CDN with www.winstartek.com source
    [
        'name'  => 'i0/1/2.wp.com (Jetpack CDN)',
        'regex' => '#https?://i[0-2]\.wp\.com/(?:www\.)?winstartek\.com(/wp-content/[^?\s"\'<>]+)(\?[^\s"\'<>]*)?#i',
        'repl'  => '$1', // strip CDN, drop query (?resize/?fit), use relative path
    ],
    // winstartek.local dev domain leak
    [
        'name'  => 'winstartek.local (dev domain)',
        'regex' => '#https?://winstartek\.local(/wp-content/[^?\s"\'<>]+)(\?[^\s"\'<>]*)?#i',
        'repl'  => '$1',
    ],
];

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n\n";

// scan posts.post_content
echo "[posts.post_content]\n";
$rows = $wpdb->get_results("SELECT ID, post_content FROM {$prefix}posts WHERE post_status='publish' AND (post_content LIKE '%i0.wp.com%' OR post_content LIKE '%i1.wp.com%' OR post_content LIKE '%i2.wp.com%' OR post_content LIKE '%winstartek.local%')");
echo "  candidate posts: " . count($rows) . "\n";
$total_repl_posts = 0; $changed_posts = 0;
foreach ($rows as $r) {
    $new = $r->post_content;
    $row_repl = 0;
    foreach ($RULES as $rule) {
        $new = preg_replace($rule['regex'], $rule['repl'], $new, -1, $cnt);
        $row_repl += $cnt;
    }
    if ($row_repl === 0 || $new === $r->post_content) continue;
    echo "  post={$r->ID}: $row_repl replacements\n";
    $changed_posts++;
    $total_repl_posts += $row_repl;
    if ($apply) {
        $payload = mysqli_real_escape_string($mysqli, $new);
        mysqli_query($mysqli, "UPDATE {$prefix}posts SET post_content='$payload' WHERE ID={$r->ID}");
    }
}
echo "  total: $total_repl_posts replacements across $changed_posts posts\n\n";

// scan postmeta._elementor_data and other potentially-image-bearing meta
echo "[postmeta]\n";
$rows = $wpdb->get_results("SELECT meta_id, post_id, meta_key, meta_value FROM {$prefix}postmeta WHERE meta_value LIKE '%i0.wp.com%' OR meta_value LIKE '%i1.wp.com%' OR meta_value LIKE '%i2.wp.com%' OR meta_value LIKE '%winstartek.local%'");
echo "  candidate postmeta rows: " . count($rows) . "\n";
$total_repl_meta = 0; $changed_meta = 0;
foreach ($rows as $r) {
    $new = $r->meta_value;
    $row_repl = 0;
    foreach ($RULES as $rule) {
        $new = preg_replace($rule['regex'], $rule['repl'], $new, -1, $cnt);
        $row_repl += $cnt;
    }
    if ($row_repl === 0 || $new === $r->meta_value) continue;
    echo "  post={$r->post_id} key={$r->meta_key}: $row_repl replacements\n";
    $changed_meta++;
    $total_repl_meta += $row_repl;
    if ($apply) {
        $payload = mysqli_real_escape_string($mysqli, $new);
        mysqli_query($mysqli, "UPDATE {$prefix}postmeta SET meta_value='$payload' WHERE meta_id={$r->meta_id}");
    }
}
echo "  total: $total_repl_meta replacements across $changed_meta postmeta rows\n\n";

// scan options
echo "[options]\n";
$rows = $wpdb->get_results("SELECT option_id, option_name, option_value FROM {$prefix}options WHERE option_value LIKE '%i0.wp.com%' OR option_value LIKE '%i1.wp.com%' OR option_value LIKE '%i2.wp.com%' OR option_value LIKE '%winstartek.local%'");
echo "  candidate options: " . count($rows) . "\n";
$total_repl_opt = 0; $changed_opt = 0;
foreach ($rows as $r) {
    $new = $r->option_value;
    $row_repl = 0;
    foreach ($RULES as $rule) {
        $new = preg_replace($rule['regex'], $rule['repl'], $new, -1, $cnt);
        $row_repl += $cnt;
    }
    if ($row_repl === 0) continue;
    echo "  option={$r->option_name}: $row_repl replacements\n";
    $changed_opt++;
    $total_repl_opt += $row_repl;
    if ($apply) {
        $payload = mysqli_real_escape_string($mysqli, $new);
        mysqli_query($mysqli, "UPDATE {$prefix}options SET option_value='$payload' WHERE option_id={$r->option_id}");
    }
}
echo "  total: $total_repl_opt replacements across $changed_opt options\n";

echo "\nGRAND TOTAL: " . ($total_repl_posts + $total_repl_meta + $total_repl_opt) . " replacements\n";
