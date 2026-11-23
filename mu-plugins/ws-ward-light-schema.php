<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// 2026-11 整併：宣告病房燈頁（post 26533）已有主 ProductGroup，offers 外掛不再輸出 stub Product（本頁 schema 不含 Offer，乙方案）
add_filter('winstartek_has_primary_product_schema', static function ($has, $post_id = 0) {
    return $has || (int) $post_id === 26533;
}, 10, 2);

add_action('wp_head', static function (): void {
    $object_id = get_queried_object_id();
    if ((int) $object_id !== 26533) {
        return;
    }

    $css = <<<'CSS'
.page-id-26533 .entry-header,.postid-26533 .entry-header{display:none!important}
body.postid-26533 #masthead,body.page-id-26533 #masthead,body.postid-26533 #masthead .elementor-17406,body.page-id-26533 #masthead .elementor-17406,body.postid-26533 #masthead .elementor-element-11f603b,body.page-id-26533 #masthead .elementor-element-11f603b,body.postid-26533 .elementor-location-header,body.page-id-26533 .elementor-location-header,body.postid-26533 .elementor-location-header .elementor-element-11f603b,body.page-id-26533 .elementor-location-header .elementor-element-11f603b{background:linear-gradient(90deg,rgba(30,32,34,.82),rgba(76,78,80,.68)),repeating-linear-gradient(135deg,rgba(255,255,255,.16) 0 1px,transparent 1px 32px),repeating-linear-gradient(45deg,rgba(255,255,255,.08) 0 1px,transparent 1px 52px),#303335!important;background-size:auto,32px 32px,52px 52px,auto!important}
.postid-26533 #content.site-content,.page-id-26533 #content.site-content{padding-top:150px!important;background:linear-gradient(90deg,rgba(30,32,34,.82),rgba(76,78,80,.68)) 0 0/100% 74px no-repeat,repeating-linear-gradient(135deg,rgba(255,255,255,.16) 0 1px,transparent 1px 32px) 0 0/32px 32px no-repeat,repeating-linear-gradient(45deg,rgba(255,255,255,.08) 0 1px,transparent 1px 52px) 0 0/52px 52px no-repeat,#f2f2f2!important}
.page-id-26533 .elementor-26533::before,.postid-26533 .elementor-26533::before{display:none!important;content:none!important}
.ws-ward-shell{max-width:1180px;margin:0 auto;padding:0 18px 54px;color:#111;line-height:1.75}
.ws-ward-shell *{box-sizing:border-box}
.ws-ward-shell h1,.ws-ward-shell h2,.ws-ward-shell h3{font-family:inherit;letter-spacing:0;color:#111}
.ws-ward-shell h1{font-size:42px;line-height:1.2;margin:0 0 26px}
.ws-ward-shell h2{font-size:26px;line-height:1.35;margin:42px 0 18px;padding-bottom:10px;border-bottom:3px solid #ed3f42}
.ws-ward-shell h3{font-size:21px;margin:24px 0 12px}
.ws-ward-hero{display:grid;grid-template-columns:minmax(320px,.95fr) minmax(440px,1.35fr);gap:32px;align-items:start;margin-bottom:28px}
.ws-ward-media{padding-top:51px}
.ws-ward-placeholder{min-height:520px;border-radius:10px;background:linear-gradient(135deg,#fff,#f7f8f9);border:1px dashed #c7cdd1;box-shadow:0 6px 18px rgba(0,0,0,.07);display:grid;place-items:center;text-align:center;padding:30px}
.ws-ward-placeholder strong{display:block;color:#ed3f42;font-size:24px;margin-bottom:6px}
.ws-ward-placeholder span{color:#74808a;font-size:15px}
.ws-ward-carousel{position:relative;min-height:520px;border-radius:10px;background:#fff;border:1px solid #e2e7ea;box-shadow:0 6px 18px rgba(0,0,0,.07);overflow:hidden}
.ws-ward-slide{display:none;min-height:520px;align-items:center;justify-content:center;padding:26px;background:#fff}
.ws-ward-slide.is-active{display:flex}
.ws-ward-slide img{display:block;width:100%;height:468px;object-fit:contain}
.ws-ward-caption{position:absolute;left:0;right:0;bottom:0;padding:10px 16px;background:rgba(255,255,255,.9);color:#66717a;font-size:14px;text-align:center}
.ws-panel-carousel-btn{position:absolute;top:50%;transform:translateY(-50%);width:42px!important;height:42px!important;padding:0!important;border:0!important;border-radius:999px!important;background:rgba(19,21,22,.68)!important;color:#fff;display:flex!important;align-items:center!important;justify-content:center!important;font-size:0!important;line-height:1!important;cursor:pointer;z-index:2}
.ws-panel-carousel-btn:hover{background:#ed3f42!important}
.ws-panel-carousel-btn::before{content:"";display:block;width:11px;height:11px;border-top:3px solid #fff;border-right:3px solid #fff;box-sizing:border-box}
.ws-panel-carousel-prev{left:12px}
.ws-panel-carousel-next{right:12px}
.ws-panel-carousel-prev::before{transform:rotate(-135deg);margin-left:3px}
.ws-panel-carousel-next::before{transform:rotate(45deg);margin-right:3px}
.ws-ward-spec h2{margin-top:0}
.ws-ward-table-wrap{overflow-x:auto;border:1px solid #dfe5e8;border-radius:8px;background:#fff}
.ws-ward-spec-table,.ws-ward-compare{width:100%;border-collapse:collapse;background:#fff}
.ws-ward-spec-table td,.ws-ward-compare th,.ws-ward-compare td{padding:13px 16px;border-bottom:1px solid #dfe5e8;border-right:1px solid #edf1f3;vertical-align:middle}
.ws-ward-spec-table tr:nth-child(even) td,.ws-ward-compare tbody tr:nth-child(even) td{background:#f3f6f8}
.ws-ward-spec-table td:first-child{width:34%;font-weight:700}
.ws-ward-compare th{background:#ed1c24;color:#fff;font-weight:700;text-align:center}
.ws-ward-compare td:first-child{font-weight:700;background:#fafafa}
.ws-ward-tldr{margin:32px 0 36px;padding:18px 22px;border-left:3px solid #ed3f42;border-radius:10px;background:#fff3f3;font-size:17px}
.ws-ward-feature-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:0;padding:0;list-style:none}
.ws-ward-feature-list li{background:#fff;border:1px solid #e2e7ea;border-radius:8px;padding:18px 20px}
.ws-ward-app-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:12px}
.ws-ward-app-card{border-radius:8px;border:1px solid #e2e7ea;background:#fff;padding:22px;min-height:150px}
.ws-ward-app-card h3{color:#ed3f42;margin-top:0}
.ws-ward-scene{margin:34px 0 36px;border:1px solid #e2e7ea;border-radius:10px;overflow:hidden;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.07)}
.ws-ward-scene img{display:block;width:100%;height:auto;max-height:620px;object-fit:contain;background:#f7f8f9}
.ws-ward-scene figcaption{display:grid;gap:4px;padding:14px 18px;border-top:1px solid #e2e7ea;background:#fff}
.ws-ward-scene figcaption strong{font-size:18px;color:#111}
.ws-ward-scene figcaption span{font-size:14px;color:#5d6870}
.ws-ward-mode{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:12px 0 28px}
.ws-ward-mode div{border:1px solid #e2e7ea;border-radius:8px;background:#fff;padding:18px;text-align:center}
.ws-ward-mode b{display:block;color:#ed3f42;font-size:19px;margin-bottom:4px}
.ws-ward-faq details{border:1px solid #e0e5e8;border-radius:8px;background:#fff;margin:0 0 10px;overflow:hidden}
.ws-ward-faq summary{cursor:pointer;font-weight:700;padding:15px 18px}
.ws-ward-faq details div{padding:0 18px 16px;color:#4f5961}
.ws-ward-cta{margin-top:38px;border-radius:8px;background:#ed3f42;color:#fff;padding:28px 30px;display:flex;align-items:center;justify-content:space-between;gap:18px}
.ws-ward-cta h3,.ws-ward-cta p{color:#fff;margin:0}
.ws-ward-cta .btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 20px;background:#fff;color:#ed3f42;border-radius:4px;font-weight:700;text-decoration:none;white-space:nowrap}
@media(max-width:921px){.postid-26533 #content.site-content,.page-id-26533 #content.site-content{padding-top:128px!important;background-size:100% 58px,32px 32px,52px 52px,auto!important}.ws-ward-hero{grid-template-columns:1fr}.ws-ward-media{padding-top:0}.ws-ward-placeholder,.ws-ward-carousel,.ws-ward-slide{min-height:320px}.ws-ward-slide img{height:280px}.ws-ward-feature-list,.ws-ward-app-grid,.ws-ward-mode{grid-template-columns:1fr 1fr}.ws-ward-cta{display:block}.ws-ward-cta .btn{margin-top:14px}}
@media(max-width:640px){.ws-ward-shell{padding-left:14px;padding-right:14px}.ws-ward-shell h1{font-size:34px}.ws-ward-feature-list,.ws-ward-app-grid,.ws-ward-mode{grid-template-columns:1fr}.ws-ward-compare th,.ws-ward-compare td{padding:11px 12px;white-space:nowrap}}
CSS;

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'ProductGroup',
                '@id' => 'https://winstartek.com/products/special-application/ward-light/#productgroup',
                'name' => '中輝 MEDLUX LED 照護燈／病床燈',
                'description' => '中輝光電 MEDLUX LED 照護燈可作為醫院病房、長照機構與診所使用的病床燈與病房床頭燈，提供四段照明模式、T8 LED 雙燈管、壁掛安裝與 2/3/4 呎規格。',
                'image' => ['https://winstartek.com/wp-content/uploads/2026/05/medlux-ward-light-scene.jpg'],
                'brand' => ['@type' => 'Brand', 'name' => 'Winstartek'],
                'manufacturer' => ['@type' => 'Organization', 'name' => '中輝光電股份有限公司', 'url' => 'https://winstartek.com'],
                'category' => 'LED Care Bedside Light / Hospital Bed Light / Ward Lighting',
                'productGroupID' => 'HBL-MEDLUX-Care-Light',
                'variesBy' => ['model', 'size', 'wattage'],
                'hasVariant' => [
                    ['@type' => 'Product', 'sku' => 'HBL-2102-LD', 'name' => 'HBL-2102-LD MEDLUX LED 照護燈／病床燈'],
                    ['@type' => 'Product', 'sku' => 'HBL-3152-LD', 'name' => 'HBL-3152-LD MEDLUX LED 照護燈／病床燈'],
                    ['@type' => 'Product', 'sku' => 'HBL-4202-LD', 'name' => 'HBL-4202-LD MEDLUX LED 照護燈／病床燈'],
                ],
            ],
        ],
    ];

    echo '<style id="ws-ward-page-css">' . $css . '</style>' . "\n";
    echo '<script type="application/ld+json" class="ws-ward-product-schema">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 30);

add_action('wp_footer', static function (): void {
    if ((int) get_queried_object_id() !== 26533) {
        return;
    }
    ?>
<script id="ws-ward-carousel-js">
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('[data-ws-panel-carousel]').forEach(function(carousel){
    var slides=[].slice.call(carousel.querySelectorAll('.ws-ward-slide'));
    if(slides.length<2){return;}
    var index=slides.findIndex(function(slide){return slide.classList.contains('is-active');});
    if(index<0){index=0;slides[0].classList.add('is-active');}
    function show(next){
      slides[index].classList.remove('is-active');
      index=(next+slides.length)%slides.length;
      slides[index].classList.add('is-active');
    }
    var prev=carousel.querySelector('.ws-panel-carousel-prev');
    var next=carousel.querySelector('.ws-panel-carousel-next');
    if(prev){prev.addEventListener('click',function(){show(index-1);});}
    if(next){next.addEventListener('click',function(){show(index+1);});}
  });
});
</script>
    <?php
}, 50);
