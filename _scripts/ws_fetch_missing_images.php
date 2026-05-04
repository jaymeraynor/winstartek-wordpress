<?php
/**
 * 從正式機（winstartek.com）抓回 local 缺漏的圖片
 *
 * 流程：
 *  1) 掃 wp_posts.post_content + wp_postmeta（含 _elementor_data） 找所有
 *     指向 wp-content/uploads/* 的圖片 URL
 *  2) 解析出 relative path
 *  3) 對應的本機檔案不存在 → 從 https://winstartek.com 下載到對應位置
 *
 * 用法：
 *   php -c _cli_php.ini ws_fetch_missing_images.php --dry
 *   php -c _cli_php.ini ws_fetch_missing_images.php
 *
 * 選項：
 *   --concurrency=8   平行下載數
 *   --limit=100       下載上限（測試用）
 */

if (PHP_SAPI !== 'cli') exit(1);

$opts = ['dry' => false, 'concurrency' => 8, 'limit' => 0, 'host' => 'https://winstartek.com'];
foreach ($argv as $a) {
    if (preg_match('/^--(\w+)(?:=(.+))?$/', $a, $m)) $opts[$m[1]] = $m[2] ?? true;
}

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
mysqli_query($wpdb->dbh, "SET NAMES utf8mb4");

$upload_dir = ABSPATH . 'wp-content/uploads/';
$prod_host = rtrim($opts['host'], '/');
echo "Local uploads: $upload_dir\n";
echo "Prod host:     $prod_host\n";

// 路徑正規化：urldecode + 移除無效尾巴
function normalize_path($p) {
    $p = str_replace(['\\/','\\\\'], ['/','\\'], $p);
    // 解 urlencoded
    if (strpos($p, '%') !== false) $p = urldecode($p);
    return ltrim($p, '/');
}

// === 收集所有提到的圖片 relative path ===
$paths = [];
// 嚴格的 regex：禁止 & " ' < > ( ) ` \ 等 HTML/JSON 邊界字元
$REGEX = '#wp-content[\\\\/]+uploads[\\\\/]+([^\s"\'<>()\\\\&`{}]+?\.(?:jpe?g|png|gif|webp|svg))#i';

// 1) post_content
$rows = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status IN ('publish','inherit')");
echo "Scanning " . count($rows) . " posts content...\n";
foreach ($rows as $r) {
    if (preg_match_all($REGEX, $r->post_content, $m)) {
        foreach ($m[1] as $p) $paths[normalize_path($p)] = true;
    }
}

// 2) _elementor_data
$rows = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data'");
echo "Scanning " . count($rows) . " _elementor_data...\n";
foreach ($rows as $r) {
    $text = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $r->meta_value);
    if (preg_match_all($REGEX, $text, $m)) {
        foreach ($m[1] as $p) $paths[normalize_path($p)] = true;
    }
}

// 3) _wp_attached_file
$rows = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file'");
echo "Scanning " . count($rows) . " attachments...\n";
foreach ($rows as $p) $paths[normalize_path($p)] = true;

// 4) options
$opts_rows = $wpdb->get_results("SELECT option_value FROM {$wpdb->options} WHERE option_name IN ('astra-settings','site_logo','custom_logo')");
foreach ($opts_rows as $r) {
    if (preg_match_all($REGEX, $r->option_value, $m)) {
        foreach ($m[1] as $p) $paths[normalize_path($p)] = true;
    }
}

echo "\nUnique image paths referenced: " . count($paths) . "\n";

// === 找出本地缺的 ===
$missing = [];
foreach (array_keys($paths) as $rel) {
    // normalize
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if (!file_exists($upload_dir . $rel)) {
        $missing[] = $rel;
    }
}
echo "Missing on local: " . count($missing) . "\n";

if ($opts['limit'] > 0) $missing = array_slice($missing, 0, (int)$opts['limit']);

if ($opts['dry']) {
    echo "\n=== DRY RUN — first 30 missing ===\n";
    foreach (array_slice($missing, 0, 30) as $p) echo "  $p\n";
    exit;
}

// === 下載 ===
$ok = 0; $fail = 0; $skip_size = 0;
$conc = max(1, (int)$opts['concurrency']);
$mh = curl_multi_init();
$pending = [];
$queue = $missing;
$total = count($queue);

function add_handle($mh, $url, $rel, $upload_dir, &$pending) {
    $abs = $upload_dir . $rel;
    @mkdir(dirname($abs), 0755, true);
    $fp = @fopen($abs, 'wb');
    if (!$fp) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compat; ws-fetch-missing/1.0)',
    ]);
    curl_multi_add_handle($mh, $ch);
    $pending[(int)$ch] = ['ch'=>$ch, 'fp'=>$fp, 'rel'=>$rel, 'abs'=>$abs, 'url'=>$url];
}

while ($queue || $pending) {
    while ($queue && count($pending) < $conc) {
        $rel = array_shift($queue);
        $url = $prod_host . '/wp-content/uploads/' . str_replace('%2F', '/', rawurlencode($rel));
        add_handle($mh, $url, $rel, $upload_dir, $pending);
    }
    do { $st = curl_multi_exec($mh, $running); } while ($st === CURLM_CALL_MULTI_PERFORM);
    if ($running) curl_multi_select($mh, 1);

    while (($info = curl_multi_info_read($mh)) !== false) {
        $ch = $info['handle'];
        $key = (int)$ch;
        $p = $pending[$key];
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        fclose($p['fp']);
        if ($code === 200 && $size > 100) {
            $ok++;
            if ($ok % 20 === 0 || $ok < 5) printf("  [%4d/%4d] OK %s (%d B)\n", $ok+$fail, $total, $p['rel'], $size);
        } else {
            $fail++;
            @unlink($p['abs']);
            if ($fail < 20) printf("  [%4d/%4d] FAIL %d  %s\n", $ok+$fail, $total, $code, $p['rel']);
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        unset($pending[$key]);
    }
}
curl_multi_close($mh);

echo "\n=== DONE ===\n";
echo "Downloaded: $ok\n";
echo "Failed:     $fail\n";
