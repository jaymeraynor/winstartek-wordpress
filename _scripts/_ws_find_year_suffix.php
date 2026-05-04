<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;

// Find all WPML translations grouped by trid where source page has 28年
$rows = $wpdb->get_results("
SELECT t.element_id AS post_id, t.trid, t.language_code
FROM wp_icl_translations t
WHERE t.element_type='post_page'
AND t.trid IN (
  SELECT trid FROM wp_icl_translations
  WHERE element_type='post_page'
  AND element_id IN (SELECT ID FROM wp_posts WHERE post_status='publish' AND (post_title LIKE '%關於%' OR post_title LIKE '%About%' OR post_name LIKE '%about%'))
)
ORDER BY t.trid, t.language_code
", ARRAY_A);

echo "candidate posts:\n";
foreach($rows as $r){
  $title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM wp_posts WHERE ID=%d", $r['post_id']));
  echo "  trid={$r['trid']} lang={$r['language_code']} id={$r['post_id']} title=$title\n";
}

// For each post, look in _elementor_data for 'Nian' or '28年' or surrounding suffix text
echo "\nscanning _elementor_data for year/Nian/年...\n";
foreach($rows as $r){
  $data = get_post_meta($r['post_id'], '_elementor_data', true);
  if(!$data) continue;
  if (preg_match_all('/"suffix"\s*:\s*"([^"]{0,40})"/u', $data, $m)) {
    foreach($m[1] as $s){
      if (trim($s)==='') continue;
      echo "  id={$r['post_id']} lang={$r['language_code']} suffix=\"$s\"\n";
    }
  }
}
