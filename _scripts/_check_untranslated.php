<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

$LANGS = ['ar','de','es','hi','id','it','ja','ko','pt-br','th','vi','en'];

echo "=== 各語言頁面 _elementor_data ZH 殘留統計 ===\n";
$worst_per_lang = [];
foreach ($LANGS as $lang) {
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT t.element_id AS pid, p.post_title, pm.meta_value
        FROM {$prefix}icl_translations t
        JOIN {$prefix}posts p ON p.ID=t.element_id
        JOIN {$prefix}postmeta pm ON pm.post_id=t.element_id AND pm.meta_key='_elementor_data'
        WHERE t.element_type='post_page' AND t.language_code=%s
          AND p.post_status='publish'
    ", $lang));
    $count = 0; $worst_pages = [];
    foreach ($rows as $r) {
        $bytes = strlen($r->meta_value);
        $zh_count = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $r->meta_value);
        $non_url_text_bytes = $bytes; // approximate
        $pct = $zh_count > 0 && $bytes > 0 ? ($zh_count*3 / $bytes * 100) : 0;
        if ($pct > 1) {  // >1% chinese chars = likely partial untranslated
            $worst_pages[] = ['pid'=>$r->pid, 'title'=>$r->post_title, 'pct'=>round($pct,2), 'zh_chars'=>$zh_count];
            $count++;
        }
    }
    usort($worst_pages, fn($a,$b)=>$b['pct']<=>$a['pct']);
    echo "[$lang] " . count($rows) . " pages, $count have >1% ZH residue\n";
    foreach (array_slice($worst_pages, 0, 5) as $p) {
        echo "  pid={$p['pid']} {$p['pct']}% zh_chars={$p['zh_chars']} {$p['title']}\n";
    }
    $worst_per_lang[$lang] = $count;
}

echo "\n=== summary ===\n";
foreach ($worst_per_lang as $l => $c) echo "  $l: $c pages with ZH residue\n";

// 也檢查 icl_strings 缺翻譯的字串(WPML String Translation 層)
echo "\n=== icl_strings 缺翻譯統計 ===\n";
$lang_counts = [];
$strings = $wpdb->get_results("SELECT id, value FROM {$prefix}icl_strings");
$total = count($strings);
echo "  total ZH source strings: $total\n";
foreach ($LANGS as $lang) {
    $translated = (int)$wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT string_id) FROM {$prefix}icl_string_translations
        WHERE language=%s AND status=10 AND value IS NOT NULL AND value != ''
    ", $lang));
    echo "  $lang: $translated / $total translated";
    if ($translated < $total) echo " (MISSING " . ($total - $translated) . ")";
    echo "\n";
}
