<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// proper check: rawurldecode each path before file_exists
$rows = $wpdb->get_results("SELECT ID, post_content FROM {$prefix}posts WHERE post_status='publish' AND post_content LIKE '%<img%'");
$missing_files = []; // file path => list of post IDs
foreach ($rows as $r) {
    if (preg_match_all('#<img[^>]+src=["\']([^"\']+)["\']#i', $r->post_content, $m)) {
        foreach ($m[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            if (strpos($path, '/wp-content/uploads/') === false) continue; // external
            $decoded = rawurldecode($path);
            $local = WP_CONTENT_DIR . str_replace('/wp-content', '', $decoded);
            if (!file_exists($local)) {
                $missing_files[$decoded][] = $r->ID;
            }
        }
    }
}

// also _elementor_data
$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key='_elementor_data'");
foreach ($rows as $r) {
    if (preg_match_all('#https?://[^\s"\'<>]+?\.(?:jpe?g|png|gif|webp|svg|avif)#i', $r->meta_value, $m)) {
        foreach ($m[0] as $url) {
            if (strpos($url, '/wp-content/uploads/') === false) continue;
            $path = parse_url($url, PHP_URL_PATH);
            $decoded = rawurldecode($path);
            $local = WP_CONTENT_DIR . str_replace('/wp-content', '', $decoded);
            if (!file_exists($local)) {
                $missing_files[$decoded][] = 'ed:'.$r->post_id;
            }
        }
    }
    if (preg_match_all('#"url":"([^"]+\.(?:jpe?g|png|gif|webp|svg|avif))"#i', $r->meta_value, $m)) {
        foreach ($m[1] as $url) {
            if (strpos($url, '/wp-content/uploads/') === false) continue;
            $path = parse_url($url, PHP_URL_PATH);
            $decoded = rawurldecode($path);
            $local = WP_CONTENT_DIR . str_replace('/wp-content', '', $decoded);
            if (!file_exists($local)) {
                $missing_files[$decoded][] = 'ed:'.$r->post_id;
            }
        }
    }
}

echo "true missing image files (rawurldecode + file_exists):\n";
echo "  unique missing files: " . count($missing_files) . "\n\n";
$i = 0;
foreach ($missing_files as $path => $refs) {
    if ($i++ >= 30) { echo "  ... and " . (count($missing_files)-30) . " more\n"; break; }
    echo "  $path  (" . count(array_unique($refs)) . " refs, e.g. " . implode(',', array_slice(array_unique($refs),0,3)) . ")\n";
}
