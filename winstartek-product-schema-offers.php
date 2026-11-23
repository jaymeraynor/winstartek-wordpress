<?php
/**
 * Plugin Name: Winstartek Product Schema Offers
 * Description: Winstartek product structured data helpers. Since 2026-11 (schema consolidation, option B) it outputs NO Offer/AggregateOffer: it strips offers from product schema, removes duplicate BreadcrumbList from inline ws-product-schema, and prints a fallback Product stub only on product pages that have no other product schema.
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @deprecated 2026-11 schema 整併（乙方案：不輸出 Offer）。本外掛已不再呼叫；
 * 保留函式只為避免其他未備份到的程式（如 theme）直接呼叫時 fatal error。
 */
function winstartek_schema_offer(): array
{
    return [
        '@type' => 'Offer',
        'url' => get_permalink(),
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'seller' => [
            '@type' => 'Organization',
            'name' => '中輝光電股份有限公司',
            'url' => home_url('/'),
        ],
    ];
}

function winstartek_is_product_detail_context(): bool
{
    if (is_singular('product')) {
        return true;
    }

    if (!is_page()) {
        return false;
    }

    $path = trim((string) wp_parse_url(get_permalink(), PHP_URL_PATH), '/');
    if ($path === '') {
        return false;
    }

    $listing_paths = [
        'products',
        'products/indoor-and-commercial-lighting',
        'products/outdoor-lighting',
        'products/industrial-lighting',
        'products/light-source',
        'products/engineering-project',
        'products/energy-storage-system',
        'products/special-application',
        'products/energy-saving-label',
    ];

    return str_starts_with($path, 'products/') && !in_array($path, $listing_paths, true);
}

function winstartek_schema_product_stub(): array
{
    $image = get_the_post_thumbnail_url(get_the_ID(), 'full');

    $product = [
        '@type' => 'Product',
        '@id' => trailingslashit(get_permalink()) . '#product',
        'name' => html_entity_decode(wp_strip_all_tags(get_the_title()), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'url' => get_permalink(),
        'sku' => get_post_field('post_name', get_the_ID()) ?: (string) get_the_ID(),
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Winstartek',
        ],
        'manufacturer' => [
            '@type' => 'Organization',
            'name' => '中輝光電股份有限公司',
            'url' => home_url('/'),
        ],
    ];

    $description = wp_strip_all_tags(get_the_excerpt());
    if ($description !== '') {
        $product['description'] = $description;
    }

    if ($image) {
        $product['image'] = [$image];
    }

    return $product;
}

function winstartek_is_product_schema_node($type): bool
{
    $types = is_array($type) ? $type : [$type];
    return in_array('Product', $types, true) || in_array('ProductGroup', $types, true);
}

/**
 * 2026-11 schema 整併（乙方案）：移除 Product／ProductGroup（含 hasVariant 變體）上的 offers。
 * 不新增任何節點（舊版會在缺 hasVariant 時自動補一個 stub 變體，已取消）。
 */
function winstartek_strip_offers_from_schema_node(&$node): void
{
    if (!is_array($node)) {
        return;
    }

    if (isset($node['@type']) && winstartek_is_product_schema_node($node['@type'])) {
        unset($node['offers']);
    }

    foreach ($node as &$child) {
        if (is_array($child)) {
            winstartek_strip_offers_from_schema_node($child);
        }
    }
    unset($child);
}

/**
 * @deprecated 2026-11：舊名稱保留為相容層，行為改為「移除 offers」，避免外部呼叫 fatal。
 */
function winstartek_add_offers_to_schema_node(&$node): void
{
    winstartek_strip_offers_from_schema_node($node);
}

/**
 * 內文 ws-product-schema 若自帶 BreadcrumbList，移除之（Rank Math 已輸出一份）。
 */
function winstartek_strip_breadcrumb_nodes(array $data): array
{
    $is_bc = static function ($node): bool {
        if (!is_array($node) || !isset($node['@type'])) {
            return false;
        }
        $types = is_array($node['@type']) ? $node['@type'] : [$node['@type']];
        return in_array('BreadcrumbList', $types, true);
    };

    if (isset($data['@graph']) && is_array($data['@graph'])) {
        $data['@graph'] = array_values(array_filter($data['@graph'], static fn($n) => !$is_bc($n)));
    } elseif ($data !== [] && array_keys($data) === range(0, count($data) - 1)) {
        $data = array_values(array_filter($data, static fn($n) => !$is_bc($n)));
    }

    return $data;
}

/**
 * 頁面是否已由其他來源輸出「主」Product/ProductGroup（2026-11 schema 整併）。
 * 有的話，wp_head 的 stub Product 就不再輸出，避免同頁兩個 Product 實體／同 @id 衝突。
 */
function winstartek_page_has_primary_product_schema(): bool
{
    $post_id = (int) get_queried_object_id();
    if (!$post_id) {
        return false;
    }

    // 1) 專屬頁外掛（ws-linear-lamp-product、ws-ward-light-schema、winstartek-standard-product-system）以 filter 宣告
    if (apply_filters('winstartek_has_primary_product_schema', false, $post_id)) {
        return true;
    }

    // 2) 標準產品系統有資料時會自行輸出 ProductGroup（filter 之外的保險）
    if (function_exists('ws_spt_has_data') && ws_spt_has_data($post_id)) {
        return true;
    }

    // 3) 內文（post_content 或 Elementor 資料）已內嵌 ws-product-schema JSON-LD
    if (stripos((string) get_post_field('post_content', $post_id), 'ws-product-schema') !== false) {
        return true;
    }

    return stripos((string) get_post_meta($post_id, '_elementor_data', true), 'ws-product-schema') !== false;
}

/**
 * 處理內文中的 <script class="ws-product-schema">：移除 offers 與 BreadcrumbList。
 */
function winstartek_filter_inline_product_schema($content)
{
    if (!is_string($content) || !winstartek_is_product_detail_context() || stripos($content, 'ws-product-schema') === false) {
        return $content;
    }

    $result = preg_replace_callback(
        '#(<script\b[^>]*class=["\'][^"\']*\bws-product-schema\b[^"\']*["\'][^>]*>)(.*?)(</script>)#is',
        function ($matches) {
            $json = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return $matches[0];
            }

            winstartek_strip_offers_from_schema_node($data);
            $data = winstartek_strip_breadcrumb_nodes($data);
            $encoded = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded ? $matches[1] . $encoded . $matches[3] : $matches[0];
        },
        $content
    );

    return is_string($result) ? $result : $content;
}

add_filter('rank_math/json_ld', function ($data) {
    if (!winstartek_is_product_detail_context()) {
        return $data;
    }

    winstartek_strip_offers_from_schema_node($data);
    return $data;
}, 99);

add_filter('the_content', 'winstartek_filter_inline_product_schema', 99);
// Elementor 版面輸出的內容另走這個 filter；同一函式可重複套用（冪等）
add_filter('elementor/frontend/the_content', 'winstartek_filter_inline_product_schema', 99);

add_action('wp', function () {
    if (!is_singular()) {
        return;
    }

    global $wpdb;
    $post_id = get_queried_object_id();
    if (!$post_id) {
        return;
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
            $post_id,
            'rank_math_schema%'
        )
    );

    foreach ($rows as $row) {
        $schema = maybe_unserialize($row->meta_value);
        if (!is_array($schema) || empty($schema['@type'])) {
            delete_metadata_by_mid('post', (int) $row->meta_id);
        }
    }
}, 1);

add_action('wp_head', function () {
    // 2026-11 整併：已有主產品 schema 的頁面不印 stub；stub 本身不含 Offer。
    // 若日後決定完全不輸出 stub，在任一 mu-plugin 加：add_filter('winstartek_output_product_stub', '__return_false');
    if (!winstartek_is_product_detail_context() || winstartek_page_has_primary_product_schema()) {
        return;
    }
    if (!apply_filters('winstartek_output_product_stub', true, (int) get_queried_object_id())) {
        return;
    }

    echo "\n<script type=\"application/ld+json\" class=\"winstartek-product-offer-schema\">";
    echo wp_json_encode(winstartek_schema_product_stub(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "</script>\n";
}, 99);

add_filter('rank_math/sitemap/post_type_archive_link', function ($archive_url, $post_type) {
    if ($post_type === 'product') {
        return false;
    }

    return $archive_url;
}, 99, 2);

add_action('init', function () {
    $path = (string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#/product-sitemap[0-9]*\.xml$#', $path)) {
        return;
    }

    ob_start(function ($xml) {
        return preg_replace(
            '#\s*<url>\s*<loc>https://winstartek\.com/product/</loc>.*?</url>#s',
            '',
            $xml
        );
    });
}, 0);
