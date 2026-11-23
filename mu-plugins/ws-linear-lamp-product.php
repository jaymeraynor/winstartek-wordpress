<?php
/**
 * Plugin Name: Winstar LED Linear Lamp Product
 * Description: Layout, carousel and schema for the LED linear lamp product page.
 */
if (!defined('ABSPATH')) {
    exit;
}

function ws_linear_lamp_is_page(): bool {
    if (!is_singular('product')) {
        return false;
    }
    $post = get_queried_object();
    return $post && $post->post_name === 'led-linear-lamp';
}

add_action('wp_footer', static function () {
    if (!ws_linear_lamp_is_page()) {
        return;
    }
    ?>
    <style id="ws-linear-lamp-product-css">
    body.single-product #content.site-content{padding-top:0!important;background:#f2f2f2!important}
    .elementor-17560 > .elementor-element-7939075,
    .elementor-17560 > .elementor-element-581da60,
    .elementor-17560 > .elementor-element-069429a{
      display:none!important;
    }
    .ws-linear-shell{max-width:1180px;margin:0 auto;padding:0 18px 56px;color:#111;line-height:1.72}
    .ws-linear-shell *{box-sizing:border-box}
    .ws-linear-shell h1,.ws-linear-shell h2,.ws-linear-shell h3{font-family:inherit;letter-spacing:0;color:#111}
    .ws-linear-shell h1{font-size:42px;line-height:1.18;margin:0 0 26px;text-align:left}
    .ws-linear-shell .ws-product-subtitle{display:block;font-size:28px;line-height:1.32;font-weight:600;color:#374047;margin:0 0 26px;text-align:left}
    .ws-linear-shell h2{font-size:26px;line-height:1.35;margin:34px 0 18px}
    .ws-linear-shell h3{font-size:20px;line-height:1.35;margin:0 0 10px}
    .ws-linear-hero{display:grid;grid-template-columns:minmax(320px,.95fr) minmax(440px,1.35fr);gap:32px;align-items:start;margin-bottom:28px}
    .ws-linear-carousel{position:relative;background:#fff;border-radius:8px;box-shadow:0 8px 22px rgba(0,0,0,.06);overflow:hidden;min-height:540px;display:flex;align-items:center;justify-content:center;margin-top:46px}
    .ws-linear-slide{display:none;width:100%;height:100%;margin:0;padding:24px}
    .ws-linear-slide.is-active{display:flex;flex-direction:column;align-items:center;justify-content:center}
    .ws-linear-slide img{width:100%;height:430px;object-fit:contain;display:block}
    .ws-linear-caption{margin-top:12px;font-size:14px;color:#707c84;text-align:center}
    .ws-panel-carousel-btn{position:absolute;top:50%;transform:translateY(-50%);width:42px!important;height:42px!important;padding:0!important;border:0!important;border-radius:999px!important;background:rgba(19,21,22,.68)!important;color:#fff;display:flex!important;align-items:center!important;justify-content:center!important;font-size:0!important;line-height:1!important;cursor:pointer;z-index:2}
    .ws-panel-carousel-btn:hover{background:#ed3f42!important}
    .ws-panel-carousel-btn::before{content:"";display:block;width:11px;height:11px;border-top:3px solid #fff;border-right:3px solid #fff;box-sizing:border-box}
    .ws-panel-carousel-prev{left:12px}
    .ws-panel-carousel-next{right:12px}
    .ws-panel-carousel-prev::before{transform:rotate(-135deg);margin-left:3px}
    .ws-panel-carousel-next::before{transform:rotate(45deg);margin-right:3px}
    .ws-linear-spec h2,.ws-linear-detail h2{border-bottom:3px solid #ed3f42;padding-bottom:10px;margin-top:0}
    .ws-linear-table-wrap{overflow-x:auto;border:1px solid #dbe1e6;border-radius:8px;background:#fff}
    .ws-linear-spec-table,.ws-linear-compare{width:100%;border-collapse:collapse;font-size:16px}
    .ws-linear-spec-table td,.ws-linear-compare td,.ws-linear-compare th{padding:14px 16px;border-bottom:1px solid #dbe1e6;vertical-align:middle}
    .ws-linear-spec-table tr:nth-child(even) td,.ws-linear-compare tbody tr:nth-child(even) td{background:#f3f6f8}
    .ws-linear-spec-table td:first-child{width:34%;font-weight:700;color:#111;background:linear-gradient(90deg,#fff,#f5f7f8)}
    .ws-linear-compare th{background:#ed3f42;color:#fff;font-weight:700;text-align:center}
    .ws-linear-compare td:first-child{font-weight:700;color:#111;white-space:nowrap}
    .ws-linear-tldr{border-left:4px solid #ed3f42;background:#fff4f4;border-radius:8px;padding:16px 18px;margin:0 0 34px}
    .ws-linear-feature-grid,.ws-linear-app-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-bottom:18px}
    .ws-linear-card{background:#fff;border:1px solid #e3e7ea;border-radius:8px;padding:20px;box-shadow:0 6px 18px rgba(0,0,0,.04)}
    .ws-linear-card p{margin:0;color:#4b555b}
    .ws-linear-product-faq details{border:1px solid #e1e6e8;border-radius:8px;background:#fff;margin:12px 0;overflow:hidden}
    .ws-linear-product-faq summary{cursor:pointer;list-style:none;padding:16px 52px 16px 18px;font-weight:700;position:relative}
    .ws-linear-product-faq summary::-webkit-details-marker{display:none}
    .ws-linear-product-faq summary::after{content:"";position:absolute;right:20px;top:50%;width:10px;height:10px;border-right:2px solid #ed3f42;border-bottom:2px solid #ed3f42;transform:translateY(-60%) rotate(45deg);transition:.2s}
    .ws-linear-product-faq details[open] summary::after{transform:translateY(-30%) rotate(225deg)}
    .ws-linear-product-faq details div{padding:0 18px 18px;color:#4b555b}
    .ws-linear-cta{display:flex;align-items:center;justify-content:space-between;gap:24px;background:#ed3f42;color:#fff;border-radius:8px;padding:26px 30px;margin-top:34px}
    .ws-linear-cta h3,.ws-linear-cta p{color:#fff;margin:0}
    .ws-linear-cta .btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 20px;border:1px solid #fff;color:#fff;text-decoration:none;font-weight:700}
    @media (max-width:921px){.ws-linear-shell h1{font-size:34px}.ws-linear-shell .ws-product-subtitle{font-size:22px;margin-bottom:22px}.ws-linear-hero,.ws-linear-feature-grid,.ws-linear-app-grid{grid-template-columns:1fr}.ws-linear-carousel{min-height:420px;margin-top:0}.ws-linear-slide img{height:320px}.ws-linear-cta{display:block}.ws-linear-cta .btn{margin-top:16px}}
    </style>
    <script id="ws-linear-lamp-carousel-js">
    (function(){
      var title=document.querySelector('.ws-linear-shell h1');
      if(title){
        var raw=(title.textContent||'').trim();
        if(raw.indexOf('｜')!==-1){
          var parts=raw.split('｜');
          var main=(parts.shift()||'').trim();
          var sub=parts.join('｜').trim();
          if(main){
            title.innerHTML='';
            title.appendChild(document.createTextNode(main));
          }
          if(sub){
            var subtitle=title.querySelector('.ws-product-subtitle');
            if(!subtitle){
              subtitle=document.createElement('span');
              subtitle.className='ws-product-subtitle';
              title.appendChild(subtitle);
            }
            subtitle.textContent=sub;
          }
        }
      }
      document.querySelectorAll('[data-ws-panel-carousel]').forEach(function(root){
        var slides=[].slice.call(root.querySelectorAll('.ws-linear-slide'));
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

// 2026-11 整併：宣告本頁已有主 ProductGroup，讓 winstartek-product-schema-offers 不再輸出 stub Product（本頁 schema 不含 Offer，乙方案）
add_filter('winstartek_has_primary_product_schema', static function ($has) {
    return $has || ws_linear_lamp_is_page();
});

add_action('wp_head', static function () {
    if (!ws_linear_lamp_is_page()) {
        return;
    }
    $url = get_permalink();
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'ProductGroup',
                '@id' => $url . '#productgroup',
                'name' => 'LED 一體線型燈',
                'description' => '中輝光電 LED 一體線型燈為寬版吸頂線型照明，提供 2 呎與 4 呎規格、IP66 防塵防水、CNS 與節能標章款式，適合辦公室、商業空間、停車場與公共走廊。',
                'brand' => ['@type' => 'Brand', 'name' => 'Winstartek'],
                'manufacturer' => ['@type' => 'Organization', 'name' => '中輝光電股份有限公司', 'url' => 'https://winstartek.com'],
                'category' => 'LED Linear Lamp / Ceiling Light',
                'productGroupID' => 'TPD-BH8S',
                'variesBy' => ['size', 'wattage', 'colorTemperature'],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $url . '#faq',
                'mainEntity' => array_map(static function ($item) {
                    return [
                        '@type' => 'Question',
                        'name' => $item[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
                    ];
                }, [
                    ['LED 一體線型燈適合哪些空間？', '適合辦公室、商業空間、走廊、停車場、倉儲與需要長條均勻照明的室內場域。'],
                    ['這款線型燈有 2 呎與 4 呎嗎？', '有，主要尺寸包含 600×67×47.5 mm 與 1200×67×47.5 mm。'],
                    ['是否有 CNS 與節能標章款？', '型錄第 6 頁列出節能標章型與 CNS 型兩組規格，可依專案需求選擇。'],
                    ['IP66 代表什麼？', 'IP66 代表具備防塵與防水能力，適合較嚴苛或需要防護的室內空間。'],
                    ['可以取代傳統 T8 燈具嗎？', '可作為傳統螢光 T8 燈具的替代方案，提供較佳節能效率與較長壽命。'],
                    ['色溫有哪些選擇？', '規格包含 3000K、4000K、5000K 與 5700K 等選項，依型號不同而異。'],
                    ['是否能降低眩光？', '一體線型燈發光面積較大，能提供較柔和且均勻的光線，降低直視眩光感。'],
                    ['可以分段調光嗎？', 'CNS 型規格提供四段功率與光通量配置，可依需求選擇照度與用電。'],
                    ['輸入電壓範圍是多少？', '主要規格為 AC100-277V，適合一般商業與公共空間供電條件。'],
                    ['如何選擇 2 呎或 4 呎？', '小空間、走道或局部照明可用 2 呎；開放辦公、停車場與大面積空間通常選 4 呎。'],
                ]),
            ],
        ],
    ];
    echo '<script type="application/ld+json" id="ws-linear-lamp-schema">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 20);
