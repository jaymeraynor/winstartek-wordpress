<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

$ids = [21401,21404,24356,24357,24413,24414,24470,24471,24527,24528,24698,24699,24584,24585,24242,24243,24299,24300,24756,24813];
foreach ($ids as $id) {
  $meta = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='_elementor_data' LIMIT 1", $id));
  $info = $wpdb->get_row($wpdb->prepare("SELECT post_title FROM {$prefix}posts WHERE ID=%d", $id));
  $lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$prefix}icl_translations WHERE element_id=%d LIMIT 1", $id));
  preg_match_all('/"suffix"\s*:\s*"([^"]{0,60})"/u', $meta, $m);
  $non_plus = array_values(array_filter(array_unique($m[1]), fn($s) => trim($s) !== '' && $s !== '+'));
  echo "ID=$id [$lang] {$info->post_title}: suffix(es) = " . json_encode($non_plus, JSON_UNESCAPED_UNICODE) . "\n";
}

// confirm none still contain Nian/苗字
$bad = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}postmeta WHERE meta_key='_elementor_data' AND (meta_value LIKE '%Nian%' OR meta_value LIKE '%\"suffix\":\"苗字\"%')");
echo "\nRemaining 'Nian' or '苗字' in any _elementor_data: $bad\n";
