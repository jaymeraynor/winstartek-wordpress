<?php
/**
 * Plugin Name: Winstartek Performance
 * Description: CWV 優化：移除 bloat、defer JS、resource hints、WebP 支援、lazy/async 圖片
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

/* ---------- 1. Remove header bloat ---------- */
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('template_redirect', 'rest_output_link_header', 11);

/* ---------- 2. Disable emojis (saves ~14KB + JS exec) ---------- */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('tiny_mce_plugins', function($plugins) {
    return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
});
add_filter('emoji_svg_url', '__return_false');

/* ---------- 3. Disable wp-embed.js for front-end ---------- */
add_action('wp_footer', function() {
    wp_dequeue_script('wp-embed');
});

/* ---------- 4. Drop jQuery Migrate (modern themes don't need it) ---------- */
add_action('wp_default_scripts', function($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $deps = $scripts->registered['jquery']->deps;
        $scripts->registered['jquery']->deps = array_diff($deps, ['jquery-migrate']);
    }
});

/* ---------- 5. Defer non-critical JS ---------- */
add_filter('script_loader_tag', function($tag, $handle) {
    if (is_admin()) return $tag;
    // 不要 defer 的（首屏需要的、或已知會爆掉的）
    $skip = [
        'jquery-core', 'jquery', 'jquery-migrate',
        // Elementor 初始化鏈
        'elementor-frontend', 'elementor-frontend-modules', 'elementor-pro-frontend',
        'elementor-waypoints', 'elementor-pro-waypoints', 'imagesloaded', 'jquery-ui-core',
        'swiper', 'share-link', 'wp-polyfill',
        // WPML
        'sitepress',
    ];
    if (in_array($handle, $skip, true)) return $tag;
    // 跳過 inline/handheld/條件式
    if (strpos($tag, ' defer') !== false || strpos($tag, ' async') !== false) return $tag;
    // 帶 type="module" 的本來就 defer，不重複加
    if (preg_match('/\stype=("|\')module\1/i', $tag)) return $tag;
    return str_replace(' src=', ' defer src=', $tag);
}, 10, 2);

/* ---------- 6. Resource hints (preconnect to common third-party) ---------- */
add_filter('wp_resource_hints', function($hints, $relation) {
    if ($relation === 'preconnect') {
        $hints[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
        $hints[] = ['href' => 'https://fonts.googleapis.com'];
        $hints[] = 'https://www.google-analytics.com';
        $hints[] = 'https://www.googletagmanager.com';
    }
    if ($relation === 'dns-prefetch') {
        $hints[] = '//www.google.com';
        $hints[] = '//stats.g.doubleclick.net';
    }
    return $hints;
}, 10, 2);

/* ---------- 7. Force lazy + async-decode on all front-end images ---------- */
add_filter('wp_get_attachment_image_attributes', function($attr) {
    if (empty($attr['loading'])) $attr['loading'] = 'lazy';
    if (empty($attr['decoding'])) $attr['decoding'] = 'async';
    return $attr;
});

add_filter('the_content', function($html) {
    if (is_admin() || is_feed()) return $html;
    // add decoding="async" to <img> that don't have it
    $html = preg_replace_callback('/<img\b([^>]*)>/i', function($m) {
        $tag = $m[0];
        if (stripos($tag, 'decoding=') === false) {
            $tag = preg_replace('/<img\b/i', '<img decoding="async"', $tag, 1);
        }
        if (stripos($tag, 'loading=') === false) {
            $tag = preg_replace('/<img\b/i', '<img loading="lazy"', $tag, 1);
        }
        return $tag;
    }, $html);
    return $html;
}, 99);

/* ---------- 8. WebP serving — rewrite <img> to <picture> when .webp exists ---------- */
function ws_perf_webp_path($url) {
    static $upload_url = null, $upload_dir = null;
    if ($upload_url === null) {
        $u = wp_get_upload_dir();
        $upload_url = trailingslashit($u['baseurl']);
        $upload_dir = trailingslashit($u['basedir']);
    }
    if (strpos($url, $upload_url) !== 0) return false;
    $rel = substr($url, strlen($upload_url));
    $abs = $upload_dir . $rel;
    $webp_abs = preg_replace('/\.(jpe?g|png)$/i', '.webp', $abs);
    if ($webp_abs === $abs) return false;
    if (!file_exists($webp_abs)) return false;
    return preg_replace('/\.(jpe?g|png)$/i', '.webp', $url);
}

add_filter('the_content', function($html) {
    if (is_admin() || is_feed()) return $html;
    return preg_replace_callback('/<img\b([^>]*?)src=("|\')([^"\']+)(\2)([^>]*)>/i', function($m) {
        $url = $m[3];
        $webp = ws_perf_webp_path($url);
        if (!$webp) return $m[0];
        $before = $m[1];
        $after  = $m[5];
        $img = '<img' . $before . 'src="' . esc_url($webp) . '"' . $after . '>';
        return '<picture><source srcset="' . esc_url($webp) . '" type="image/webp">' . $img . '</picture>';
    }, $html);
}, 100);

/* ---------- 9. Disable XML-RPC (security + perf) ---------- */
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

/* ---------- 10. Slim heartbeat (less admin-ajax noise) ---------- */
add_filter('heartbeat_settings', function($s) {
    $s['interval'] = 60;
    return $s;
});
