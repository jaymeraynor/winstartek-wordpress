<?php
/**
 * C. 掃所有 _elementor_data,統計 widgetType 使用次數,
 *    並依 prefix 歸屬到對應 plugin。
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$prefix = $wpdb->prefix;

// widgetType prefix → plugin slug 的對照
// 來源:翻過 plugin source 跟 Elementor doc,以及實際 element pack / addon-elements / ele-custom-skin 的命名慣例
$PLUGIN_OF = function($wt) {
    if (preg_match('/^(ep-|ep_|element-pack-|element_pack_|bdt-|bdthemes-)/i', $wt)) return 'bdthemes-element-pack';
    // Essential Addons uses BOTH eael- and eae- prefix (legacy)
    if (preg_match('/^(eael-|eae-|exclusive-)/i', $wt))                              return 'addon-elements (Essential)';
    if (preg_match('/^(woolentor-|wlt-)/i', $wt))                                    return 'addon-elements (WooLentor)';
    if (preg_match('/^(wts-|wpaddon-)/i', $wt))                                      return 'addon-elements';
    if (preg_match('/^(ecs-|ele-cs-)/i', $wt))                                       return 'ele-custom-skin';
    if (preg_match('/^(ecsp-|ele-csp-|ele-skin-)/i', $wt))                          return 'ele-custom-skin-pro';
    if (preg_match('/^(astra-|ast-)/i', $wt))                                        return 'astra-addon / astra-widgets';
    // Elementor Pro: Theme Builder + dynamic widgets + Forms
    if (preg_match('/^(form|forms|nav-menu|portfolio|posts|template|woocommerce|popup|theme-|archive-|search-form|breadcrumbs|hotspot|reviews|share-buttons|countdown|flip-box|price-list|price-table|call-to-action|media-carousel|testimonial-carousel|login|sitemap|table-of-contents|paypal|stripe|gallery|lottie|slides|nav|menu-anchor|video-playlist|fb-comments|fb-page|fb-button|fb-embed|hello-world|hello\+|nested-tabs|nested-accordion|nested-carousel|loop-grid|loop-carousel|taxonomy|post-info|post-comments|post-navigation|post-title|post-excerpt|post-content|site-logo|site-title|page-title|author-box)$/i', $wt)) return 'elementor-pro';
    // Elementor core
    if (preg_match('/^(heading|text-editor|image|video|button|divider|spacer|google_maps|html|menu-anchor|read-more|alert|toggle|accordion|tabs|image-box|icon-box|icon|image-carousel|image-gallery|icon-list|counter|progress|testimonial|tabs|tab|sound-cloud|shortcode|content-area|section|container|column|social-icons|rating|star-rating|tabs|text-path)$/i', $wt)) return 'elementor (core)';
    return 'unknown';
};

// fetch all _elementor_data
$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key='_elementor_data' AND meta_value LIKE '[%'");
echo "scanning " . count($rows) . " _elementor_data records...\n\n";

$widget_count = [];   // widgetType => count
$widget_pages = [];   // widgetType => set of post_ids
$walk = function($node) use (&$walk, &$widget_count, &$widget_pages, &$current_post) {
    if (!is_array($node)) return;
    if (isset($node['widgetType'])) {
        $w = $node['widgetType'];
        $widget_count[$w] = ($widget_count[$w] ?? 0) + 1;
        $widget_pages[$w][$current_post] = true;
    } elseif (isset($node['elType']) && in_array($node['elType'], ['section','column','container'])) {
        // structural — skip but recurse
    }
    if (!empty($node['elements']) && is_array($node['elements'])) {
        foreach ($node['elements'] as $c) $walk($c);
    }
};

foreach ($rows as $r) {
    $current_post = $r->post_id;
    $data = json_decode($r->meta_value, true);
    if (!is_array($data)) continue;
    foreach ($data as $section) $walk($section);
}

// roll up by plugin
$by_plugin = [];
foreach ($widget_count as $w => $c) {
    $p = $PLUGIN_OF($w);
    $by_plugin[$p]['widgets'][$w] = ['count'=>$c, 'pages'=>count($widget_pages[$w])];
    $by_plugin[$p]['total'] = ($by_plugin[$p]['total'] ?? 0) + $c;
    $by_plugin[$p]['pages_union'] = ($by_plugin[$p]['pages_union'] ?? []) + $widget_pages[$w];
}

uasort($by_plugin, fn($a,$b) => $b['total'] <=> $a['total']);

echo "==== USAGE BY PLUGIN ====\n";
foreach ($by_plugin as $plug => $info) {
    $page_n = count($info['pages_union']);
    echo "\n[$plug]  total uses={$info['total']}  on $page_n pages\n";
    uasort($info['widgets'], fn($a,$b) => $b['count'] <=> $a['count']);
    foreach ($info['widgets'] as $w => $stat) {
        printf("  %-50s  %4d uses  %3d pages\n", $w, $stat['count'], $stat['pages']);
    }
}

echo "\n\n==== SUMMARY (plugin keep/remove decision aid) ====\n";
foreach ($by_plugin as $plug => $info) {
    if ($plug === 'elementor (core)' || $plug === 'unknown') continue;
    $page_n = count($info['pages_union']);
    $verdict = $info['total'] === 0 ? 'CAN REMOVE'
             : ($page_n <= 2 ? 'LOW USE — replaceable' : 'IN USE');
    printf("  %-40s  uses=%-5d pages=%-3d  %s\n", $plug, $info['total'], $page_n, $verdict);
}

// ele-custom-skin uses skin mechanism on Posts/Loop widgets, not its own widgetType.
// Detect via "_skin" or "ecs_post_template_id" / "ecs_skin" / "skin_*" with template_id.
echo "\n\n==== ele-custom-skin SKIN USAGE (different mechanism) ====\n";
$ecs = $wpdb->get_results("
    SELECT post_id FROM {$prefix}postmeta
    WHERE meta_key='_elementor_data'
      AND (meta_value LIKE '%\"_skin\":\"ele-custom-skin%'
        OR meta_value LIKE '%\"_skin\":\"ecs%'
        OR meta_value LIKE '%ecs_post_template_id%'
        OR meta_value LIKE '%ele_custom_skin_template%')
");
echo "  pages using ele-custom-skin skins: " . count($ecs) . "\n";
foreach (array_slice($ecs, 0, 20) as $r) {
    $info  = $wpdb->get_row($wpdb->prepare("SELECT post_title,post_type,post_status FROM {$prefix}posts WHERE ID=%d", $r->post_id));
    echo "    ID={$r->post_id} type={$info->post_type} status={$info->post_status} title={$info->post_title}\n";
}

// Also count CPTs registered by ele-custom-skin (named "ele-custom-skin")
$ecs_cpt = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}posts WHERE post_type='ele-custom-skin'");
echo "  posts of CPT 'ele-custom-skin' (skin templates themselves): $ecs_cpt\n";

// Astra addon: check for Astra Custom Layouts (theme builder feature, post type 'astra-advanced-hook')
echo "\n==== ASTRA-ADDON CUSTOM LAYOUTS (theme features, not widgets) ====\n";
$astra_hooks = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}posts WHERE post_type='astra-advanced-hook' AND post_status='publish'");
echo "  astra-advanced-hook (Custom Layouts published): $astra_hooks\n";
$astra_settings = $wpdb->get_var("SELECT LENGTH(option_value) FROM {$prefix}options WHERE option_name='astra-settings'");
echo "  astra-settings option size: " . number_format(($astra_settings ?: 0)/1024, 1) . " KB\n";
