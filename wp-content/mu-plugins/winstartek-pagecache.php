<?php
/**
 * Plugin Name: Winstartek Page Cache
 * Description: 輕量靜態頁面快取（非登入使用者）。寫入 wp-content/cache/winstartek/
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

class WS_PageCache {
    const TTL = 43200;          // 12h
    const DIR = WP_CONTENT_DIR . '/cache/winstartek';
    private static $key = null;

    public static function init() {
        if (defined('DOING_CRON') || defined('WP_CLI') || defined('REST_REQUEST') || defined('XMLRPC_REQUEST') || defined('DOING_AJAX')) return;
        if (PHP_SAPI === 'cli') return;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
        if (!empty($_GET) && !self::query_is_cacheable()) return;
        if (self::has_logged_in_cookie()) return;
        if (is_admin()) return;
        // skip preview/draft/admin paths
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (preg_match('#^/(wp-admin|wp-login|wp-cron|wp-json|xmlrpc)#', $uri)) return;

        self::$key = self::cache_key();
        $file = self::cache_file(self::$key);

        // serve cached
        if (file_exists($file) && (time() - filemtime($file)) < self::TTL) {
            header('X-WS-Cache: HIT');
            header('Content-Type: text/html; charset=UTF-8');
            readfile($file);
            exit;
        }

        ob_start([__CLASS__, 'finalize']);
    }

    public static function finalize($buffer) {
        if (strlen($buffer) < 256) return $buffer;
        if (is_user_logged_in()) return $buffer;
        if (function_exists('is_404') && is_404()) return $buffer;
        if (function_exists('is_search') && is_search()) return $buffer;
        if (function_exists('is_feed') && is_feed()) return $buffer;
        if (http_response_code() !== 200) return $buffer;
        // make sure it's HTML
        $headers = headers_list();
        $is_html = false;
        foreach ($headers as $h) {
            if (stripos($h, 'Content-Type:') === 0 && stripos($h, 'text/html') !== false) { $is_html = true; break; }
            if (stripos($h, 'Content-Type:') === 0) return $buffer;
        }
        $file = self::cache_file(self::$key);
        @wp_mkdir_p(dirname($file));
        @file_put_contents($file, $buffer, LOCK_EX);
        header('X-WS-Cache: MISS');
        return $buffer;
    }

    private static function has_logged_in_cookie() {
        foreach ($_COOKIE as $k => $_) {
            if (strpos($k, 'wordpress_logged_in') === 0) return true;
            if (strpos($k, 'comment_author_') === 0) return true;
            if (strpos($k, 'wp-postpass_') === 0) return true;
            if ($k === 'woocommerce_items_in_cart' || $k === 'woocommerce_cart_hash') return true;
            if (strpos($k, 'wp_woocommerce_session_') === 0) return true;
        }
        return false;
    }

    private static function query_is_cacheable() {
        // 允許單一 lang 參數通過（WPML 有時用），其他一律繞過
        $allow = ['lang'];
        foreach (array_keys($_GET) as $k) {
            if (!in_array($k, $allow, true)) return false;
        }
        return true;
    }

    private static function cache_key() {
        $host  = $_SERVER['HTTP_HOST'] ?? '';
        $uri   = $_SERVER['REQUEST_URI'] ?? '/';
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $mobile = (stripos($_SERVER['HTTP_USER_AGENT'] ?? '', 'mobile') !== false) ? 'm' : 'd';
        return md5($proto . '|' . $host . '|' . $uri . '|' . $mobile);
    }

    private static function cache_file($key) {
        return self::DIR . '/' . substr($key, 0, 2) . '/' . $key . '.html';
    }

    public static function purge_all() {
        if (!is_dir(self::DIR)) return;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::DIR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            if ($f->isFile()) @unlink($f->getPathname());
        }
    }
}

WS_PageCache::init();

// 內容變更時清光快取（簡單粗暴；對 SEO 上傳量不大的 B2B 站完全 OK）
add_action('save_post',           ['WS_PageCache', 'purge_all']);
add_action('deleted_post',        ['WS_PageCache', 'purge_all']);
add_action('comment_post',        ['WS_PageCache', 'purge_all']);
add_action('switch_theme',        ['WS_PageCache', 'purge_all']);
add_action('activated_plugin',    ['WS_PageCache', 'purge_all']);
add_action('deactivated_plugin',  ['WS_PageCache', 'purge_all']);
add_action('updated_option',      function($opt) {
    if (in_array($opt, ['blogname','blogdescription','siteurl','home','permalink_structure','active_plugins','template','stylesheet'], true)) {
        WS_PageCache::purge_all();
    }
});

// admin bar 提供清快取連結（登入者）
add_action('admin_bar_menu', function($bar) {
    if (!current_user_can('manage_options')) return;
    $bar->add_node([
        'id'    => 'ws-purge-cache',
        'title' => '清頁面快取',
        'href'  => wp_nonce_url(admin_url('admin-post.php?action=ws_purge_cache'), 'ws_purge_cache'),
    ]);
}, 100);

add_action('admin_post_ws_purge_cache', function() {
    if (!current_user_can('manage_options')) wp_die('no');
    check_admin_referer('ws_purge_cache');
    WS_PageCache::purge_all();
    wp_safe_redirect(wp_get_referer() ?: admin_url());
    exit;
});
