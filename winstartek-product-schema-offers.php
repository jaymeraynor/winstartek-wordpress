<?php
/**
 * Plugin Name: Winstartek Product Schema Offers
 * Description: Adds quote-style offers to Winstartek product structured data so product pages satisfy Google product snippet requirements.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        'name' => wp_strip_all_tags(get_the_title()),
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
        'offers' => winstartek_schema_offer(),
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

function winstartek_add_offers_to_schema_node(&$node): void
{
    if (!is_array($node)) {
        return;
    }

    if (isset($node['@type']) && winstartek_is_product_schema_node($node['@type'])) {
        if (empty($node['offers'])) {
            $node['offers'] = winstartek_schema_offer();
        }

        if (in_array('ProductGroup', is_array($node['@type']) ? $node['@type'] : [$node['@type']], true) && empty($node['hasVariant'])) {
            $variant = winstartek_schema_product_stub();
            $variant['isVariantOf'] = ['@id' => $node['@id'] ?? trailingslashit(get_permalink()) . '#productgroup'];
            $node['hasVariant'] = [$variant];
        }
    }

    foreach ($node as &$child) {
        if (is_array($child)) {
            winstartek_add_offers_to_schema_node($child);
        }
    }
    unset($child);
}

add_filter('rank_math/json_ld', function ($data) {
    if (!winstartek_is_product_detail_context()) {
        return $data;
    }

    winstartek_add_offers_to_schema_node($data);
    return $data;
}, 99);

add_filter('the_content', function ($content) {
    if (!winstartek_is_product_detail_context() || stripos($content, 'ws-product-schema') === false) {
        return $content;
    }

    return preg_replace_callback(
        '#(<script\b[^>]*class=["\'][^"\']*\bws-product-schema\b[^"\']*["\'][^>]*>)(.*?)(</script>)#is',
        function ($matches) {
            $json = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return $matches[0];
            }

            winstartek_add_offers_to_schema_node($data);
            $encoded = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded ? $matches[1] . $encoded . $matches[3] : $matches[0];
        },
        $content
    );
}, 99);

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
    if (!winstartek_is_product_detail_context()) {
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
