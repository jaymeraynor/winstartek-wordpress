<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// Categories
echo "=== categories (term + count) ===\n";
$cats = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.count, tt.parent
    FROM {$prefix}terms t JOIN {$prefix}term_taxonomy tt ON t.term_id=tt.term_id
    WHERE tt.taxonomy='category'
    ORDER BY t.term_id
");
foreach ($cats as $c) {
    $lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$prefix}icl_translations WHERE element_id=%d AND element_type='tax_category' LIMIT 1", $c->term_taxonomy_id));
    echo "  tt={$c->term_taxonomy_id} term={$c->term_id} [$lang] name={$c->name} slug={$c->slug} count={$c->count} parent={$c->parent}\n";
}

// Find pages with title 'news' / 'blog' / '新聞' / '消息' / '部落格' / '文章'
echo "\n=== relevant pages ===\n";
$rows = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_type, p.post_status, t.language_code
    FROM {$prefix}posts p
    LEFT JOIN {$prefix}icl_translations t ON t.element_id=p.ID AND t.element_type LIKE 'post_%'
    WHERE p.post_status='publish'
      AND (p.post_title LIKE '%news%' OR p.post_title LIKE '%blog%'
           OR p.post_title LIKE '%消息%' OR p.post_title LIKE '%新聞%'
           OR p.post_title LIKE '%專欄%' OR p.post_title LIKE '%文章%'
           OR p.post_name LIKE '%news%' OR p.post_name LIKE '%blog%')
    ORDER BY p.ID
");
foreach ($rows as $r) echo "  ID={$r->ID} type={$r->post_type} [{$r->language_code}] {$r->post_title}\n";

// Find Posts widget instances on pages titled news/blog and inspect their query settings
echo "\n=== Posts widget query settings on news/blog pages ===\n";
$candidate_ids = array_column($rows, 'ID');
foreach ($candidate_ids as $pid) {
    $data = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='_elementor_data' LIMIT 1", $pid));
    if (!$data) continue;
    $arr = json_decode($data, true);
    if (!is_array($arr)) continue;
    $found = [];
    $walk = function($node) use (&$walk, &$found) {
        if (!is_array($node)) return;
        if (isset($node['widgetType']) && in_array($node['widgetType'], ['posts','loop-carousel','loop-grid'])) {
            $s = $node['settings'] ?? [];
            $picked = [];
            foreach (['_skin','posts_post_type','posts_query_type','posts_include','posts_include_term_ids','posts_include_authors','posts_exclude','posts_exclude_term_ids','posts_per_page','orderby','order','wp_query'] as $k) {
                if (isset($s[$k])) $picked[$k] = $s[$k];
            }
            // also check ANY 'cat' or 'category' keys
            foreach ($s as $k => $v) {
                if (preg_match('/^(cat|category|tag|term|taxonomy|include|exclude)/i', $k) && !isset($picked[$k])) {
                    $picked[$k] = $v;
                }
            }
            $found[] = ['type' => $node['widgetType'], 'settings' => $picked];
        }
        if (!empty($node['elements'])) foreach ($node['elements'] as $c) $walk($c);
    };
    foreach ($arr as $sec) $walk($sec);
    if ($found) {
        $title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$prefix}posts WHERE ID=%d", $pid));
        echo "\n  page=$pid [$title]:\n";
        foreach ($found as $w) {
            echo "    widget={$w['type']}:\n";
            foreach ($w['settings'] as $k => $v) {
                $vstr = is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
                if (strlen($vstr) > 200) $vstr = substr($vstr,0,200).'...';
                echo "      $k = $vstr\n";
            }
        }
    }
}
