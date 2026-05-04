<?php
/**
 * 翻譯 _elementor_data 內的文字（非 ZH 翻譯頁面）
 *
 * 用法：
 *   # 估字數
 *   php -c _cli_php.ini ws_translate_elementor.php --lang=EN-US --dry
 *
 *   # 先跑 1 頁試
 *   php -c _cli_php.ini ws_translate_elementor.php --lang=EN-US --ids=20141
 *
 *   # 全部
 *   php -c _cli_php.ini ws_translate_elementor.php --lang=EN-US
 *
 * 已翻過的 _elementor_data 會跳過（用 _elementor_translated_lang postmeta 標記）
 * 加 --force 才覆寫
 */

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

array_shift($argv);
$opts = ['lang'=>'', 'ids'=>'', 'limit'=>0, 'dry'=>false, 'force'=>false, 'yes'=>false];
foreach ($argv as $a) {
    if (preg_match('/^--(\w+)(?:=(.+))?$/', $a, $m)) $opts[$m[1]] = $m[2] ?? true;
}
if (!$opts['lang']) { fwrite(STDERR, "Required: --lang=EN-US\n"); exit(1); }

$target_langs = array_map('trim', explode(',', strtoupper($opts['lang'])));
$D2W = ['EN-US'=>'en','EN-GB'=>'en','EN'=>'en','DE'=>'de','ES'=>'es','IT'=>'it','PT-BR'=>'pt-br',
        'JA'=>'ja','KO'=>'ko','AR'=>'ar','HI'=>'hi','ID'=>'id','TH'=>'th','VI'=>'vi'];
foreach ($target_langs as $tl) if (!isset($D2W[$tl])) { fwrite(STDERR, "Unknown lang: $tl\n"); exit(1); }
$FORMALITY = ['DE','ES','IT','PT-BR','JA','FR','PL','NL','RU'];

$sec = json_decode(file_get_contents(__DIR__ . '/_secrets.json'), true);
$DL_KEY = $sec['deepl_api_key'];
$DL_URL = rtrim($sec['deepl_endpoint'], '/');

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");
function e($s) { global $mysqli; return mysqli_real_escape_string($mysqli, (string)$s); }

// ---------- DeepL ----------
function deepl_usage() {
    global $DL_KEY, $DL_URL;
    $ch = curl_init("$DL_URL/usage");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>["Authorization: DeepL-Auth-Key $DL_KEY"],
        CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false]);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true);
}

// 一次送多 text（DeepL 支援，最多 50 個 / request, 130KB total）
function deepl_translate_batch(array $texts, $target, $is_html = false) {
    global $DL_KEY, $DL_URL, $FORMALITY;
    if (!$texts) return [];
    $post = ['source_lang' => 'ZH', 'target_lang' => $target, 'preserve_formatting' => '1'];
    if ($is_html) $post['tag_handling'] = 'html';
    if (in_array($target, $FORMALITY, true)) $post['formality'] = 'more';
    // build form body manually with multiple text=... params
    $body_parts = [];
    foreach ($post as $k => $v) $body_parts[] = urlencode($k) . '=' . urlencode($v);
    foreach ($texts as $t) $body_parts[] = 'text=' . urlencode($t);
    $body = implode('&', $body_parts);

    $ch = curl_init("$DL_URL/translate");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$body,
        CURLOPT_HTTPHEADER=>["Authorization: DeepL-Auth-Key $DL_KEY",
                             "Content-Type: application/x-www-form-urlencoded"],
        CURLOPT_TIMEOUT=>120, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 456) { fwrite(STDERR, "DeepL QUOTA EXCEEDED\n"); return false; }
    if ($code === 400 && $is_html && stripos($resp, 'tag handling') !== false) {
        // fallback plain
        return deepl_translate_batch($texts, $target, false);
    }
    if ($code !== 200) { fwrite(STDERR, "DeepL HTTP $code: " . substr($resp,0,200) . "\n"); return false; }
    $data = json_decode($resp, true);
    return array_column($data['translations'] ?? [], 'text');
}

// ---------- Elementor walker ----------

// 哪些 settings key 是「文字內容」要翻譯
// 來源：Elementor 慣例 + Element Pack / Pro 常見 widget
$TRANSLATABLE_KEYS_PLAIN = [
    'title','heading_title','subtitle','sub_heading','sub_title',
    'text','button_text','text_above_button','text_prefix','text_suffix',
    'placeholder','field_label','caption','image_caption',
    'tab_title','tab_subtitle','accordion_title','accordion_text',
    'before_text','after_text','highlighted_text','rotating_text',
    'description','description_bottom','description_text',
    'price','period','sub_text','badge_text',
    'name','position','testimonial_content','testimonial_name','testimonial_job',
    'cta_button_text','cta_subtitle','cta_title',
    'header','footer','content','message',
    // 補：常見額外 widget keys
    'title_text','item_title','prefix','suffix',
    'starting_number_label','ending_number_label',
    'list_title','list_text','label','button_label',
    'first_button_text','second_button_text',
    'show_more_text','show_less_text','close_text',
    'image_link_text','link_text','overlay_title','overlay_text',
    'feature_title','feature_text','team_name','team_position','team_bio',
    'pricing_title','pricing_subtitle','pricing_button_text','pricing_features_text',
    'flip_box_title','flip_box_text','flip_box_btn_text',
    'before','after','header_text','footer_text',
    'progress_label','progress_text',
    'hover_text','tooltip_text',
    // 圖片 alt
    'alt','image_alt',
    // Posts widget
    'classic_read_more_text','cards_read_more_text',
    'pagination_prev_label','pagination_next_label',
    'load_more_no_posts_custom_message','load_more_message',
    'no_posts_message','nothing_found_message',
    // Form widget
    'form_name','step_next_label','step_previous_label',
    'success_message','error_message','server_message',
    'invalid_message','required_field_message',
    'field_options',  // select 選項 (string with newline-separated)
    // Submit 按鈕
    'submit_button_text','submit_label',
];
$TRANSLATABLE_KEYS_HTML = [
    'editor','text_editor','content_html','rich_text','html',
    'item_content','accordion_content','content_text','tab_content',
    'box_content','overlay_content','tooltip_content',
];

// 要遞迴下去的容器 key（可能是 array of items，每 item 內部又有 title/content 等）
$RECURSE_KEYS = [
    'tabs','accordion','accordion_items','tab_items','items','nav_menu_items',
    'icon_list','icon_box','testimonials','team_members',
    'price_list','flip_box_items','timeline_items','progress_bars',
    'image_carousel','gallery','social_icons','contact_items',
];

function collect_strings($node, &$texts, $path = '') {
    global $TRANSLATABLE_KEYS_PLAIN, $TRANSLATABLE_KEYS_HTML, $RECURSE_KEYS;
    if (!is_array($node)) return;

    // 1) settings
    if (isset($node['settings']) && is_array($node['settings'])) {
        collect_settings_strings($node['settings'], $texts, $path . '/settings');
    }
    // 2) elements (子節點)
    if (isset($node['elements']) && is_array($node['elements'])) {
        foreach ($node['elements'] as $i => $child) {
            collect_strings($child, $texts, $path . "/elements[$i]");
        }
    }
}

function collect_settings_strings($settings, &$texts, $path) {
    global $TRANSLATABLE_KEYS_PLAIN, $TRANSLATABLE_KEYS_HTML, $RECURSE_KEYS;
    foreach ($settings as $k => $v) {
        $cur = "$path/$k";
        // plain text key
        if (in_array($k, $TRANSLATABLE_KEYS_PLAIN, true)) {
            if (is_string($v) && trim($v) !== '' && contains_chinese($v)) {
                $texts[$cur] = ['text' => $v, 'html' => false];
            }
        }
        // html-bearing key
        elseif (in_array($k, $TRANSLATABLE_KEYS_HTML, true)) {
            if (is_string($v) && trim($v) !== '' && contains_chinese($v)) {
                $texts[$cur] = ['text' => $v, 'html' => true];
            }
        }
        // recurse into children arrays
        elseif (is_array($v)) {
            // is it a list of items?
            if (isset($v[0]) && is_array($v[0])) {
                foreach ($v as $i => $item) {
                    collect_settings_strings($item, $texts, "$cur[$i]");
                }
            } else {
                collect_settings_strings($v, $texts, $cur);
            }
        }
    }
}

function contains_chinese($str) {
    if (!preg_match('/[\x{4e00}-\x{9fff}]/u', $str)) return false;
    // 跳過 URL（含路徑/檔名是中文的情況也別翻，避免破連結）
    if (preg_match('#^https?://|^/wp-content/|^//|^data:#i', $str)) return false;
    return true;
}

// 套回翻譯
function apply_translations(&$node, $translations, $path = '') {
    global $TRANSLATABLE_KEYS_PLAIN, $TRANSLATABLE_KEYS_HTML;
    if (!is_array($node)) return;
    if (isset($node['settings']) && is_array($node['settings'])) {
        apply_settings_translations($node['settings'], $translations, $path . '/settings');
    }
    if (isset($node['elements']) && is_array($node['elements'])) {
        foreach ($node['elements'] as $i => &$child) {
            apply_translations($child, $translations, $path . "/elements[$i]");
        }
    }
}

function apply_settings_translations(&$settings, $translations, $path) {
    global $TRANSLATABLE_KEYS_PLAIN, $TRANSLATABLE_KEYS_HTML;
    foreach ($settings as $k => &$v) {
        $cur = "$path/$k";
        if (in_array($k, $TRANSLATABLE_KEYS_PLAIN, true) || in_array($k, $TRANSLATABLE_KEYS_HTML, true)) {
            if (isset($translations[$cur])) $v = $translations[$cur];
        } elseif (is_array($v)) {
            if (isset($v[0]) && is_array($v[0])) {
                foreach ($v as $i => &$item) {
                    apply_settings_translations($item, $translations, "$cur[$i]");
                }
            } else {
                apply_settings_translations($v, $translations, $cur);
            }
        }
    }
}

// ZH 單字單位字典:DeepL 看到單字「年」會猜成姓氏(年姓 → "Surname Nian"),
// 故對單字單位 suffix 改用字典直譯,不送 DeepL。鍵是 ZH 原字,值是各 WPML 語言碼對應翻譯。
$ZH_UNIT_MAP = [
    '年' => ['en'=>'Years','de'=>'Jahre','es'=>'Años','fr'=>'Années','it'=>'Anni','pt-br'=>'Anos','ja'=>'年','ko'=>'년','ar'=>'سنة','hi'=>'वर्ष','id'=>'Tahun','th'=>'ปี','vi'=>'Năm'],
    '月' => ['en'=>'Months','de'=>'Monate','es'=>'Meses','fr'=>'Mois','it'=>'Mesi','pt-br'=>'Meses','ja'=>'月','ko'=>'월','ar'=>'شهر','hi'=>'महीने','id'=>'Bulan','th'=>'เดือน','vi'=>'Tháng'],
    '日' => ['en'=>'Days','de'=>'Tage','es'=>'Días','fr'=>'Jours','it'=>'Giorni','pt-br'=>'Dias','ja'=>'日','ko'=>'일','ar'=>'يوم','hi'=>'दिन','id'=>'Hari','th'=>'วัน','vi'=>'Ngày'],
    '天' => ['en'=>'Days','de'=>'Tage','es'=>'Días','fr'=>'Jours','it'=>'Giorni','pt-br'=>'Dias','ja'=>'日','ko'=>'일','ar'=>'يوم','hi'=>'दिन','id'=>'Hari','th'=>'วัน','vi'=>'Ngày'],
    '週' => ['en'=>'Weeks','de'=>'Wochen','es'=>'Semanas','fr'=>'Semaines','it'=>'Settimane','pt-br'=>'Semanas','ja'=>'週','ko'=>'주','ar'=>'أسبوع','hi'=>'सप्ताह','id'=>'Minggu','th'=>'สัปดาห์','vi'=>'Tuần'],
    '小時' => ['en'=>'Hours','de'=>'Stunden','es'=>'Horas','fr'=>'Heures','it'=>'Ore','pt-br'=>'Horas','ja'=>'時間','ko'=>'시간','ar'=>'ساعات','hi'=>'घंटे','id'=>'Jam','th'=>'ชั่วโมง','vi'=>'Giờ'],
    '件' => ['en'=>'Items','de'=>'Stück','es'=>'Artículos','fr'=>'Articles','it'=>'Articoli','pt-br'=>'Itens','ja'=>'件','ko'=>'건','ar'=>'عناصر','hi'=>'आइटम','id'=>'Item','th'=>'รายการ','vi'=>'Mục'],
    '個' => ['en'=>'Pcs','de'=>'Stück','es'=>'Pzas','fr'=>'Pcs','it'=>'Pz','pt-br'=>'Pçs','ja'=>'個','ko'=>'개','ar'=>'قطعة','hi'=>'पीस','id'=>'Pcs','th'=>'ชิ้น','vi'=>'Cái'],
    '台' => ['en'=>'Units','de'=>'Geräte','es'=>'Unidades','fr'=>'Unités','it'=>'Unità','pt-br'=>'Unidades','ja'=>'台','ko'=>'대','ar'=>'وحدات','hi'=>'इकाइयाँ','id'=>'Unit','th'=>'เครื่อง','vi'=>'Chiếc'],
    '人' => ['en'=>'People','de'=>'Personen','es'=>'Personas','fr'=>'Personnes','it'=>'Persone','pt-br'=>'Pessoas','ja'=>'人','ko'=>'명','ar'=>'أشخاص','hi'=>'लोग','id'=>'Orang','th'=>'คน','vi'=>'Người'],
    '位' => ['en'=>'People','de'=>'Personen','es'=>'Personas','fr'=>'Personnes','it'=>'Persone','pt-br'=>'Pessoas','ja'=>'名','ko'=>'명','ar'=>'أشخاص','hi'=>'लोग','id'=>'Orang','th'=>'คน','vi'=>'Người'],
    '家' => ['en'=>'Companies','de'=>'Unternehmen','es'=>'Empresas','fr'=>'Entreprises','it'=>'Aziende','pt-br'=>'Empresas','ja'=>'社','ko'=>'개사','ar'=>'شركات','hi'=>'कंपनियाँ','id'=>'Perusahaan','th'=>'บริษัท','vi'=>'Công ty'],
    '家公司' => ['en'=>'Companies','de'=>'Unternehmen','es'=>'Empresas','fr'=>'Entreprises','it'=>'Aziende','pt-br'=>'Empresas','ja'=>'社','ko'=>'개사','ar'=>'شركات','hi'=>'कंपनियाँ','id'=>'Perusahaan','th'=>'บริษัท','vi'=>'Công ty'],
    '次' => ['en'=>'Times','de'=>'Mal','es'=>'Veces','fr'=>'Fois','it'=>'Volte','pt-br'=>'Vezes','ja'=>'回','ko'=>'회','ar'=>'مرات','hi'=>'बार','id'=>'Kali','th'=>'ครั้ง','vi'=>'Lần'],
    '筆' => ['en'=>'Records','de'=>'Einträge','es'=>'Registros','fr'=>'Entrées','it'=>'Voci','pt-br'=>'Registros','ja'=>'件','ko'=>'건','ar'=>'سجلات','hi'=>'रिकॉर्ड','id'=>'Catatan','th'=>'รายการ','vi'=>'Mục'],
    '名' => ['en'=>'People','de'=>'Personen','es'=>'Personas','fr'=>'Personnes','it'=>'Persone','pt-br'=>'Pessoas','ja'=>'名','ko'=>'명','ar'=>'أشخاص','hi'=>'लोग','id'=>'Orang','th'=>'คน','vi'=>'Người'],
];

// 公司名 pre-replace（避免 DeepL 翻錯）
function pre_replace_company($text) {
    return str_replace([
        '中輝光電股份有限公司',
        '中輝光電',
        '中輝',
    ], [
        'Radiant Star Co., Ltd.',
        'Radiant Star',
        'Radiant Star',
    ], $text);
}

// ---------- 主流程 ----------

$tbl = $wpdb->prefix . 'icl_translations';
$id_filter = $opts['ids'] ? array_map('intval', explode(',', $opts['ids'])) : [];

$grand_chars = 0; $total_pages_to_process = 0;

foreach ($target_langs as $dl_target) {
    $wpml_lang = $D2W[$dl_target];
    echo "===== TARGET: $dl_target -> $wpml_lang =====\n";

    // 找該 lang 的所有翻譯 post + 對應 ZH 原始
    $sql = "
        SELECT t.element_id AS tr_id, t.trid, t2.element_id AS zh_id
        FROM $tbl t
        JOIN $tbl t2 ON t.trid=t2.trid AND t2.language_code='zh-hant'
        WHERE t.language_code='" . e($wpml_lang) . "'
          AND t.element_type IN ('post_page','post_post','post_project')
    ";
    if ($id_filter) $sql .= " AND t2.element_id IN (" . implode(',', $id_filter) . ")";
    $rows = $wpdb->get_results($sql);

    foreach ($rows as $r) {
        // 取 ZH 的 _elementor_data 當翻譯來源（更權威）
        $ed_src = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id={$r->zh_id} AND meta_key='_elementor_data' LIMIT 1");
        if (!$ed_src) { continue; }

        // 已標記翻譯過？
        $marker = (string) $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id={$r->tr_id} AND meta_key='_elementor_translated_lang' LIMIT 1");
        if ($marker === $wpml_lang && !$opts['force']) {
            echo "  SKIP zh={$r->zh_id} tr={$r->tr_id} (already translated)\n";
            continue;
        }

        $data = json_decode($ed_src, true);
        if (!is_array($data)) {
            echo "  WARN zh={$r->zh_id}: invalid JSON\n";
            continue;
        }

        // 收集需翻譯的 strings
        $texts = [];
        foreach ($data as $i => $section) {
            collect_strings($section, $texts, "[$i]");
        }

        if (!$texts) {
            echo "  EMPTY zh={$r->zh_id}: no translatable text\n";
            continue;
        }

        // 預先用 ZH 單位字典解析(避免 DeepL 把單字「年/月/件…」誤判為姓氏)
        $translations = [];
        $dict_count   = 0;
        foreach ($texts as $path => $info) {
            $t = trim($info['text']);
            if (isset($ZH_UNIT_MAP[$t][$wpml_lang])) {
                $translations[$path] = $ZH_UNIT_MAP[$t][$wpml_lang];
                unset($texts[$path]);
                $dict_count++;
            }
        }
        $expected_total = count($translations) + count($texts);

        $total_pages_to_process++;

        // 估字數(只算需送 DeepL 的)
        $page_chars = 0;
        foreach ($texts as $t) $page_chars += mb_strlen($t['text']);
        $grand_chars += $page_chars;

        echo "  zh={$r->zh_id} tr={$r->tr_id}: " . count($texts) . " strings"
           . ($dict_count ? " (+$dict_count via unit-dict)" : "")
           . ", ~$page_chars ZH chars";

        if ($opts['dry']) { echo " [DRY]\n"; continue; }

        // 分組批次：plain text 一批、html 一批，每批 ≤ 40 個
        $plain_keys = []; $plain_texts = [];
        $html_keys = [];  $html_texts = [];
        foreach ($texts as $path => $info) {
            $t_pre = pre_replace_company($info['text']);
            if ($info['html']) { $html_keys[] = $path; $html_texts[] = $t_pre; }
            else               { $plain_keys[] = $path; $plain_texts[] = $t_pre; }
        }

        // batch send (translations array already pre-seeded by unit-dict)
        foreach ([['p', $plain_keys, $plain_texts, false], ['h', $html_keys, $html_texts, true]] as [$tag, $keys, $vals, $is_html]) {
            if (!$vals) continue;
            for ($i = 0; $i < count($vals); $i += 40) {
                $batch_keys = array_slice($keys, $i, 40);
                $batch_vals = array_slice($vals, $i, 40);
                $tr = deepl_translate_batch($batch_vals, $dl_target, $is_html);
                if ($tr === false) { echo " FAIL"; break 2; }
                if (count($tr) !== count($batch_vals)) { echo " COUNT_MISMATCH"; break 2; }
                foreach ($tr as $j => $t) $translations[$batch_keys[$j]] = $t;
            }
        }

        if (count($translations) !== $expected_total) {
            echo " (got " . count($translations) . "/$expected_total)";
        }

        // 套回 JSON
        foreach ($data as $i => &$section) {
            apply_translations($section, $translations, "[$i]");
        }
        $new_json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // WordPress wpdb 的 _elementor_data 是 slash-escaped 字串(serialized)；
        // 但這裡 metavalue 是直接 JSON。確認：
        $existing_meta = $wpdb->get_var("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id={$r->tr_id} AND meta_key='_elementor_data' LIMIT 1");

        // _elementor_data 是 JSON 字串，直接用 mysqli_real_escape_string 處理 SQL 安全
        // 不要 wp_slash — Elementor 讀回時不會自動 stripslashes，會把 \\" 當字面值
        $payload = $new_json;

        if ($existing_meta) {
            mysqli_query($mysqli, "UPDATE {$wpdb->postmeta} SET meta_value='" . mysqli_real_escape_string($mysqli, $payload) . "' WHERE meta_id=$existing_meta");
        } else {
            mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ({$r->tr_id},'_elementor_data','" . mysqli_real_escape_string($mysqli, $payload) . "')");
        }

        // 標記已翻
        $marker_meta = $wpdb->get_var("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id={$r->tr_id} AND meta_key='_elementor_translated_lang' LIMIT 1");
        if ($marker_meta) {
            mysqli_query($mysqli, "UPDATE {$wpdb->postmeta} SET meta_value='" . e($wpml_lang) . "' WHERE meta_id=$marker_meta");
        } else {
            mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ({$r->tr_id},'_elementor_translated_lang','" . e($wpml_lang) . "')");
        }

        echo " OK\n";
    }
}

if ($opts['dry']) {
    echo "\n=== DRY ESTIMATE ===\n";
    echo "Pages to translate: $total_pages_to_process\n";
    echo "ZH chars total: " . number_format($grand_chars) . "\n";
    echo "Real DeepL usage will be ~ " . number_format($grand_chars) . " chars (DeepL counts ZH = 1 char)\n";
}

if (!$opts['dry']) {
    $u = deepl_usage();
    echo "\nDeepL usage: " . number_format($u['character_count']) . " / " . number_format($u['character_limit']) . "\n";
}
