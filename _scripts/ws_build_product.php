<?php
/**
 * 中輝產品系列頁產生器（CLI）
 *
 * 用法：
 *   php -c _cli_php.ini ws_build_product.php product-config.json
 *   php -c _cli_php.ini ws_build_product.php product-config.json --dry
 *
 * 設計原則：「同型號不同尺寸／瓦數」全部塞同一頁，用 ProductGroup + hasVariant schema。
 *
 * Config JSON 範例：見 ws_product_config_sample.json
 */

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

$argv0 = array_shift($argv);
$config_file = null;
$dry = false;
foreach ($argv as $a) {
    if ($a === '--dry') $dry = true;
    elseif (!$config_file) $config_file = $a;
}
if (!$config_file || !file_exists($config_file)) {
    fwrite(STDERR, "Usage: php ws_build_product.php config.json [--dry]\n");
    exit(1);
}

$cfg = json_decode(file_get_contents($config_file), true);
if (!$cfg) { fwrite(STDERR, "JSON parse failed: " . json_last_error_msg() . "\n"); exit(1); }

// ------- bootstrap WP -------
define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;

function e($s) { global $mysqli; return mysqli_real_escape_string($mysqli, (string)$s); }
function siteurl() { global $wpdb; return rtrim((string)$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='siteurl'"), '/'); }

// ------- helpers -------

function find_or_create_attachment($file_path_rel, $title, $alt) {
    global $wpdb, $mysqli;
    $existing = (int) $wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value='" . e($file_path_rel) . "' LIMIT 1");
    if ($existing) return $existing;

    $abs = ABSPATH . 'wp-content/uploads/' . $file_path_rel;
    if (!file_exists($abs)) {
        fwrite(STDERR, "  [warn] image not on disk: $abs\n");
        return 0;
    }
    $info = @getimagesize($abs);
    [$w, $h, $type] = $info ?: [0, 0, 0];
    $mime = $type ? image_type_to_mime_type($type) : 'image/jpeg';
    $now = date('Y-m-d H:i:s');
    $now_gmt = gmdate('Y-m-d H:i:s');
    $slug = 'pgen-' . substr(md5($file_path_rel), 0, 12);
    $guid = siteurl() . '/wp-content/uploads/' . str_replace('%2F', '/', rawurlencode($file_path_rel));

    $sql = "INSERT INTO {$wpdb->posts}
      (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,
       post_status,comment_status,ping_status,post_password,post_name,
       to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,
       post_parent,guid,menu_order,post_type,post_mime_type,comment_count)
      VALUES (1,'" . e($now) . "','" . e($now_gmt) . "','','" . e($title) . "','" . e($alt) . "',
       'inherit','closed','closed','','" . e($slug) . "',
       '','','" . e($now) . "','" . e($now_gmt) . "','',
       0,'" . e($guid) . "',0,'attachment','" . e($mime) . "',0)";
    mysqli_query($mysqli, $sql);
    $aid = mysqli_insert_id($mysqli);
    if (!$aid) { fwrite(STDERR, "  [err] attachment insert failed\n"); return 0; }
    mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ($aid,'_wp_attached_file','" . e($file_path_rel) . "')");
    if ($alt) {
        mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ($aid,'_wp_attachment_image_alt','" . e($alt) . "')");
    }
    if ($w && $h) {
        $meta = serialize([
            'width'=>(int)$w,'height'=>(int)$h,'file'=>$file_path_rel,
            'filesize'=>filesize($abs),'sizes'=>[],'image_meta'=>[],
        ]);
        mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ($aid,'_wp_attachment_metadata','" . e($meta) . "')");
    }
    return $aid;
}

function upsert_meta($pid, $key, $value) {
    global $wpdb, $mysqli;
    $existing = (int) $wpdb->get_var("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id=$pid AND meta_key='" . e($key) . "' LIMIT 1");
    if ($existing) {
        mysqli_query($mysqli, "UPDATE {$wpdb->postmeta} SET meta_value='" . e($value) . "' WHERE meta_id=$existing");
    } else {
        mysqli_query($mysqli, "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ($pid,'" . e($key) . "','" . e($value) . "')");
    }
}

// ------- 內容組裝 -------

function render_specs($rows) {
    $h = '<div class="ws-spec-wrap"><table class="ws-spec"><tbody>';
    foreach ($rows as $r) $h .= '<tr><td>' . htmlspecialchars($r[0]) . '</td><td>' . htmlspecialchars($r[1]) . '</td></tr>';
    $h .= '</tbody></table></div>';
    return $h;
}

function render_features($items) {
    $h = '<ul class="ws-feature-list">';
    foreach ($items as $it) {
        if (is_array($it)) $h .= '<li><strong>' . htmlspecialchars($it[0]) . '</strong>：' . htmlspecialchars($it[1]) . '</li>';
        else               $h .= '<li>' . htmlspecialchars($it) . '</li>';
    }
    return $h . '</ul>';
}

function render_scenarios($scenes) {
    $h = '<div class="ws-grid">';
    foreach ($scenes as $s) {
        $h .= '<figure><img src="' . htmlspecialchars($s['image']) . '" alt="' . htmlspecialchars($s['caption']) . '" loading="lazy"/>';
        $h .= '<figcaption>' . htmlspecialchars($s['caption']) . '</figcaption></figure>';
    }
    return $h . '</div>';
}

function render_app_list($items) {
    $h = '<ul class="ws-app">';
    foreach ($items as $it) $h .= '<li>' . htmlspecialchars($it) . '</li>';
    return $h . '</ul>';
}

function render_table($headers, $rows, $class = 'ws-cmp', $pos_col = -1) {
    $h = '<div class="ws-cmp-wrap"><table class="' . $class . '"><thead><tr>';
    foreach ($headers as $th) $h .= '<th>' . htmlspecialchars($th) . '</th>';
    $h .= '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $h .= '<tr>';
        foreach ($r as $i => $cell) {
            $cls = ($pos_col >= 0 && $i === $pos_col) ? ' class="pos"' : '';
            $h .= "<td$cls>" . htmlspecialchars((string)$cell) . '</td>';
        }
        $h .= '</tr>';
    }
    return $h . '</tbody></table></div>';
}

function render_faq($faqs) {
    $h = '<div class="ws-faq">';
    foreach ($faqs as $i => $f) {
        $h .= '<details><summary>Q' . ($i+1) . '. ' . htmlspecialchars($f['q']) . '</summary>';
        $h .= '<div>' . htmlspecialchars($f['a']) . '</div></details>';
    }
    return $h . '</div>';
}

function build_schema($cfg, $page_url, $hero_url) {
    $variants = $cfg['variants'] ?? [];
    $variant_nodes = [];
    $prices = [];
    foreach ($variants as $v) {
        $price = (float)($v['price'] ?? 0);
        if ($price > 0) $prices[] = $price;
        $variant_nodes[] = [
            '@type' => 'Product',
            '@id'   => $page_url . '#' . ($v['sku'] ?? md5(json_encode($v))),
            'sku'   => $v['sku'] ?? '',
            'name'  => $v['name'] ?? ($cfg['title'] . ' ' . ($v['dim'] ?? '')),
            'additionalProperty' => array_filter([
                isset($v['dim'])   ? ['@type'=>'PropertyValue','name'=>'尺寸','value'=>$v['dim']]   : null,
                isset($v['watt'])  ? ['@type'=>'PropertyValue','name'=>'功率','value'=>$v['watt'].' W'] : null,
                isset($v['lumen'])? ['@type'=>'PropertyValue','name'=>'光通量','value'=>$v['lumen'].' lm'] : null,
            ]),
            'offers' => $price > 0 ? [
                '@type'=>'Offer','priceCurrency'=>$cfg['currency'] ?? 'TWD',
                'price'=>(string)$price,'availability'=>'https://schema.org/InStock',
                'seller'=>['@type'=>'Organization','name'=>$cfg['brand'] ?? '中輝光電股份有限公司'],
            ] : null,
        ];
    }
    $variant_nodes = array_map(function($n){ return array_filter($n); }, $variant_nodes);

    $product_group = [
        '@type' => 'ProductGroup',
        '@id'   => $page_url . '#productgroup',
        'name'  => $cfg['title'],
        'description' => $cfg['meta_description'] ?? '',
        'image' => $hero_url ? [$hero_url] : [],
        'brand' => ['@type'=>'Brand','name'=>$cfg['brand'] ?? '中輝光電 Winstartek'],
        'manufacturer' => ['@type'=>'Organization','name'=>$cfg['brand'] ?? '中輝光電股份有限公司','url'=>'https://winstartek.com'],
        'category' => $cfg['category'] ?? '',
        'productGroupID' => $cfg['product_group_id'] ?? '',
        'variesBy' => ['size','wattage'],
    ];
    if ($prices) {
        $product_group['offers'] = [
            '@type'=>'AggregateOffer','priceCurrency'=>$cfg['currency'] ?? 'TWD',
            'lowPrice'=>(string)min($prices),'highPrice'=>(string)max($prices),
            'offerCount'=>(string)count($prices),'availability'=>'https://schema.org/InStock',
        ];
    }
    if (!empty($cfg['rating'])) {
        $product_group['aggregateRating'] = [
            '@type'=>'AggregateRating',
            'ratingValue'=>(string)$cfg['rating']['value'],
            'reviewCount'=>(string)$cfg['rating']['count'],
            'bestRating'=>'5',
        ];
    }
    if ($variant_nodes) $product_group['hasVariant'] = $variant_nodes;

    $faq_node = null;
    if (!empty($cfg['faq'])) {
        $faq_node = [
            '@type' => 'FAQPage',
            '@id'   => $page_url . '#faq',
            'mainEntity' => array_map(function($f) {
                return ['@type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['a']]];
            }, $cfg['faq']),
        ];
    }

    $crumbs = [];
    foreach (($cfg['breadcrumb'] ?? []) as $i => $c) {
        $crumbs[] = ['@type'=>'ListItem','position'=>$i+1,'name'=>$c['name'],'item'=>$c['url']];
    }
    $bc_node = $crumbs ? ['@type'=>'BreadcrumbList','itemListElement'=>$crumbs] : null;

    return ['@context'=>'https://schema.org','@graph'=>array_values(array_filter([$product_group, $faq_node, $bc_node]))];
}

// ------- 主流程 -------

$siteurl = siteurl();
$slug = $cfg['slug'];
$page_url = rtrim($cfg['canonical'] ?? ($siteurl . '/' . trim($slug, '/') . '/'), '/') . '/';

// hero image attachment (rel path from uploads)
$hero_aid = 0;
$hero_url = '';
if (!empty($cfg['hero']['file'])) {
    $hero_aid = find_or_create_attachment($cfg['hero']['file'], $cfg['hero']['title'] ?? $cfg['title'], $cfg['hero']['alt'] ?? $cfg['title']);
    $hero_url = $siteurl . '/wp-content/uploads/' . $cfg['hero']['file'];
}

// build body
$body  = '<div class="ws-prod">';
// hero (左圖右規格)
$body .= '<section class="ws-hero">';
if ($hero_url) {
    $body .= '<figure class="ws-hero-img"><img src="' . htmlspecialchars($hero_url) . '" alt="' . htmlspecialchars($cfg['hero']['alt'] ?? $cfg['title']) . '" loading="eager"/>';
    if (!empty($cfg['hero']['caption'])) $body .= '<figcaption>' . htmlspecialchars($cfg['hero']['caption']) . '</figcaption>';
    $body .= '</figure>';
}
$body .= '<div class="ws-hero-spec"><h2 id="specs">規格表</h2>' . render_specs($cfg['specs'] ?? []) . '</div></section>';

// TL;DR — 移到 hero 下方
if (!empty($cfg['tldr'])) {
    $body .= '<aside class="ws-tldr"><p><strong>一句話總結：</strong>' . htmlspecialchars($cfg['tldr']) . '</p></aside>';
}

// 特色
if (!empty($cfg['features'])) $body .= '<h2 id="features">產品特色</h2>' . render_features($cfg['features']);

// 應用場景
if (!empty($cfg['scenarios']) || !empty($cfg['app_list'])) {
    $body .= '<h2 id="scenarios">應用場景</h2>';
    if (!empty($cfg['scenarios_intro'])) $body .= '<p>' . htmlspecialchars($cfg['scenarios_intro']) . '</p>';
    if (!empty($cfg['scenarios'])) $body .= render_scenarios($cfg['scenarios']);
    if (!empty($cfg['app_list']))  $body .= render_app_list($cfg['app_list']);
}

// 安裝
if (!empty($cfg['installation'])) {
    $body .= '<h2 id="installation">' . htmlspecialchars($cfg['installation']['title'] ?? '安裝方式') . '</h2>';
    $body .= render_table($cfg['installation']['headers'], $cfg['installation']['rows']);
}

// 全系列型號
if (!empty($cfg['variants'])) {
    $body .= '<h2 id="variants">全系列型號</h2>';
    $headers = ['型號','尺寸','瓦數','光通量','適用空間'];
    $rows = [];
    foreach ($cfg['variants'] as $v) {
        $rows[] = [$v['sku'] ?? '', $v['dim'] ?? '', ($v['watt'] ?? '') . ' W', ($v['lumen'] ?? '') . ' lm', $v['room'] ?? ''];
    }
    $body .= render_table($headers, $rows);
}

// 比較
if (!empty($cfg['comparison'])) {
    $cmp = $cfg['comparison'];
    $body .= '<h2 id="comparison">' . htmlspecialchars($cmp['title']) . '</h2>';
    $body .= render_table($cmp['headers'], $cmp['rows'], 'ws-cmp', 1);
}

// FAQ
if (!empty($cfg['faq'])) $body .= '<h2 id="faq">常見問題（FAQ）</h2>' . render_faq($cfg['faq']);

// CTA
if (!empty($cfg['cta'])) {
    $cta = $cfg['cta'];
    $body .= '<aside class="ws-cta"><h3>' . htmlspecialchars($cta['title']) . '</h3>';
    $body .= '<p>' . htmlspecialchars($cta['subtitle'] ?? '') . '</p>';
    $body .= '<a class="btn" href="' . htmlspecialchars($cta['url']) . '">' . htmlspecialchars($cta['btn'] ?? '立即詢價') . '</a></aside>';
}
$body .= '</div>';

// schema
$schema = build_schema($cfg, $page_url, $hero_url);
$schema_json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$body .= "\n\n" . '<script type="application/ld+json">' . $schema_json . '</script>';

// ------- find or insert page -------
$pid = (int)($cfg['post_id'] ?? 0);
if (!$pid && !empty($cfg['slug'])) {
    $pid = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name='" . e(trim($slug,'/')) . "' AND post_type='page' LIMIT 1");
}

if ($dry) {
    echo "=== DRY RUN ===\n";
    echo "title: {$cfg['title']}\n";
    echo "slug:  $slug\n";
    echo "post_id: " . ($pid ?: 'NEW') . "\n";
    echo "hero attachment: $hero_aid\n";
    echo "body length: " . strlen($body) . "\n";
    echo "schema graph items: " . count($schema['@graph']) . "\n";
    echo "variants: " . count($cfg['variants'] ?? []) . "\n";
    echo "faq: " . count($cfg['faq'] ?? []) . "\n";
    exit;
}

$now = date('Y-m-d H:i:s');
$now_gmt = gmdate('Y-m-d H:i:s');
$parent = (int)($cfg['parent_id'] ?? 0);

if ($pid) {
    // update existing
    mysqli_query($mysqli, "UPDATE {$wpdb->posts} SET
        post_title='" . e($cfg['title']) . "',
        post_content='" . e($body) . "',
        post_modified='$now',
        post_modified_gmt='$now_gmt',
        post_parent=$parent
        WHERE ID=$pid");
    echo "UPDATED page #$pid\n";
} else {
    $sql = "INSERT INTO {$wpdb->posts}
      (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,
       post_status,comment_status,ping_status,post_password,post_name,
       to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,
       post_parent,guid,menu_order,post_type,post_mime_type,comment_count)
      VALUES (1,'$now','$now_gmt','" . e($body) . "','" . e($cfg['title']) . "','',
       'publish','closed','closed','','" . e(trim($slug,'/')) . "',
       '','','$now','$now_gmt','',
       $parent,'" . e($page_url) . "',0,'page','',0)";
    mysqli_query($mysqli, $sql);
    $pid = mysqli_insert_id($mysqli);
    if (!$pid) { fwrite(STDERR, "FAIL: " . mysqli_error($mysqli) . "\n"); exit(1); }
    echo "CREATED page #$pid\n";
}

// postmeta
upsert_meta($pid, '_ws_product_template', '1');
if ($hero_aid) upsert_meta($pid, '_thumbnail_id', (string)$hero_aid);

// RankMath
if (!empty($cfg['focus_keyword']))   upsert_meta($pid, 'rank_math_focus_keyword', $cfg['focus_keyword']);
if (!empty($cfg['meta_title']))      upsert_meta($pid, 'rank_math_title', $cfg['meta_title']);
if (!empty($cfg['meta_description']))upsert_meta($pid, 'rank_math_description', $cfg['meta_description']);
if (!empty($cfg['canonical']))       upsert_meta($pid, 'rank_math_canonical_url', $cfg['canonical']);

echo "OK page_id=$pid url=$page_url\n";
echo "DONE.\n";
