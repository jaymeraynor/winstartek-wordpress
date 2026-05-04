<?php
/**
 * 整合 QA 報告(QA-1 ~ QA-5)
 * 用法:
 *   php -c _cli_php.ini _ws_qa.php             # 全跑
 *   php -c _cli_php.ini _ws_qa.php --only=1    # 只跑 QA-1
 *   --only 接受逗號分隔: 1,2,5
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

$only = [];
foreach ($argv as $a) {
    if (preg_match('/^--only=([\d,]+)$/', $a, $m)) {
        $only = array_map('intval', explode(',', $m[1]));
    }
}
$run = function($n) use ($only) { return !$only || in_array($n, $only); };

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;
mysqli_query($wpdb->dbh, "SET NAMES utf8mb4");

$LANGS = ['zh-hant','en','de','es','it','pt-br','ja','ko','ar','hi','id','th','vi']; // 13 actually-enabled

function hr($t){ echo "\n========================\n  $t\n========================\n"; }

// ============ QA-1 文字亂碼 ============
if ($run(1)) {
hr('QA-1 文字亂碼掃描');

// 典型 mojibake patterns (UTF-8 被當 Latin-1 / Windows-1252 解碼後再存)
$BAD = [
    '\\xe4\\xb8\\x80'    => null, // skip — these ARE valid UTF-8
    'å¤'  => 'CJK 被當 Latin-1 解碼',
    'â€'  => 'curly quote 被當 Latin-1 解碼',
    'Ã©'  => 'é → Ã©',
    'Ã¨'  => 'è → Ã¨',
    'Ã '  => 'à → Ã',
    'â€™' => '右單引號',
    'â€œ' => '左雙引號',
    'â€'  => '右雙引號',
    'â€"' => '長破折號',
    'Â '  => '不換行空格亂碼',
    '\\u0000' => 'null 字元字面',
];
foreach ($BAD as $needle => $desc) {
    if ($desc === null) continue;
    // posts.post_content
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, post_type, post_title FROM {$prefix}posts WHERE post_content LIKE %s LIMIT 5",
        '%' . $wpdb->esc_like($needle) . '%'
    ));
    if ($rows) {
        echo "  posts.post_content 含 \"$needle\" ($desc):\n";
        foreach ($rows as $r) echo "    ID={$r->ID} type={$r->post_type} title={$r->post_title}\n";
    }
    // postmeta
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_key FROM {$prefix}postmeta WHERE meta_value LIKE %s LIMIT 5",
        '%' . $wpdb->esc_like($needle) . '%'
    ));
    if ($rows) {
        echo "  postmeta 含 \"$needle\":\n";
        foreach ($rows as $r) echo "    post={$r->post_id} key={$r->meta_key}\n";
    }
}

// 檢查無效 UTF-8 byte sequences
echo "\n  掃描 _elementor_data 內無效 UTF-8 bytes...\n";
$bad_utf = 0;
$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key='_elementor_data'");
foreach ($rows as $r) {
    if (!mb_check_encoding($r->meta_value, 'UTF-8')) {
        echo "    INVALID UTF-8 in post_id={$r->post_id}\n";
        $bad_utf++;
    }
}
echo "  total invalid UTF-8 records: $bad_utf\n";

// 檢查 ? mark 過多(常見 charset 失敗症狀)
$q_rows = $wpdb->get_results("SELECT post_id, ROUND(LENGTH(meta_value) - LENGTH(REPLACE(meta_value,'?','')))/LENGTH(meta_value)*100 AS pct FROM {$prefix}postmeta WHERE meta_key='_elementor_data' HAVING pct > 5 LIMIT 10");
if ($q_rows) {
    echo "  _elementor_data 中問號比例 >5% 的:\n";
    foreach ($q_rows as $r) echo "    post={$r->post_id} pct=" . number_format($r->pct,1) . "%\n";
} else {
    echo "  _elementor_data 中無異常問號比例\n";
}
}

// ============ QA-2 圖片連結 ============
if ($run(2)) {
hr('QA-2 圖片連結檢查');

$home = trim(get_option('home'), '/');
$site_host = parse_url($home, PHP_URL_HOST);
echo "  site host: $site_host\n";

$uploads_path = WP_CONTENT_DIR . '/uploads';
echo "  uploads path: $uploads_path\n\n";

$missing = [];
$external = [];
$total_imgs = 0;

$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key='_elementor_data'");
foreach ($rows as $r) {
    // collect all url-like strings ending in image extensions
    if (preg_match_all('#https?://[^\s"\'<>]+?\.(?:jpe?g|png|gif|webp|svg|avif)#i', $r->meta_value, $m)) {
        foreach ($m[0] as $url) {
            $total_imgs++;
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === $site_host || strpos($url, '/wp-content/uploads/') !== false) {
                // check filesystem
                $relpath = parse_url($url, PHP_URL_PATH);
                $local_path = WP_CONTENT_DIR . str_replace('/wp-content', '', $relpath);
                if (!file_exists($local_path)) {
                    $missing[$url] = $missing[$url] ?? [];
                    $missing[$url][] = $r->post_id;
                }
            } else {
                $external[$host] = ($external[$host] ?? 0) + 1;
            }
        }
    }
}

echo "  images scanned: $total_imgs\n";
echo "  internal missing files: " . count($missing) . " unique URLs\n";
foreach (array_slice($missing, 0, 20, true) as $url => $pids) {
    echo "    MISSING: $url  (used in " . count($pids) . " pages, e.g. " . implode(',', array_slice($pids,0,3)) . ")\n";
}
if (count($missing) > 20) echo "    ... and " . (count($missing) - 20) . " more\n";

echo "  external image hosts:\n";
arsort($external);
foreach (array_slice($external, 0, 10, true) as $h => $c) echo "    $h: $c imgs\n";

// also check post_content
echo "\n  also scan posts.post_content for img src...\n";
$post_missing = 0;
$rows = $wpdb->get_results("SELECT ID, post_content FROM {$prefix}posts WHERE post_status='publish' AND post_content LIKE '%<img%'");
foreach ($rows as $r) {
    if (preg_match_all('#<img[^>]+src=["\']([^"\']+)["\']#i', $r->post_content, $m)) {
        foreach ($m[1] as $url) {
            if (strpos($url, '/wp-content/uploads/') !== false || (strpos($url, '://') === false && substr($url,0,1) === '/')) {
                $relpath = parse_url($url, PHP_URL_PATH) ?: $url;
                $local_path = WP_CONTENT_DIR . str_replace('/wp-content', '', $relpath);
                if (!file_exists($local_path)) {
                    $post_missing++;
                    if ($post_missing <= 10) echo "    POST MISSING: post={$r->ID} url=$url\n";
                }
            }
        }
    }
}
echo "  post_content missing internal images: $post_missing\n";
}

// ============ QA-3 Google 代碼 ============
if ($run(3)) {
hr('QA-3 Google 代碼檢查');

// 找 GA / GTM / Pixel 設定
$opts = $wpdb->get_results("
    SELECT option_name, option_value FROM {$prefix}options
    WHERE option_name LIKE '%google%' OR option_name LIKE '%gtm%' OR option_name LIKE '%analytics%'
       OR option_name LIKE '%pixel%' OR option_name LIKE '%tag_manager%' OR option_name LIKE 'googlesitekit%'
    ORDER BY option_name
");
echo "  發現 Google/Pixel/Analytics 相關 options: " . count($opts) . "\n";
foreach ($opts as $o) {
    $v = $o->option_value;
    // 提取 GA4 / UA / GTM ID
    $ids = [];
    if (preg_match_all('/G-[A-Z0-9]{6,}/', $v, $m)) $ids = array_merge($ids, $m[0]);
    if (preg_match_all('/UA-\d{4,}-\d+/', $v, $m)) $ids = array_merge($ids, $m[0]);
    if (preg_match_all('/GTM-[A-Z0-9]{5,}/', $v, $m)) $ids = array_merge($ids, $m[0]);
    if (preg_match_all('/AW-\d{6,}/', $v, $m)) $ids = array_merge($ids, $m[0]);
    $ids = array_unique($ids);
    $marker = $ids ? ('IDs: ' . implode(', ', $ids)) : '(no GA/GTM/AW id detected in value)';
    echo "    {$o->option_name}: $marker\n";
}

// 注入頭尾 HTML 的 plugin
echo "\n  Custom CSS/JS 注入(custom-css-js plugin):\n";
$rows = $wpdb->get_results("SELECT ID, post_title, post_content, post_status FROM {$prefix}posts WHERE post_type='custom-css-js' LIMIT 30");
foreach ($rows as $r) {
    $has_g = (stripos($r->post_content, 'google') !== false || stripos($r->post_content, 'gtag') !== false || stripos($r->post_content, 'GTM-') !== false);
    echo "    [" . ($r->post_status==='publish'?'A':'-') . "] {$r->post_title} (ID={$r->ID})" . ($has_g ? "  [Google/GTM detected]" : "") . "\n";
}

// 主題 functions 和 child theme custom code
$child_funcs = get_template_directory() . '/../astra-child/functions.php';
$child_funcs2 = WP_CONTENT_DIR . '/themes/astra-child/functions.php';
foreach ([$child_funcs2] as $p) {
    if (file_exists($p)) {
        $c = file_get_contents($p);
        $hits = [];
        foreach (['G-','UA-','GTM-','AW-','gtag(','dataLayer'] as $needle) {
            if (strpos($c, $needle) !== false) $hits[] = $needle;
        }
        echo "  theme functions.php ($p): " . ($hits ? 'has [' . implode(',', $hits) . ']' : 'no Google code') . "\n";
    }
}
}

// ============ QA-4 翻譯完整 ============
if ($run(4)) {
hr('QA-4 翻譯完整性檢查');

// per-trid coverage of languages
$expected = $LANGS;
$total_trids = (int)$wpdb->get_var("SELECT COUNT(DISTINCT trid) FROM {$prefix}icl_translations WHERE element_type='post_page'");
echo "  total page trids: $total_trids\n  expected langs: " . implode(',', $expected) . "\n\n";

$missing_per_lang = array_fill_keys($expected, 0);
$incomplete_pages = [];

$trids = $wpdb->get_col("SELECT DISTINCT trid FROM {$prefix}icl_translations WHERE element_type='post_page'");
foreach ($trids as $trid) {
    $langs = $wpdb->get_col($wpdb->prepare(
        "SELECT language_code FROM {$prefix}icl_translations WHERE trid=%d AND element_type='post_page'", $trid));
    $missing = array_diff($expected, $langs);
    foreach ($missing as $l) $missing_per_lang[$l]++;
    if ($missing) {
        // get ZH source
        $zh_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code='zh-hant' LIMIT 1", $trid));
        $title = $zh_id ? $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$prefix}posts WHERE ID=%d", $zh_id)) : '?';
        $incomplete_pages[] = "    trid=$trid zh_id=$zh_id [$title]: missing " . implode(',', $missing);
    }
}

echo "  缺漏統計(每個語言缺幾頁):\n";
foreach ($missing_per_lang as $l => $c) echo "    $l: missing $c pages\n";

if ($incomplete_pages) {
    echo "\n  示例(前 30):\n";
    foreach (array_slice($incomplete_pages, 0, 30) as $line) echo "$line\n";
    if (count($incomplete_pages) > 30) echo "    ... and " . (count($incomplete_pages) - 30) . " more\n";
} else {
    echo "  所有頁皆有全 13 種語言版本 ✓\n";
}

// 檢查譯後 _elementor_data 仍含中文(可能漏譯)
echo "\n  檢查非 ZH 頁面的 _elementor_data 中文殘留比例...\n";
$lang_counts = [];
$lang_zh_residual = [];
$rows = $wpdb->get_results("
    SELECT t.element_id AS pid, t.language_code AS lang, LENGTH(pm.meta_value) AS bytes,
           (LENGTH(pm.meta_value) - LENGTH(REGEXP_REPLACE(CONVERT(pm.meta_value USING utf8mb4), '[\x{4e00}-\x{9fff}]', ''))) AS zh_bytes
    FROM {$prefix}icl_translations t
    JOIN {$prefix}postmeta pm ON pm.post_id=t.element_id AND pm.meta_key='_elementor_data'
    WHERE t.element_type='post_page' AND t.language_code != 'zh-hant'
");
foreach ($rows as $r) {
    $lang_counts[$r->lang] = ($lang_counts[$r->lang] ?? 0) + 1;
    if ($r->bytes > 0 && $r->zh_bytes / $r->bytes > 0.05) {
        $lang_zh_residual[$r->lang][] = ['pid'=>$r->pid, 'pct'=>round($r->zh_bytes/$r->bytes*100,1)];
    }
}
foreach ($lang_counts as $l => $c) {
    $resid = isset($lang_zh_residual[$l]) ? count($lang_zh_residual[$l]) : 0;
    echo "    $l: $c pages, $resid pages still >5% Chinese chars (likely untranslated)\n";
    if (isset($lang_zh_residual[$l])) {
        foreach (array_slice($lang_zh_residual[$l],0,3) as $rec) {
            echo "      pid={$rec['pid']} zh={$rec['pct']}%\n";
        }
    }
}
}

// ============ QA-5 SEO ============
if ($run(5)) {
hr('QA-5 SEO 完整性檢查');

// 公開頁 / 文章 (rename 'desc' to 'seo_desc' since DESC is SQL reserved)
$rows = $wpdb->get_results("
    SELECT p.ID, p.post_type, p.post_title,
        mt.meta_value AS seo_title,
        md.meta_value AS seo_desc,
        mk.meta_value AS seo_kw,
        t.language_code
    FROM {$prefix}posts p
    LEFT JOIN {$prefix}postmeta mt ON mt.post_id=p.ID AND mt.meta_key='rank_math_title'
    LEFT JOIN {$prefix}postmeta md ON md.post_id=p.ID AND md.meta_key='rank_math_description'
    LEFT JOIN {$prefix}postmeta mk ON mk.post_id=p.ID AND mk.meta_key='rank_math_focus_keyword'
    LEFT JOIN {$prefix}icl_translations t ON t.element_id=p.ID AND t.element_type LIKE 'post_%'
    WHERE p.post_status='publish' AND p.post_type IN ('page','post','project','product')
");
echo "  scanning " . count($rows) . " published entries (page/post/project/product)\n";

$no_title = 0; $no_desc = 0; $no_kw = 0;
$bad_title_len = 0; $bad_desc_len = 0;
$samples_no = []; $samples_bad = [];
$by_type_no_title = [];

foreach ($rows as $r) {
    $t_len = $r->seo_title ? mb_strlen($r->seo_title) : 0;
    $d_len = $r->seo_desc  ? mb_strlen($r->seo_desc)  : 0;
    if (!$r->seo_title) {
        $no_title++;
        $by_type_no_title[$r->post_type] = ($by_type_no_title[$r->post_type] ?? 0) + 1;
        if (count($samples_no)<10) $samples_no[] = "no title: ID={$r->ID} type={$r->post_type} [{$r->language_code}] {$r->post_title}";
    }
    if (!$r->seo_desc)  $no_desc++;
    if (!$r->seo_kw)    $no_kw++;
    if ($t_len > 0 && ($t_len < 10 || $t_len > 70)) { $bad_title_len++; if (count($samples_bad)<10) $samples_bad[] = "title len={$t_len}: ID={$r->ID} [{$r->language_code}] {$r->seo_title}"; }
    if ($d_len > 0 && ($d_len < 50 || $d_len > 170)) $bad_desc_len++;
}

echo "  no rank_math_title:        $no_title\n";
foreach ($by_type_no_title as $t => $c) echo "    └ $t: $c\n";
echo "  no rank_math_description:  $no_desc\n";
echo "  no rank_math_focus_keyword: $no_kw\n";
echo "  title length 異常 (<10 or >70): $bad_title_len\n";
echo "  description length 異常 (<50 or >170): $bad_desc_len\n";

if ($samples_no)  { echo "\n  缺 title 範例:\n"; foreach ($samples_no as $s) echo "    $s\n"; }
if ($samples_bad) { echo "\n  title 長度異常範例:\n"; foreach ($samples_bad as $s) echo "    $s\n"; }
}

echo "\n=== DONE ===\n";
