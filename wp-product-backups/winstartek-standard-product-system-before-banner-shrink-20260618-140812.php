<?php
/**
 * Plugin Name: Winstartek Standard Product System
 * Description: Shared data-driven product template for Winstartek product CPT pages.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

const WS_SPT_META_KEY = '_ws_standard_product_data';

function ws_spt_decode_data($raw) {
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ws_spt_get_data($post_id = null) {
    $post_id = $post_id ? absint($post_id) : get_queried_object_id();
    if (!$post_id || get_post_type($post_id) !== 'product') {
        return [];
    }

    return ws_spt_decode_data((string) get_post_meta($post_id, WS_SPT_META_KEY, true));
}

function ws_spt_has_data($post_id = null) {
    return (bool) ws_spt_get_data($post_id);
}

function ws_spt_is_elementor_editor_request() {
    if (isset($_GET['elementor-preview']) || isset($_GET['elementor_library'])) {
        return true;
    }
    if (isset($_GET['action']) && is_string($_GET['action']) && strpos($_GET['action'], 'elementor') !== false) {
        return true;
    }
    return false;
}

function ws_spt_text($value, $fallback = '') {
    return is_scalar($value) ? (string) $value : $fallback;
}

function ws_spt_image_url($image) {
    if (is_numeric($image)) {
        $url = wp_get_attachment_image_url((int) $image, 'full');
        return $url ?: '';
    }
    if (is_array($image)) {
        if (!empty($image['id'])) {
            $url = wp_get_attachment_image_url((int) $image['id'], 'full');
            if ($url) {
                return $url;
            }
        }
        return ws_spt_text($image['url'] ?? '');
    }

    return ws_spt_text($image);
}

function ws_spt_image_alt($image, $fallback = '') {
    if (is_array($image) && isset($image['alt'])) {
        return ws_spt_text($image['alt'], $fallback);
    }
    if (is_array($image) && !empty($image['id'])) {
        $alt = get_post_meta((int) $image['id'], '_wp_attachment_image_alt', true);
        return $alt ? (string) $alt : $fallback;
    }

    return $fallback;
}

function ws_spt_image_caption($image) {
    if (is_array($image) && isset($image['caption'])) {
        return ws_spt_text($image['caption']);
    }

    return '';
}

function ws_spt_normalize_public_path($path) {
    $path = ws_spt_text($path);
    $path = preg_replace('#^https?://[^/]+#i', '', $path);
    $path = trim($path, '/');
    $path = preg_replace('#/+#', '/', $path);
    return $path;
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'ws-standard-product-data',
        'Winstar 標準產品資料',
        'ws_spt_render_metabox',
        'product',
        'normal',
        'high'
    );
});

function ws_spt_render_metabox($post) {
    wp_nonce_field('ws_spt_save_product_data', 'ws_spt_nonce');
    $raw = (string) get_post_meta($post->ID, WS_SPT_META_KEY, true);
    $public_path = (string) get_post_meta($post->ID, '_ws_product_public_path', true);
    ?>
    <p>貼上標準產品 JSON。儲存後會自動套用產品頁版型、SEO meta、FAQ schema、輪播箭頭與 legacy URL。</p>
    <p>
      <label for="ws_spt_public_path"><strong>公開網址路徑</strong></label><br>
      <input id="ws_spt_public_path" name="ws_spt_public_path" type="text" value="<?php echo esc_attr($public_path); ?>" style="width:100%" placeholder="products/indoor-and-commercial-lighting/panel-light">
    </p>
    <p>
      <label for="ws_spt_data"><strong>產品 JSON</strong></label><br>
      <textarea id="ws_spt_data" name="ws_spt_data" rows="24" style="width:100%;font-family:Consolas,Monaco,monospace"><?php echo esc_textarea($raw); ?></textarea>
    </p>
    <?php
}

add_action('save_post_product', function ($post_id, $post) {
    if (!isset($_POST['ws_spt_nonce']) || !wp_verify_nonce((string) $_POST['ws_spt_nonce'], 'ws_spt_save_product_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw = isset($_POST['ws_spt_data']) ? wp_unslash((string) $_POST['ws_spt_data']) : '';
    $raw = trim($raw);
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            update_post_meta($post_id, WS_SPT_META_KEY, wp_json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            ws_spt_apply_standard_product_meta($post_id, $decoded);
        }
    }

    $public_path = isset($_POST['ws_spt_public_path']) ? ws_spt_normalize_public_path(wp_unslash((string) $_POST['ws_spt_public_path'])) : '';
    if ($public_path !== '') {
        ws_spt_set_public_path($post_id, $public_path);
    }
}, 20, 2);

function ws_spt_set_public_path($post_id, $public_path) {
    $public_path = ws_spt_normalize_public_path($public_path);
    if ($public_path === '') {
        return;
    }

    update_post_meta($post_id, '_ws_product_public_path', $public_path);

    $map = get_option('ws_product_public_path_map', []);
    if (!is_array($map)) {
        $map = [];
    }
    $map[$public_path] = (int) $post_id;
    update_option('ws_product_public_path_map', $map, false);
}

function ws_spt_apply_standard_product_meta($post_id, array $data) {
    $layout_meta = [
        '_wp_page_template' => 'default',
        '_elementor_edit_mode' => '',
        '_elementor_template_type' => '',
        '_ws_product_template' => '1',
        'site-post-title' => 'disabled',
        'site-sidebar-layout' => 'no-sidebar',
        'site-content-layout' => 'page-builder',
        'ast-featured-img' => 'disabled',
        'ast-site-content-layout' => 'full-width-container',
        'site-content-style' => 'unboxed',
        'site-sidebar-style' => 'unboxed',
    ];
    foreach ($layout_meta as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    $seo = isset($data['seo']) && is_array($data['seo']) ? $data['seo'] : [];
    $focus = ws_spt_text($data['focusKeyword'] ?? ($seo['focusKeyword'] ?? ''));
    $title = ws_spt_text($seo['title'] ?? ($data['seoTitle'] ?? ''));
    $description = ws_spt_text($seo['description'] ?? ($data['metaDescription'] ?? ''));

    if ($focus !== '') {
        update_post_meta($post_id, 'rank_math_focus_keyword', $focus);
    }
    if ($title !== '') {
        update_post_meta($post_id, 'rank_math_title', $title);
    }
    if ($description !== '') {
        update_post_meta($post_id, 'rank_math_description', $description);
    }
    update_post_meta($post_id, 'rank_math_schema_Article', 'off');

    $public_path = ws_spt_text($data['publicPath'] ?? '');
    if ($public_path !== '') {
        ws_spt_set_public_path($post_id, $public_path);
    }

    $featured_id = absint($data['featuredImageId'] ?? 0);
    if (!$featured_id && !empty($data['images'][0]['id'])) {
        $featured_id = absint($data['images'][0]['id']);
    }
    if ($featured_id) {
        set_post_thumbnail($post_id, $featured_id);
    }

    $images = isset($data['images']) && is_array($data['images']) ? $data['images'] : [];
    foreach ($images as $image) {
        if (is_array($image) && !empty($image['id']) && isset($image['alt'])) {
            update_post_meta((int) $image['id'], '_wp_attachment_image_alt', ws_spt_text($image['alt']));
        }
    }
}

add_shortcode('ws_standard_product', function () {
    return ws_spt_render_product(get_queried_object_id());
});

add_filter('the_content', function ($content) {
    if (!is_singular('product') || !ws_spt_has_data() || get_post_meta(get_queried_object_id(), '_ws_elementor_product_page', true) === '1') {
        return $content;
    }

    return ws_spt_render_product(get_queried_object_id());
}, 8);

add_filter('the_content', function ($content) {
    if (is_singular('product') && ws_spt_has_data() && get_post_meta(get_queried_object_id(), '_ws_elementor_product_page', true) !== '1') {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'wptexturize');
        remove_filter('the_content', 'shortcode_unautop');
    }
    return $content;
}, 0);

function ws_spt_render_product($post_id) {
    $data = ws_spt_get_data($post_id);
    if (!$data) {
        return '';
    }

    $title = ws_spt_text($data['title'] ?? get_the_title($post_id));
    $subtitle = ws_spt_text($data['subtitle'] ?? '');
    $images = isset($data['images']) && is_array($data['images']) ? $data['images'] : [];
    $specs = isset($data['specs']) && is_array($data['specs']) ? $data['specs'] : [];
    $summary = ws_spt_text($data['summary'] ?? '');
    $sections = isset($data['sections']) && is_array($data['sections']) ? $data['sections'] : [];
    $faq = isset($data['faq']) && is_array($data['faq']) ? $data['faq'] : [];

    ob_start();
    ?>
    <div class="ws-standard-product">
      <h1>
        <?php echo esc_html($title); ?>
        <?php if ($subtitle !== '') : ?>
          <span class="ws-product-subtitle"><?php echo esc_html($subtitle); ?></span>
        <?php endif; ?>
      </h1>

      <section class="ws-spt-hero" aria-labelledby="ws-spt-overview">
        <div class="ws-spt-media">
          <?php ws_spt_render_carousel($images, $title); ?>
        </div>
        <div class="ws-spt-spec">
          <h2 id="ws-spt-overview"><?php echo esc_html(ws_spt_text($data['specHeading'] ?? '規格總覽')); ?></h2>
          <?php ws_spt_render_specs($specs); ?>
        </div>
      </section>

      <?php if ($summary !== '') : ?>
        <p class="ws-spt-summary"><strong>一句話總結：</strong><?php echo esc_html($summary); ?></p>
      <?php endif; ?>

      <?php foreach ($sections as $section) : ?>
        <?php ws_spt_render_section(is_array($section) ? $section : []); ?>
      <?php endforeach; ?>

      <?php if ($faq) : ?>
        <section class="ws-spt-faq">
          <h2><?php echo esc_html(ws_spt_text($data['faqHeading'] ?? '常見問題')); ?></h2>
          <?php foreach ($faq as $item) : ?>
            <?php
            $q = ws_spt_text(is_array($item) ? ($item['q'] ?? '') : '');
            $a = ws_spt_text(is_array($item) ? ($item['a'] ?? '') : '');
            if ($q === '' || $a === '') {
                continue;
            }
            ?>
            <details><summary><?php echo esc_html($q); ?></summary><div><?php echo wp_kses_post(wpautop($a)); ?></div></details>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php ws_spt_render_cta($data['cta'] ?? []); ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function ws_spt_render_carousel(array $images, $fallback_alt) {
    if (!$images) {
        echo '<div class="ws-spt-placeholder"><strong>產品圖片待補</strong><p>輪播素材之後補上，收到產品圖後會接成標準輪播。</p></div>';
        return;
    }
    ?>
    <div class="ws-spt-carousel" data-ws-panel-carousel>
      <?php foreach ($images as $index => $image) : ?>
        <?php $url = ws_spt_image_url($image); if ($url === '') { continue; } ?>
        <figure class="ws-spt-slide <?php echo $index === 0 ? 'is-active' : ''; ?>">
          <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr(ws_spt_image_alt($image, $fallback_alt)); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
          <?php $caption = ws_spt_image_caption($image); if ($caption !== '') : ?>
            <figcaption><?php echo esc_html($caption); ?></figcaption>
          <?php endif; ?>
        </figure>
      <?php endforeach; ?>
      <?php if (count($images) > 1) : ?>
        <button class="ws-panel-carousel-btn ws-panel-carousel-prev" type="button" aria-label="上一張"></button>
        <button class="ws-panel-carousel-btn ws-panel-carousel-next" type="button" aria-label="下一張"></button>
      <?php endif; ?>
    </div>
    <?php
}

function ws_spt_render_specs(array $specs) {
    echo '<div class="ws-spt-table-wrap"><table class="ws-spt-spec-table"><tbody>';
    foreach ($specs as $row) {
        if (!is_array($row) || count($row) < 2) {
            continue;
        }
        echo '<tr><td>' . esc_html(ws_spt_text($row[0])) . '</td><td>' . wp_kses_post(ws_spt_text($row[1])) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function ws_spt_render_section(array $section) {
    $heading = ws_spt_text($section['heading'] ?? '');
    $type = ws_spt_text($section['type'] ?? 'cards');
    if ($heading === '') {
        return;
    }
    echo '<section class="ws-spt-section ws-spt-section-' . esc_attr($type) . '"><h2>' . esc_html($heading) . '</h2>';

    if ($type === 'table') {
        ws_spt_render_comparison_table($section);
    } elseif ($type === 'bullets') {
        $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];
        echo '<ul class="ws-spt-bullets">';
        foreach ($items as $item) {
            echo '<li>' . wp_kses_post(ws_spt_text($item)) . '</li>';
        }
        echo '</ul>';
    } else {
        ws_spt_render_cards($section);
    }

    echo '</section>';
}

function ws_spt_render_cards(array $section) {
    $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];
    echo '<div class="ws-spt-card-grid">';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $image = $item['image'] ?? null;
        $url = $image ? ws_spt_image_url($image) : '';
        echo '<article class="ws-spt-card">';
        if ($url !== '') {
            echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(ws_spt_image_alt($image, ws_spt_text($item['title'] ?? ''))) . '" loading="lazy">';
        }
        echo '<div>';
        echo '<h3>' . esc_html(ws_spt_text($item['title'] ?? '')) . '</h3>';
        echo '<p>' . wp_kses_post(ws_spt_text($item['text'] ?? '')) . '</p>';
        echo '</div></article>';
    }
    echo '</div>';
}

function ws_spt_render_comparison_table(array $section) {
    $headers = isset($section['headers']) && is_array($section['headers']) ? $section['headers'] : [];
    $rows = isset($section['rows']) && is_array($section['rows']) ? $section['rows'] : [];
    echo '<div class="ws-spt-table-wrap"><table class="ws-spt-compare-table">';
    if ($headers) {
        echo '<thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . esc_html(ws_spt_text($header)) . '</th>';
        }
        echo '</tr></thead>';
    }
    echo '<tbody>';
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . wp_kses_post(ws_spt_text($cell)) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function ws_spt_render_cta($cta) {
    if (!is_array($cta)) {
        return;
    }
    $title = ws_spt_text($cta['title'] ?? '');
    $text = ws_spt_text($cta['text'] ?? '');
    $url = ws_spt_text($cta['url'] ?? '');
    $button = ws_spt_text($cta['button'] ?? '立即詢問');
    if ($title === '' && $text === '') {
        return;
    }
    echo '<section class="ws-spt-cta"><div><h3>' . esc_html($title) . '</h3><p>' . esc_html($text) . '</p></div>';
    if ($url !== '') {
        echo '<a class="btn" href="' . esc_url($url) . '">' . esc_html($button) . '</a>';
    }
    echo '</section>';
}

add_action('wp_head', function () {
    if (!is_singular('product') || !ws_spt_has_data()) {
        return;
    }

    try {
        ws_spt_print_css();
        ws_spt_print_schema(get_queried_object_id());
    } catch (\Throwable $e) {
        error_log('WS standard product wp_head error for post ' . get_queried_object_id() . ': ' . $e->getMessage());
    }
}, 80);

function ws_spt_print_css() {
    ?>
<style id="ws-standard-product-css">
body.single-product #content.site-content{padding-top:0!important;background:#f2f2f2!important}
.ws-standard-product{max-width:1180px;margin:0 auto;padding:0 18px 56px;color:#111;line-height:1.72}
.ws-standard-product::before{content:"";display:block;width:100vw;min-height:150px;margin-top:-76px;margin-bottom:54px;margin-left:calc(50% - 50vw);background:linear-gradient(90deg,rgba(30,32,34,.82),rgba(76,78,80,.68)),repeating-linear-gradient(135deg,rgba(255,255,255,.16) 0 1px,transparent 1px 32px),repeating-linear-gradient(45deg,rgba(255,255,255,.08) 0 1px,transparent 1px 52px),#5f6264;background-size:auto,32px 32px,52px 52px,auto}
.ws-standard-product *{box-sizing:border-box}
.ws-standard-product h1,.ws-standard-product h2,.ws-standard-product h3{font-family:inherit;letter-spacing:0;color:#111}
.ws-standard-product h1{font-size:42px;line-height:1.18;margin:0 0 26px;text-align:left}
.ws-standard-product .ws-product-subtitle{display:block;margin-top:8px;font-size:28px;line-height:1.32;font-weight:600;color:#374047}
.ws-standard-product h2{font-size:26px;line-height:1.35;margin:34px 0 18px}
.ws-standard-product h3{font-size:20px;line-height:1.35;margin:0 0 10px}
.ws-spt-hero{display:grid;grid-template-columns:minmax(320px,.95fr) minmax(440px,1.35fr);gap:32px;align-items:start;margin-bottom:28px}
.ws-spt-carousel,.ws-spt-placeholder{position:relative;background:#fff;border-radius:8px;box-shadow:0 8px 22px rgba(0,0,0,.06);overflow:hidden;min-height:540px;display:flex;align-items:center;justify-content:center;margin-top:46px}
.ws-spt-placeholder{border:1px dashed #cbd5dc;text-align:center;display:grid;place-items:center;padding:28px}
.ws-spt-placeholder strong{display:block;color:#ed3f42;font-size:24px;margin-bottom:8px}
.ws-spt-slide{display:none;width:100%;height:100%;margin:0;padding:24px}
.ws-spt-slide.is-active{display:flex;flex-direction:column;align-items:center;justify-content:center}
.ws-spt-slide img{width:100%;height:430px;object-fit:contain;display:block}
.ws-spt-slide figcaption{margin-top:12px;font-size:14px;color:#707c84;text-align:center}
.ws-panel-carousel-btn{position:absolute;top:50%;transform:translateY(-50%);width:42px!important;height:42px!important;padding:0!important;border:0!important;border-radius:999px!important;background:rgba(19,21,22,.68)!important;color:#fff;display:flex!important;align-items:center!important;justify-content:center!important;font-size:0!important;line-height:1!important;cursor:pointer;z-index:2}
.ws-panel-carousel-btn:hover{background:#ed3f42!important}
.ws-panel-carousel-btn::before{content:"";display:block;width:11px;height:11px;border-top:3px solid #fff;border-right:3px solid #fff;box-sizing:border-box}
.ws-panel-carousel-prev{left:12px}
.ws-panel-carousel-next{right:12px}
.ws-panel-carousel-prev::before{transform:rotate(-135deg);margin-left:3px}
.ws-panel-carousel-next::before{transform:rotate(45deg);margin-right:3px}
.ws-spt-spec h2,.ws-spt-section h2,.ws-spt-faq h2{border-bottom:3px solid #ed3f42;padding-bottom:10px;margin-top:0}
.ws-spt-table-wrap{overflow-x:auto;border:1px solid #dbe1e6;border-radius:8px;background:#fff}
.ws-spt-spec-table,.ws-spt-compare-table{width:100%;border-collapse:collapse;font-size:16px}
.ws-spt-spec-table td,.ws-spt-compare-table td,.ws-spt-compare-table th{padding:14px 16px;border-bottom:1px solid #dbe1e6;vertical-align:middle}
.ws-spt-spec-table tr:nth-child(even) td,.ws-spt-compare-table tbody tr:nth-child(even) td{background:#f3f6f8}
.ws-spt-spec-table td:first-child{width:34%;font-weight:700;color:#111;background:linear-gradient(90deg,#fff,#f5f7f8)}
.ws-spt-compare-table th{background:#ed3f42;color:#fff;font-weight:700;text-align:center}
.ws-spt-compare-table td:first-child{font-weight:700;color:#111;white-space:nowrap}
.ws-spt-summary{border-left:4px solid #ed3f42;background:#fff4f4;border-radius:8px;padding:16px 18px;margin:0 0 34px}
.ws-spt-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-bottom:18px}
.ws-spt-card{background:#fff;border:1px solid #e3e7ea;border-radius:8px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.04)}
.ws-spt-card img{width:100%;height:230px;object-fit:contain;background:#fff;display:block;border-bottom:1px solid #e3e7ea}
.ws-spt-card div{padding:20px}
.ws-spt-card p{margin:0;color:#4b555b}
.ws-spt-bullets{background:#fff;border:1px solid #e3e7ea;border-radius:8px;padding:20px 24px 20px 44px;line-height:1.95}
.ws-spt-faq details{border:1px solid #e1e6e8;border-radius:8px;background:#fff;margin:12px 0;overflow:hidden}
.ws-spt-faq summary{cursor:pointer;list-style:none;padding:16px 52px 16px 18px;font-weight:700;position:relative}
.ws-spt-faq summary::-webkit-details-marker{display:none}
.ws-spt-faq summary::after{content:"";position:absolute;right:20px;top:50%;width:10px;height:10px;border-right:2px solid #ed3f42;border-bottom:2px solid #ed3f42;transform:translateY(-60%) rotate(45deg);transition:.2s}
.ws-spt-faq details[open] summary::after{transform:translateY(-30%) rotate(225deg)}
.ws-spt-faq details div{padding:0 18px 18px;color:#4b555b}
.ws-spt-cta{display:flex;align-items:center;justify-content:space-between;gap:24px;background:#ed3f42;color:#fff;border-radius:8px;padding:26px 30px;margin-top:34px}
.ws-spt-cta h3,.ws-spt-cta p{color:#fff;margin:0}
.ws-spt-cta .btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 20px;border:1px solid #fff;color:#fff;text-decoration:none;font-weight:700}
@media (max-width:921px){.ws-standard-product::before{min-height:128px;margin-top:-74px;margin-bottom:46px}.ws-standard-product h1{font-size:34px}.ws-standard-product .ws-product-subtitle{font-size:22px}.ws-spt-hero,.ws-spt-card-grid{grid-template-columns:1fr}.ws-spt-carousel,.ws-spt-placeholder{min-height:420px;margin-top:0}.ws-spt-slide img{height:320px}.ws-spt-cta{display:block}.ws-spt-cta .btn{margin-top:16px}}
</style>
    <?php
}

add_action('wp_footer', function () {
    if (!is_singular('product') || !ws_spt_has_data()) {
        return;
    }
    ?>
<script id="ws-standard-product-carousel-js">
(function(){
  document.querySelectorAll('[data-ws-panel-carousel]').forEach(function(root){
    var slides=[].slice.call(root.querySelectorAll('.ws-spt-slide'));
    if(!slides.length) return;
    var index=Math.max(0,slides.findIndex(function(slide){return slide.classList.contains('is-active');}));
    function show(next){slides[index].classList.remove('is-active');index=(next+slides.length)%slides.length;slides[index].classList.add('is-active');}
    var prev=root.querySelector('.ws-panel-carousel-prev');
    var next=root.querySelector('.ws-panel-carousel-next');
    if(prev) prev.addEventListener('click',function(){show(index-1);});
    if(next) next.addEventListener('click',function(){show(index+1);});
  });
})();
</script>
    <?php
}, 1002);

function ws_spt_print_schema($post_id) {
    $data = ws_spt_get_data($post_id);
    if (!$data) {
        return;
    }

    $url = get_permalink($post_id);
    $images = [];
    foreach ((array) ($data['images'] ?? []) as $image) {
        $image_url = ws_spt_image_url($image);
        if ($image_url !== '') {
            $images[] = $image_url;
        }
    }

    $spec_props = [];
    foreach ((array) ($data['specs'] ?? []) as $row) {
        if (is_array($row) && count($row) >= 2) {
            $spec_props[] = [
                '@type' => 'PropertyValue',
                'name' => wp_strip_all_tags(ws_spt_text($row[0])),
                'value' => wp_strip_all_tags(ws_spt_text($row[1])),
            ];
        }
    }

    $graph = [[
        '@type' => 'ProductGroup',
        '@id' => $url . '#productgroup',
        'name' => ws_spt_text($data['title'] ?? get_the_title($post_id)),
        'description' => ws_spt_text($data['summary'] ?? get_post_field('post_excerpt', $post_id)),
        'image' => $images,
        'brand' => ['@type' => 'Brand', 'name' => 'Winstartek'],
        'manufacturer' => ['@type' => 'Organization', 'name' => '中輝光電股份有限公司', 'url' => 'https://winstartek.com'],
        'category' => ws_spt_text($data['schemaCategory'] ?? ''),
        'productGroupID' => ws_spt_text($data['productGroupID'] ?? get_post_field('post_name', $post_id)),
        'additionalProperty' => $spec_props,
    ]];

    $faq_entities = [];
    foreach ((array) ($data['faq'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $q = ws_spt_text($item['q'] ?? '');
        $a = ws_spt_text($item['a'] ?? '');
        if ($q !== '' && $a !== '') {
            $faq_entities[] = [
                '@type' => 'Question',
                'name' => wp_strip_all_tags($q),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($a)],
            ];
        }
    }
    if ($faq_entities) {
        $graph[] = [
            '@type' => 'FAQPage',
            '@id' => $url . '#faq',
            'mainEntity' => $faq_entities,
        ];
    }

    $graph = array_values(array_filter($graph, function ($node) {
        if (!is_array($node)) {
            return true;
        }
        $type = $node['@type'] ?? '';
        $types = is_array($type) ? $type : [$type];
        $is_product = in_array('Product', $types, true) || in_array('ProductGroup', $types, true);
        if (!$is_product) {
            return true;
        }
        if (!empty($node['offers']) || !empty($node['review']) || !empty($node['aggregateRating'])) {
            return true;
        }
        foreach ((array) ($node['hasVariant'] ?? []) as $variant) {
            if (is_array($variant) && !empty($variant['offers'])) {
                return true;
            }
        }
        return false;
    }));

    if (!$graph) {
        return;
    }

    echo '<script type="application/ld+json" id="ws-standard-product-schema">' . wp_json_encode([
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
