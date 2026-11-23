<?php
/**
 * Plugin Name: Winstartek Product Category Schema
 * Description: Adds CollectionPage and ItemList JSON-LD for product listing/category pages.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function ws_pcat_schema_clean(string $value): string {
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = wp_strip_all_tags($value, true);
    $value = preg_replace('/\s+/u', ' ', (string) $value);
    return trim((string) $value);
}

function ws_pcat_schema_path_parts(string $url): array {
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    $parts = $path === '' ? [] : array_values(array_filter(explode('/', $path), static fn($part) => $part !== ''));
    $langs = ['en', 'ar', 'de', 'hi', 'id', 'it', 'ja', 'ko', 'pt-br', 'es', 'th', 'vi'];
    if ($parts && in_array($parts[0], $langs, true)) {
        array_shift($parts);
    }
    return $parts;
}

function ws_pcat_schema_is_products_page(): bool {
    if (!is_singular('page')) {
        return false;
    }

    $parts = ws_pcat_schema_path_parts((string) get_permalink());
    return !empty($parts) && $parts[0] === 'products';
}

function ws_pcat_schema_language(): string {
    if (defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE) {
        return (string) ICL_LANGUAGE_CODE;
    }

    $locale = get_locale();
    return $locale ? str_replace('_', '-', $locale) : 'zh-hant';
}

function ws_pcat_schema_name(): string {
    $title = ws_pcat_schema_clean((string) get_the_title());
    if ($title === '') {
        return 'WINSTAR LED Lighting Products';
    }

    $parts = preg_split('/\s*(?:\||｜|–|—|-)\s*/u', $title, -1, PREG_SPLIT_NO_EMPTY);
    if (is_array($parts) && count($parts) > 1) {
        $last = ws_pcat_schema_clean((string) end($parts));
        return $last !== '' ? $last : $title;
    }

    return $title;
}

function ws_pcat_schema_description(int $post_id): string {
    $description = ws_pcat_schema_clean((string) get_post_meta($post_id, 'rank_math_description', true));
    if ($description !== '') {
        return $description;
    }

    $excerpt = ws_pcat_schema_clean((string) get_the_excerpt($post_id));
    if ($excerpt !== '') {
        return $excerpt;
    }

    return ws_pcat_schema_name() . ' | WINSTAR LED lighting products and solutions.';
}

function ws_pcat_schema_link_name(string $href, string $text): string {
    $text = ws_pcat_schema_clean($text);
    if ($text !== '' && !preg_match('/^(more|read more|更多|查看|view)$/iu', $text)) {
        return $text;
    }

    $parts = ws_pcat_schema_path_parts($href);
    $slug = $parts ? (string) end($parts) : '';
    $slug = trim(str_replace(['-', '_'], ' ', $slug));
    return $slug !== '' ? ucwords($slug) : 'Product category';
}

function ws_pcat_schema_title_from_post(int $post_id): string {
    $title = ws_pcat_schema_clean((string) get_the_title($post_id));
    $parts = preg_split('/\s*(?:\||｜|–|—|-)\s*/u', $title, -1, PREG_SPLIT_NO_EMPTY);
    if (is_array($parts) && count($parts) > 1) {
        $last = ws_pcat_schema_clean((string) end($parts));
        return $last !== '' ? $last : $title;
    }
    return $title !== '' ? $title : 'Product category';
}

function ws_pcat_schema_child_page_items(int $post_id, string $current_url): array {
    $current_parts = ws_pcat_schema_path_parts($current_url);
    if (!$current_parts) {
        return [];
    }

    $ids = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'suppress_filters' => false,
    ]);

    $one_level = [];
    $descendants = [];

    foreach ($ids as $candidate_id) {
        $candidate_id = (int) $candidate_id;
        if ($candidate_id === $post_id) {
            continue;
        }

        $url = get_permalink($candidate_id);
        $parts = ws_pcat_schema_path_parts((string) $url);
        if (count($parts) <= count($current_parts)) {
            continue;
        }

        if (array_slice($parts, 0, count($current_parts)) !== $current_parts) {
            continue;
        }

        $entry = [
            '@type' => 'ListItem',
            'position' => 0,
            'url' => trailingslashit($url),
            'name' => ws_pcat_schema_title_from_post($candidate_id),
        ];

        if (count($parts) === count($current_parts) + 1) {
            $one_level[] = $entry;
        } else {
            $descendants[] = $entry;
        }
    }

    $items = $one_level ?: $descendants;
    $items = array_slice($items, 0, 24);
    foreach ($items as $index => $item) {
        $items[$index]['position'] = $index + 1;
    }

    return $items;
}

function ws_pcat_schema_content_link_items(int $post_id, string $current_url): array {
    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return [];
    }

    preg_match_all('#<a\b[^>]*\bhref=(["\'])(.*?)\1[^>]*>(.*?)</a>#isu', $content, $matches, PREG_SET_ORDER);
    if (!$matches) {
        return [];
    }

    $items = [];
    $seen = [];
    $current = untrailingslashit(strtok($current_url, '#?'));

    foreach ($matches as $match) {
        $href = html_entity_decode((string) $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            continue;
        }

        $url = esc_url_raw(wp_make_link_relative($href));
        $absolute = esc_url_raw(home_url($url));
        $absolute_base = untrailingslashit(strtok($absolute, '#?'));
        if ($absolute_base === $current) {
            continue;
        }

        $parts = ws_pcat_schema_path_parts($absolute);
        if (empty($parts) || $parts[0] !== 'products') {
            continue;
        }

        $key = untrailingslashit($absolute_base);
        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'url' => $absolute_base . '/',
            'name' => ws_pcat_schema_link_name($absolute, (string) $match[3]),
        ];

        if (count($items) >= 24) {
            break;
        }
    }

    return $items;
}

function ws_pcat_schema_items(int $post_id, string $current_url): array {
    $items = ws_pcat_schema_child_page_items($post_id, $current_url);
    if ($items) {
        return $items;
    }

    return ws_pcat_schema_content_link_items($post_id, $current_url);
}

add_action('wp_head', static function (): void {
    if (is_admin() || !ws_pcat_schema_is_products_page()) {
        return;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return;
    }

    $url = get_permalink($post_id);

    // 2026-11 整併：只有真的列出子產品的列表/分類頁才輸出 CollectionPage；
    // 單品 page（ItemList 為空）不再輸出，避免與 Rank Math WebPage、stub Product 互相矛盾。
    $items = ws_pcat_schema_items($post_id, (string) $url);
    if (!$items) {
        return;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        '@id' => trailingslashit($url) . '#collection',
        'url' => trailingslashit($url),
        'name' => ws_pcat_schema_name(),
        'description' => ws_pcat_schema_description($post_id),
        'inLanguage' => ws_pcat_schema_language(),
        'isPartOf' => [
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'name' => 'WINSTAR',
            'url' => home_url('/'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            '@id' => home_url('/#organization'),
            'name' => 'WINSTAR / 中輝光電股份有限公司',
            'url' => home_url('/'),
        ],
    ];

    $schema['mainEntity'] = [
        '@type' => 'ItemList',
        'itemListElement' => $items,
    ];

    echo "\n<script type=\"application/ld+json\" data-ws-product-category-schema=\"1\">";
    echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    echo "</script>\n";
}, 98);
