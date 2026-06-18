<?php
/**
 * Plugin Name: Winstartek Product Template
 * Description: 產品頁樣板 — CSS 從 post_content 抽出來、wpautop 關掉、避免 <style> 被 wpautop 切壞
 * Version: 1.0
 *
 * 啟用方式：page postmeta `_ws_product_template = 1`
 */

if (!defined('ABSPATH')) exit;

function ws_pt_is_product_template() {
    if (!is_singular()) return false;
    $id = get_queried_object_id();
    if (!$id) return false;
    return (string) get_post_meta($id, '_ws_product_template', true) === '1';
}

/* 1. 對標記過的頁面關掉 wpautop / wptexturize（避免 wpautop 切壞 <style> 與 details/summary） */
add_filter('the_content', function($content) {
    if (ws_pt_is_product_template()) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'wptexturize');
        remove_filter('the_content', 'shortcode_unautop');
    }
    return $content;
}, 0);

/* 2. 在 wp_head 注入樣板 CSS（只在標記過的頁面） */
add_action('wp_head', function() {
    if (!ws_pt_is_product_template()) return;
    ?>
<style id="ws-product-template-css">
:root{
  --ws-red:#ed3f42;        /* 站體主紅，對齊公司簡介／Astra color-0 */
  --ws-red-dark:#d8060a;   /* 較深紅，做 hover / gradient end */
  --ws-red-soft:#fff5f5;   /* 紅底淺色背景 */
  --ws-text:#131516;
  --ws-gray:#707c84;
  --ws-bg:#F5F5F5;
  --ws-bg-2:#F2F5F7;
  --ws-line:#e5e7eb;
}
.ws-prod{font-family:inherit;color:var(--ws-text);line-height:1.75}
.ws-prod .ws-has-subtitle{margin-bottom:26px!important}
.ws-product-subtitle{
  display:block;
  margin-top:8px;
  font-size:28px;
  line-height:1.32;
  font-weight:600;
  color:#374047;
}
.ws-prod h2{margin:42px 0 18px;padding-bottom:10px;border-bottom:3px solid var(--ws-red);font-size:24px;color:var(--ws-text);font-weight:700}
.ws-prod h3{margin:20px 0 8px;font-size:18px;color:var(--ws-text)}

/* TL;DR */
.ws-tldr{background:var(--ws-red-soft);border-left:4px solid var(--ws-red);padding:18px 22px;margin:0 0 32px;border-radius:14px}
.ws-tldr p{margin:0;font-size:15.5px;line-height:1.85}

/* Hero — 左圖右規格 */
.ws-hero{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:28px;align-items:start;margin:0 0 36px}
.ws-hero-img{margin:0}
.ws-hero-img img{width:100%;height:auto;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.08);display:block}
.ws-hero-img figcaption{margin-top:10px;color:var(--ws-gray);font-size:14px;text-align:center}
.ws-hero-gallery{display:grid;grid-template-rows:repeat(2,minmax(0,1fr));gap:18px}
.ws-hero-gallery figure{margin:0;min-height:0;display:grid;grid-template-rows:minmax(0,1fr) auto}
.ws-hero-gallery img{width:100%;height:100%;background:#fff;object-fit:contain}
.ws-hero-gallery .ws-hero-shot:nth-child(2) img{padding:20px}
.ws-hero-spec{margin:0}
.ws-hero-spec h2{margin-top:0}
@media(min-width:783px){.ws-prod .ws-hero > .ws-hero-img{margin-top:62px!important}}
@media(min-width:783px){.ws-hero-gallery.ws-height-synced{height:var(--ws-spec-table-height)}}
@media(max-width:782px){.ws-hero{grid-template-columns:1fr;gap:18px}.ws-hero-gallery{display:flex;flex-direction:column}.ws-hero-gallery img{height:auto}}

/* 規格表 */
.ws-spec-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--ws-line)}
.ws-spec{width:100%;border-collapse:collapse;background:#fff}
.ws-spec td{padding:11px 14px;border-bottom:1px solid var(--ws-line);text-align:left;vertical-align:top;font-size:14.5px}
.ws-spec tr:last-child td{border-bottom:0}
.ws-spec tr:nth-child(even){background:var(--ws-bg-2)}
.ws-spec td:first-child{width:38%;font-weight:600;color:var(--ws-text);background:linear-gradient(90deg,#fafafa,transparent)}

/* 特色 */
.ws-feature-list{line-height:2.1;padding-left:22px;margin:0 0 28px}
.ws-feature-list li{margin-bottom:6px}
.ws-feature-list strong{color:var(--ws-red-dark)}

/* 應用場景 grid */
.ws-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin:18px 0 24px}
.ws-grid figure{margin:0;border:1px solid var(--ws-line);border-radius:10px;overflow:hidden;background:#fff;transition:transform .2s,box-shadow .2s}
.ws-grid figure:hover{transform:translateY(-3px);box-shadow:0 8px 22px rgba(0,0,0,.10)}
.ws-grid img{width:100%;height:200px;object-fit:cover;display:block}
.ws-grid figcaption{padding:12px 14px;font-weight:600;color:var(--ws-text);background:#fff;border-top:1px solid var(--ws-line)}
.ws-app{columns:2;column-gap:32px;line-height:2.1;padding-left:22px;margin:0 0 28px}
.ws-app li{break-inside:avoid;margin-bottom:4px}
@media(max-width:600px){.ws-app{columns:1}}
@media(max-width:640px){.ws-product-subtitle{font-size:22px}}

/* 比較表 */
.ws-cmp-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--ws-line);margin:14px 0 28px}
.ws-cmp{width:100%;border-collapse:collapse;background:#fff}
.ws-cmp th,.ws-cmp td{padding:13px 14px;border-bottom:1px solid var(--ws-line)}
.ws-cmp tr:last-child td{border-bottom:0}
.ws-cmp thead th{background:linear-gradient(135deg,var(--ws-red),var(--ws-red-dark));color:#fff;font-weight:600}
.ws-cmp thead th:first-child{text-align:left}
.ws-cmp thead th:not(:first-child){text-align:center}
.ws-cmp tbody td:not(:first-child){text-align:center}
.ws-cmp tbody td:first-child{font-weight:600;background:#fafafa}
.ws-cmp tbody tr:nth-child(even) td:not(:first-child){background:var(--ws-bg-2)}
.ws-cmp .pos{color:var(--ws-red);font-weight:700}

/* FAQ */
.ws-faq{margin:14px 0 28px}
.ws-faq details{margin:0 0 10px;border:1px solid var(--ws-line);border-radius:8px;background:#fff;transition:box-shadow .2s,border-color .2s}
.ws-faq details[open]{box-shadow:0 4px 14px rgba(0,0,0,.05);border-color:var(--ws-red)}
.ws-faq summary{padding:16px 20px;cursor:pointer;font-weight:600;font-size:16px;color:var(--ws-text);list-style:none;position:relative;padding-right:46px}
.ws-faq summary::-webkit-details-marker{display:none}
.ws-faq summary::after{content:"";position:absolute;right:18px;top:50%;width:10px;height:10px;border-right:3px solid var(--ws-red);border-bottom:3px solid var(--ws-red);transform:translateY(-65%) rotate(45deg);transition:transform .2s ease;box-sizing:border-box}
.ws-faq details[open] summary::after{transform:translateY(-35%) rotate(225deg)}
.ws-faq summary:hover{background:#fffafa;color:var(--ws-red-dark)}
.ws-faq details > div{padding:0 20px 18px;color:#444;line-height:1.85}

/* CTA */
.ws-cta{background:linear-gradient(135deg,var(--ws-red) 0%,var(--ws-red-dark) 100%);color:#fff;padding:38px 28px;border-radius:18px;text-align:center;margin:32px 0 0}
.ws-cta h3{color:#fff;margin:0 0 12px;font-size:24px;border:0;padding:0}
.ws-cta p{color:#ffe5e5;margin:0 0 22px;font-size:16px}
.ws-cta a.btn{display:inline-block;background:#fff;color:var(--ws-red);padding:14px 36px;border-radius:6px;text-decoration:none;font-weight:700;font-size:16px;transition:transform .15s,box-shadow .15s}
.ws-cta a.btn:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(0,0,0,.20);color:var(--ws-red-dark)}

/* This sample page owns its editable product content. Hide legacy single-product
   template blocks so the duplicated model/spec/gallery area does not appear. */
.elementor-17560 > .elementor-element-7939075,
.elementor-17560 > .elementor-element-581da60,
.elementor-17560 > .elementor-element-069429a{
  display:none!important;
}
.elementor-17560::before{
  content:"";
  display:block;
  min-height:150px;
  margin-top:-76px;
  background:
    linear-gradient(90deg,rgba(30,32,34,.82),rgba(76,78,80,.68)),
    repeating-linear-gradient(135deg,rgba(255,255,255,.16) 0 1px,transparent 1px 32px),
    repeating-linear-gradient(45deg,rgba(255,255,255,.08) 0 1px,transparent 1px 52px),
    #5f6264;
  background-size:auto,32px 32px,52px 52px,auto;
}
@media(max-width:921px){
  .elementor-17560::before{
    min-height:128px;
    margin-top:-74px;
  }
}
</style>
    <?php
}, 100);

add_action('wp_footer', function() {
    if (!ws_pt_is_product_template()) return;
    ?>
<script id="ws-product-template-js">
(function(){
  function applyProductSubtitles(){
    var body = document.body;
    if (!body) return;

    var match = body.className.match(/(?:^|\s)(?:postid|page-id)-(\d+)(?:\s|$)/);
    var postId = match ? match[1] : '';
    var mappedTitles = {
      '20159': ['LED 輕鋼架燈', 'T5/T8 格柵燈具與節能標章款'],
      '20255': ['UVC 紫外線殺菌器', '移動式室內空間殺菌設備'],
      '26533': ['LED 照護燈', '病床燈與 MEDLUX 病房照明']
    };
    var title = document.querySelector('.ws-prod .ws-seo-product-title, .ws-uvc-shell h1, .ws-ward-shell h1, .ws-linear-shell h1');
    if (!title || title.querySelector('.ws-product-subtitle')) return;

    var mainText = '';
    var subtitleText = '';
    if (mappedTitles[postId]) {
      mainText = mappedTitles[postId][0];
      subtitleText = mappedTitles[postId][1];
    } else {
      var raw = (title.textContent || '').trim();
      if (raw.indexOf('｜') === -1) return;
      var parts = raw.split('｜');
      mainText = (parts.shift() || '').trim();
      subtitleText = parts.join('｜').trim();
    }
    if (!mainText || !subtitleText) return;

    title.textContent = '';
    title.classList.add('ws-has-subtitle');
    title.appendChild(document.createTextNode(mainText));
    var subtitle = document.createElement('span');
    subtitle.className = 'ws-product-subtitle';
    subtitle.textContent = subtitleText;
    title.appendChild(subtitle);
  }

  function syncProductGalleryHeight(){
    var hero = document.querySelector('.ws-prod .ws-hero');
    if (!hero) return;

    var gallery = hero.querySelector('.ws-hero-gallery');
    var spec = hero.querySelector('.ws-hero-spec .ws-spec-wrap');
    if (!gallery || !spec) return;

    if (!window.matchMedia('(min-width: 783px)').matches) {
      gallery.classList.remove('ws-height-synced');
      gallery.style.removeProperty('--ws-spec-table-height');
      return;
    }

    var height = Math.round(spec.getBoundingClientRect().height);
    if (height > 0) {
      gallery.style.setProperty('--ws-spec-table-height', height + 'px');
      gallery.classList.add('ws-height-synced');
    }
  }

  document.addEventListener('DOMContentLoaded', applyProductSubtitles);
  document.addEventListener('DOMContentLoaded', syncProductGalleryHeight);
  window.addEventListener('load', syncProductGalleryHeight);
  window.addEventListener('resize', syncProductGalleryHeight);

  if ('ResizeObserver' in window) {
    var observer = new ResizeObserver(syncProductGalleryHeight);
    document.addEventListener('DOMContentLoaded', function(){
      var spec = document.querySelector('.ws-prod .ws-hero .ws-hero-spec .ws-spec-wrap');
      if (spec) observer.observe(spec);
    });
  }
})();
</script>
    <?php
}, 100);
