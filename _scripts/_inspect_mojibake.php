<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// Use BINARY comparison to be case-sensitive
echo "=== BINARY counts (true mojibake patterns only) ===\n";
foreach (['Ã ', 'Â ', 'â€', 'Ã©', 'Ã¨', 'Ã¥', 'å¤', 'ä¸', 'æ–'] as $needle) {
    $pm = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}postmeta WHERE meta_value LIKE BINARY %s", '%'.$needle.'%'));
    $po = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}posts WHERE post_content LIKE BINARY %s OR post_title LIKE BINARY %s OR post_excerpt LIKE BINARY %s", '%'.$needle.'%','%'.$needle.'%','%'.$needle.'%'));
    $op = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}options WHERE option_value LIKE BINARY %s", '%'.$needle.'%'));
    echo "  \"$needle\" (binary) : postmeta=$pm posts=$po options=$op\n";
}

echo "\n=== Sample posts having Ã (case-sensitive uppercase) ===\n";
$rows = $wpdb->get_results("SELECT ID, post_type, post_title FROM {$prefix}posts WHERE post_content LIKE BINARY '%Ã %' LIMIT 10");
echo "  count: " . count($rows) . "\n";
foreach ($rows as $r) echo "    ID={$r->ID} type={$r->post_type} title={$r->post_title}\n";

echo "\n=== Sample postmeta having Ã (case-sensitive uppercase) ===\n";
$rows = $wpdb->get_results("SELECT post_id, meta_key FROM {$prefix}postmeta WHERE meta_value LIKE BINARY '%Ã %' LIMIT 10");
echo "  count: " . count($rows) . "\n";
foreach ($rows as $r) echo "    post={$r->post_id} key={$r->meta_key}\n";

echo "\n=== bytes around 'Ã ' in postmeta of those records (raw hex) ===\n";
foreach ($rows as $r) {
    $val = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key=%s LIMIT 1", $r->post_id, $r->meta_key));
    $pos = strpos($val, "Ã ");
    if ($pos === false) continue;
    $snip = substr($val, max(0,$pos-20), 60);
    $hex = bin2hex(substr($val, max(0,$pos-3), 12));
    echo "  post={$r->post_id} hex around Ã: $hex\n";
    echo "    snip: $snip\n";
}
