<?php
/**
 * 中輝多語翻譯（WPML + DeepL）
 *
 * 用法：
 *   # 估算字數（不打 API）
 *   php -c _cli_php.ini ws_translate.php --lang=EN-US --type=page --dry
 *
 *   # 預覽要翻哪幾個（打 DeepL 的 char count 但不寫 DB）
 *   php -c _cli_php.ini ws_translate.php --lang=EN-US --type=page --limit=3 --preview
 *
 *   # 實際翻譯（會寫 DB + 打 API）
 *   php -c _cli_php.ini ws_translate.php --lang=EN-US --type=page
 *
 *   # 指定特定 post id
 *   php -c _cli_php.ini ws_translate.php --lang=JA --ids=20157,20106
 *
 *   # 一次翻多語（會檢查 quota）
 *   php -c _cli_php.ini ws_translate.php --lang=EN-US,JA,KO --type=page
 *
 * 已存在翻譯的會跳過（resumable），加 --force 才覆寫。
 */

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

// ---------- 參數 ----------
array_shift($argv);
$opts = ['lang'=>'', 'type'=>'page', 'ids'=>'', 'limit'=>0, 'dry'=>false, 'preview'=>false, 'force'=>false, 'yes'=>false, 'no_meta'=>false];
foreach ($argv as $a) {
    if (preg_match('/^--(\w+)(?:=(.+))?$/', $a, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    }
}
if (!$opts['lang']) { fwrite(STDERR, "Required: --lang=EN-US (multiple ok: EN-US,JA,KO)\n"); exit(1); }

$target_langs = array_map('trim', explode(',', strtoupper($opts['lang'])));
$element_types = array_map(fn($t) => 'post_' . trim($t), explode(',', $opts['type']));
$id_filter = $opts['ids'] ? array_map('intval', explode(',', $opts['ids'])) : [];
$limit = (int)$opts['limit'];

// DeepL → WPML lang map
$D2W = [
    'EN-US'=>'en','EN-GB'=>'en','EN'=>'en',
    'DE'=>'de','ES'=>'es','IT'=>'it','PT-BR'=>'pt-br',
    'JA'=>'ja','KO'=>'ko','AR'=>'ar','HI'=>'hi',
    'ID'=>'id','TH'=>'th','VI'=>'vi','FR'=>'fr',
    'ZH'=>'zh-hant','ZH-HANS'=>'zh-hans',
];
foreach ($target_langs as $tl) {
    if (!isset($D2W[$tl])) { fwrite(STDERR, "Unknown DeepL lang: $tl\n"); exit(1); }
}

// formality 支援的語言
$FORMALITY = ['DE','ES','IT','PT-BR','JA','FR','PL','NL','RU'];

// ---------- secrets ----------
$secrets_file = __DIR__ . '/_secrets.json';
if (!file_exists($secrets_file)) { fwrite(STDERR, "missing _secrets.json\n"); exit(1); }
$sec = json_decode(file_get_contents($secrets_file), true);
$DL_KEY = $sec['deepl_api_key'];
$DL_URL = rtrim($sec['deepl_endpoint'], '/');

// ---------- bootstrap WP ----------
define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");

function e($s) { global $mysqli; return mysqli_real_escape_string($mysqli, (string)$s); }

// ---------- DeepL helpers ----------
function deepl_usage() {
    global $DL_KEY, $DL_URL;
    $ch = curl_init("$DL_URL/usage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: DeepL-Auth-Key $DL_KEY"],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,  // Local 環境 PHP 沒裝 CA bundle
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $r = curl_exec($ch);
    if (curl_errno($ch)) { fwrite(STDERR, "DeepL usage err: " . curl_error($ch) . "\n"); exit(1); }
    curl_close($ch);
    return json_decode($r, true);
}

function deepl_translate($text, $target_lang, $is_html = true) {
    global $DL_KEY, $DL_URL, $FORMALITY;
    if (trim($text) === '') return '';
    $post = [
        'text' => $text,
        'source_lang' => 'ZH',
        'target_lang' => $target_lang,
        'preserve_formatting' => '1',
    ];
    if ($is_html) $post['tag_handling'] = 'html';
    if (in_array($target_lang, $FORMALITY, true)) $post['formality'] = 'more';

    $ch = curl_init("$DL_URL/translate");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_HTTPHEADER => [
            "Authorization: DeepL-Auth-Key $DL_KEY",
            "Content-Type: application/x-www-form-urlencoded",
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) { fwrite(STDERR, "DeepL err: " . curl_error($ch) . "\n"); curl_close($ch); return false; }
    curl_close($ch);
    if ($code === 456) { fwrite(STDERR, "DeepL QUOTA EXCEEDED\n"); return false; }
    // 400 + tag_handling 解析失敗 → 退回 plain-text 模式重試一次
    if ($code === 400 && $is_html && stripos($resp, 'tag handling') !== false) {
        fwrite(STDERR, "  [retry as plain text]\n");
        return deepl_translate($text, $target_lang, false);
    }
    if ($code !== 200) { fwrite(STDERR, "DeepL HTTP $code: $resp\n"); return false; }
    $data = json_decode($resp, true);
    return $data['translations'][0]['text'] ?? false;
}

// ---------- 撈待翻譯 post 列表 ----------
$tbl = $wpdb->prefix . 'icl_translations';

$type_in = "'" . implode("','", array_map('e', $element_types)) . "'";
$id_clause = $id_filter ? ' AND t.element_id IN (' . implode(',', $id_filter) . ')' : '';
$limit_clause = $limit > 0 ? " LIMIT $limit" : '';

$rows = $wpdb->get_results("
    SELECT t.element_id AS pid, t.element_type, t.trid,
           p.post_title, p.post_content, p.post_excerpt, p.post_name, p.post_parent
    FROM $tbl t
    JOIN {$wpdb->posts} p ON p.ID = t.element_id
    WHERE t.language_code='zh-hant' AND t.element_type IN ($type_in)
      AND p.post_status='publish'
      $id_clause
    ORDER BY t.element_id ASC
    $limit_clause
");

echo "Source ZH posts found: " . count($rows) . "\n";
echo "Target languages: " . implode(', ', $target_langs) . " (WPML codes: " . implode(',', array_map(fn($x) => $D2W[$x], $target_langs)) . ")\n";

// ---------- 估算字數 ----------
$total_chars_per_post = [];
$grand_total = 0;
foreach ($rows as $r) {
    $c = mb_strlen($r->post_title) + mb_strlen($r->post_content) + mb_strlen($r->post_excerpt);
    $total_chars_per_post[$r->pid] = $c;
    $grand_total += $c;
}
echo "ZH source chars total: " . number_format($grand_total) . "\n";
echo "Will translate to " . count($target_langs) . " langs => approx " . number_format($grand_total * count($target_langs)) . " chars consumed (roughly equals source size)\n";

if (!$opts['dry']) {
    $usage = deepl_usage();
    echo "DeepL usage: " . number_format($usage['character_count']) . " / " . number_format($usage['character_limit']) . " chars\n";
    $remaining = $usage['character_limit'] - $usage['character_count'];
    $will_use = $grand_total * count($target_langs);
    echo "Will use ~" . number_format($will_use) . " of remaining " . number_format($remaining) . "\n";
    if ($will_use > $remaining) {
        echo "!! Pre-flight estimates over quota (估計含 HTML tag，DeepL 實際只算正文約 18%，可能還是夠用)\n";
        if (!$opts['preview'] && !$opts['yes']) {
            echo "   加 --yes 旁通；或 --limit=N / --ids= 縮小範圍\n";
            exit(1);
        }
    }
}
echo "\n";

if ($opts['dry']) { echo "(DRY — exiting)\n"; exit; }

// ---------- 主流程 ----------
$inserted = 0; $skipped = 0; $failed = 0;
foreach ($target_langs as $dl_target) {
    $wpml_lang = $D2W[$dl_target];
    echo "===== TARGET: $dl_target -> $wpml_lang =====\n";

    foreach ($rows as $r) {
        $pid = (int)$r->pid;

        // 檢查是否已有翻譯
        $existing = (int) $wpdb->get_var("SELECT element_id FROM $tbl WHERE trid={$r->trid} AND language_code='" . e($wpml_lang) . "' LIMIT 1");
        if ($existing && !$opts['force']) {
            echo "  SKIP [$pid] $r->post_title (exists: $existing)\n";
            $skipped++;
            continue;
        }

        echo "  TRANSLATE [$pid] " . mb_substr($r->post_title, 0, 40) . " ...";
        flush();

        $title_t   = deepl_translate($r->post_title, $dl_target, false);
        if ($title_t === false) { echo " FAIL TITLE\n"; $failed++; continue; }

        $content_t = deepl_translate($r->post_content, $dl_target, true);
        if ($content_t === false) { echo " FAIL CONTENT\n"; $failed++; continue; }

        $excerpt_t = '';
        if (trim($r->post_excerpt) !== '') {
            $excerpt_t = deepl_translate($r->post_excerpt, $dl_target, false);
            if ($excerpt_t === false) $excerpt_t = '';
        }

        if ($opts['preview']) {
            echo " preview: " . mb_substr($title_t, 0, 50) . "\n";
            continue;
        }

        // 寫翻譯
        $now = date('Y-m-d H:i:s');
        $now_gmt = gmdate('Y-m-d H:i:s');
        $slug = $r->post_name; // 保留原 slug；WPML 用 lang 路徑前綴

        if ($existing) {
            // 覆寫既有翻譯 post 內容
            mysqli_query($mysqli,
                "UPDATE {$wpdb->posts} SET
                    post_title='" . e($title_t) . "',
                    post_content='" . e($content_t) . "',
                    post_excerpt='" . e($excerpt_t) . "',
                    post_modified='$now', post_modified_gmt='$now_gmt'
                 WHERE ID=$existing"
            );
            echo " UPDATED #$existing\n";
            $inserted++;
            continue;
        }

        // 新增翻譯 post
        $type = str_replace('post_', '', $r->element_type);
        $sql = "INSERT INTO {$wpdb->posts}
            (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
             post_status, comment_status, ping_status, post_password, post_name,
             to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered,
             post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
            VALUES (1,'$now','$now_gmt','" . e($content_t) . "','" . e($title_t) . "','" . e($excerpt_t) . "',
             'publish','closed','closed','','" . e($slug) . "',
             '','','$now','$now_gmt','',
             " . (int)$r->post_parent . ",'','0','" . e($type) . "','',0)";
        $ok = mysqli_query($mysqli, $sql);
        if (!$ok) { echo " FAIL INSERT POST: " . mysqli_error($mysqli) . "\n"; $failed++; continue; }
        $new_pid = mysqli_insert_id($mysqli);

        // guid（WP 慣例）
        mysqli_query($mysqli, "UPDATE {$wpdb->posts} SET guid='http://winstartek.local/?page_id=$new_pid' WHERE ID=$new_pid");

        // 寫 icl_translations 連結
        mysqli_query($mysqli,
            "INSERT INTO $tbl (element_type, element_id, trid, language_code, source_language_code)
             VALUES ('" . e($r->element_type) . "', $new_pid, " . (int)$r->trid . ", '" . e($wpml_lang) . "', 'zh-hant')"
        );

        // 從 ZH 複製版型/Elementor/Astra 全套 postmeta（不翻譯，保持原值，讓版面跟 ZH 一致）
        $COPY_PREFIXES = ['_elementor_', 'ast-', 'astra-', 'site-', 'theme-', 'stick-'];
        $COPY_EXACT    = ['_wp_page_template', '_thumbnail_id', '_ws_product_template',
                          '_cms_nav_minihome', '_top_nav_excluded',
                          'rank_math_robots', 'rank_math_og_content_image',
                          '使用產品1', '_使用產品1', '使用產品2', '_使用產品2',
                          '場景圖', '_場景圖', '更多圖片', '_更多圖片',
                          '產品縮圖1', '_產品縮圖1', '產品縮圖2', '_產品縮圖2',
                          '縮圖1', '_縮圖1', '縮圖2', '_縮圖2',
                          '情境圖', '_情境圖', '分類項目2', '_分類項目2',
                          '分類項目4', '_分類項目4', '專案標題', '_專案標題'];
        $SKIP_PREFIXES = ['_wpml_', '_icl_'];

        $zh_metas = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=$pid");
        $rows_to_insert = [];
        foreach ($zh_metas as $zm) {
            $k = $zm->meta_key;
            $skip = false;
            foreach ($SKIP_PREFIXES as $sp) if (strpos($k, $sp) === 0) { $skip = true; break; }
            if ($skip) continue;
            // rank_math title/desc/focus_keyword 由本腳本自己翻
            if (in_array($k, ['rank_math_title','rank_math_description','rank_math_focus_keyword'], true)) continue;
            $match = false;
            foreach ($COPY_PREFIXES as $cp) if (strpos($k, $cp) === 0) { $match = true; break; }
            if (!$match && in_array($k, $COPY_EXACT, true)) $match = true;
            if (!$match) continue;
            $rows_to_insert[] = "($new_pid,'" . e($k) . "','" . e($zm->meta_value) . "')";
        }
        if ($rows_to_insert) {
            mysqli_query($mysqli, "INSERT IGNORE INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES " . implode(',', $rows_to_insert));
        }

        // 翻譯 RankMath title/desc/focus_keyword（可選；省字數可關）
        $rm_keys = ['rank_math_title','rank_math_description','rank_math_focus_keyword'];
        foreach ($rm_keys as $mk) {
            $mv = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=$pid AND meta_key='" . e($mk) . "' LIMIT 1");
            if ($mv !== null && trim($mv) !== '') {
                $tv = deepl_translate($mv, $dl_target, false);
                if ($tv !== false && $tv !== '') {
                    mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ($new_pid,'" . e($mk) . "','" . e($tv) . "')");
                }
            }
        }

        echo " NEW #$new_pid\n";
        $inserted++;
    }
}

// 最終 usage
$u = deepl_usage();
echo "\n=== DONE ===\n";
echo "Inserted/Updated: $inserted\n";
echo "Skipped:          $skipped\n";
echo "Failed:           $failed\n";
echo "DeepL after run:  " . number_format($u['character_count']) . " / " . number_format($u['character_limit']) . "\n";
