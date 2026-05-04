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
.ws-hero-spec{margin:0}
.ws-hero-spec h2{margin-top:0}
@media(max-width:782px){.ws-hero{grid-template-columns:1fr;gap:18px}}

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
.ws-faq summary::after{content:"+";position:absolute;right:18px;top:50%;transform:translateY(-50%);font-size:24px;color:var(--ws-red);font-weight:300;line-height:1;transition:transform .2s}
.ws-faq details[open] summary::after{content:"\2212"}
.ws-faq summary:hover{background:#fffafa;color:var(--ws-red-dark)}
.ws-faq details > div{padding:0 20px 18px;color:#444;line-height:1.85}

/* CTA */
.ws-cta{background:linear-gradient(135deg,var(--ws-red) 0%,var(--ws-red-dark) 100%);color:#fff;padding:38px 28px;border-radius:18px;text-align:center;margin:32px 0 0}
.ws-cta h3{color:#fff;margin:0 0 12px;font-size:24px;border:0;padding:0}
.ws-cta p{color:#ffe5e5;margin:0 0 22px;font-size:16px}
.ws-cta a.btn{display:inline-block;background:#fff;color:var(--ws-red);padding:14px 36px;border-radius:6px;text-decoration:none;font-weight:700;font-size:16px;transition:transform .15s,box-shadow .15s}
.ws-cta a.btn:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(0,0,0,.20);color:var(--ws-red-dark)}
</style>
    <?php
}, 100);
