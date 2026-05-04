<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

echo "1. postmeta _elementor_data 還有 'Nian' 嗎?\n";
$rows = $wpdb->get_results("SELECT post_id FROM {$prefix}postmeta WHERE meta_key='_elementor_data' AND meta_value LIKE '%Nian%'");
echo "   matches: " . count($rows) . "\n";
foreach ($rows as $r) echo "     post_id={$r->post_id}\n";

echo "\n2. 其他 postmeta key 含 'Nian' 嗎?\n";
$rows = $wpdb->get_results("SELECT post_id, meta_key, SUBSTRING(meta_value, GREATEST(1,LOCATE('Nian',meta_value)-30), 100) AS snip FROM {$prefix}postmeta WHERE meta_value LIKE '%Nian%' AND meta_key != '_elementor_data' LIMIT 20");
echo "   matches: " . count($rows) . "\n";
foreach ($rows as $r) echo "     post={$r->post_id} key={$r->meta_key} snip={$r->snip}\n";

echo "\n3. wp_posts 任何欄位含 'Nian' 嗎?\n";
$rows = $wpdb->get_results("SELECT ID, post_type, post_status, post_title FROM {$prefix}posts WHERE post_content LIKE '%Nian%' OR post_excerpt LIKE '%Nian%' OR post_title LIKE '%Nian%' LIMIT 20");
echo "   matches: " . count($rows) . "\n";
foreach ($rows as $r) echo "     ID={$r->ID} type={$r->post_type} status={$r->post_status} title={$r->post_title}\n";

echo "\n4. wp_options 含 'Nian'?\n";
$rows = $wpdb->get_results("SELECT option_name, autoload, LENGTH(option_value) AS bytes FROM {$prefix}options WHERE option_value LIKE '%Nian%'");
echo "   matches: " . count($rows) . "\n";
foreach ($rows as $r) echo "     {$r->option_name} ({$r->autoload}, {$r->bytes} bytes)\n";

echo "\n5. WPML icl_strings / icl_string_translations 含 'Nian'?\n";
$rows = $wpdb->get_results("SELECT id, name, value FROM {$prefix}icl_strings WHERE value LIKE '%Nian%' LIMIT 20");
echo "   icl_strings: " . count($rows) . "\n";
foreach ($rows as $r) echo "     id={$r->id} name={$r->name} value={$r->value}\n";

$rows = $wpdb->get_results("SELECT id, string_id, language, value FROM {$prefix}icl_string_translations WHERE value LIKE '%Nian%' LIMIT 20");
echo "   icl_string_translations: " . count($rows) . "\n";
foreach ($rows as $r) echo "     id={$r->id} string_id={$r->string_id} lang={$r->language} value={$r->value}\n";

echo "\n6. WPML icl_translate (translation work table) 含 'Nian'?\n";
$rows = $wpdb->get_results("SELECT job_id, field_type, field_data_translated FROM {$prefix}icl_translate WHERE field_data_translated LIKE '%Nian%' OR field_data LIKE '%Nian%' LIMIT 20");
echo "   matches: " . count($rows) . "\n";
foreach ($rows as $r) echo "     job_id={$r->job_id} type={$r->field_type} v=" . substr($r->field_data_translated, 0, 80) . "\n";

echo "\n7. 檔案系統 — uploads/elementor/css/ 含 'Nian' 的檔案?\n";
$dir = WP_CONTENT_DIR . '/uploads/elementor/css';
if (is_dir($dir)) {
    $found = 0;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $f) {
        if (!$f->isFile()) continue;
        $c = file_get_contents($f->getPathname());
        if (strpos($c, 'Nian') !== false) {
            echo "     " . $f->getPathname() . "\n";
            $found++;
        }
    }
    echo "   total elementor/css files containing Nian: $found\n";
} else {
    echo "   (uploads/elementor/css 目錄不存在)\n";
}

echo "\n8. Breeze / WP page cache files?\n";
foreach ([WP_CONTENT_DIR . '/cache', WP_CONTENT_DIR . '/breeze-cache', WP_CONTENT_DIR . '/wp-rocket-config'] as $cd) {
    if (is_dir($cd)) {
        echo "   存在: $cd\n";
    }
}
