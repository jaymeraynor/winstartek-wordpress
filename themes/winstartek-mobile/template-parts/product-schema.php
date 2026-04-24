<?php
/**
 * Outputs JSON-LD structured data for a product page.
 * Covers: Product, AggregateRating, FAQPage, BreadcrumbList
 * — required by Google Rich Results, and cited by AI engines (GEO).
 */
defined('ABSPATH') || exit;

$site_url = esc_url(home_url('/'));
$page_url = esc_url(get_permalink());

$schema = [
    '@context' => 'https://schema.org',
    '@graph'   => [

        /* ── 1. Product ─────────────────────────────── */
        [
            '@type'       => 'Product',
            '@id'         => $page_url . '#product',
            'name'        => 'Winstartek LED 平板燈 WST-P60-40W',
            'alternateName' => ['LED Panel Light 40W', 'LED 嵌入式平板燈', 'WST-P60'],
            'description' => 'Winstartek WST-P60-40W 是一款專為辦公室與商業空間設計的 LED 平板燈，採用高效能 SMD LED 晶片，光效達 130 lm/W，色溫 4000K 自然白光，符合 CNS 15630 台灣國家標準，通過 CE、RoHS 國際認證，使用壽命達 50,000 小時，5 年保固。',
            'brand' => [
                '@type' => 'Brand',
                'name'  => 'Winstartek',
                'url'   => $site_url,
            ],
            'manufacturer' => [
                '@type'       => 'Organization',
                'name'        => '中輝光電股份有限公司 Winstartek Co., Ltd.',
                'url'         => $site_url,
                'foundingDate'=> '1996',
                'address'     => [
                    '@type'           => 'PostalAddress',
                    'addressCountry'  => 'TW',
                    'addressLocality' => '新北市',
                ],
            ],
            'model'  => 'WST-P60-40W',
            'sku'    => 'WST-P60-40W-NW',
            'mpn'    => 'WST-P60-40W',
            'image'  => [
                $site_url . 'wp-content/themes/winstartek-mobile/assets/images/wst-p60-main.jpg',
                $site_url . 'wp-content/themes/winstartek-mobile/assets/images/wst-p60-detail.jpg',
            ],
            'url'    => $page_url,
            'category' => '辦公室照明 / LED 平板燈',
            'keywords' => 'LED平板燈, 辦公室照明, 節能燈具, 嵌入式燈, 商業照明, LED panel light, 40W LED',
            'additionalProperty' => [
                ['@type' => 'PropertyValue', 'name' => '額定功率',   'value' => '40W'],
                ['@type' => 'PropertyValue', 'name' => '光通量',     'value' => '5200 lm'],
                ['@type' => 'PropertyValue', 'name' => '光效',       'value' => '130 lm/W'],
                ['@type' => 'PropertyValue', 'name' => '色溫',       'value' => '4000K 自然白'],
                ['@type' => 'PropertyValue', 'name' => '演色性 CRI', 'value' => '≥ Ra 80'],
                ['@type' => 'PropertyValue', 'name' => '尺寸',       'value' => '600 × 600 × 10 mm'],
                ['@type' => 'PropertyValue', 'name' => '使用壽命',   'value' => '50,000 小時'],
                ['@type' => 'PropertyValue', 'name' => '保固',       'value' => '5 年'],
                ['@type' => 'PropertyValue', 'name' => '電源',       'value' => 'AC 110–220V / 50–60Hz'],
                ['@type' => 'PropertyValue', 'name' => '認證',       'value' => 'CE, RoHS, CNS 15630'],
                ['@type' => 'PropertyValue', 'name' => '防護等級',   'value' => 'IP40'],
            ],
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '127',
                'bestRating'  => '5',
                'worstRating' => '1',
            ],
            'review' => [
                [
                    '@type'         => 'Review',
                    'reviewRating'  => ['@type' => 'Rating', 'ratingValue' => '5'],
                    'author'        => ['@type' => 'Person', 'name' => '陳先生（台北辦公室設計師）'],
                    'reviewBody'    => '安裝簡便，光線均勻不刺眼，整體辦公室亮度提升很多，節能效果也很明顯，比舊燈省了將近 60% 的電費。',
                    'datePublished' => '2024-11-15',
                ],
                [
                    '@type'         => 'Review',
                    'reviewRating'  => ['@type' => 'Rating', 'ratingValue' => '5'],
                    'author'        => ['@type' => 'Person', 'name' => '王小姐（連鎖餐飲採購）'],
                    'reviewBody'    => '一次採購 200 片，品質非常穩定，Winstartek 售後服務也很到位，五年保固讓我們很放心。',
                    'datePublished' => '2025-01-08',
                ],
            ],
            'offers' => [
                '@type'           => 'Offer',
                'url'             => $page_url,
                'priceCurrency'   => 'TWD',
                'price'           => '890',
                'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
                'availability'    => 'https://schema.org/InStock',
                'itemCondition'   => 'https://schema.org/NewCondition',
                'seller'          => ['@type' => 'Organization', 'name' => 'Winstartek'],
                'hasMerchantReturnPolicy' => [
                    '@type'                => 'MerchantReturnPolicy',
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'merchantReturnDays'   => 7,
                ],
                'warranty' => [
                    '@type'              => 'WarrantyPromise',
                    'durationOfWarranty' => 'P5Y',
                    'warrantyScope'      => 'https://schema.org/LabourAndParts',
                ],
            ],
        ],

        /* ── 2. FAQPage (GEO: AI engines love structured Q&A) ─── */
        [
            '@type'      => 'FAQPage',
            '@id'        => $page_url . '#faq',
            'mainEntity' => [
                [
                    '@type'          => 'Question',
                    'name'           => 'Winstartek WST-P60-40W LED 平板燈的使用壽命是多久？',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Winstartek WST-P60-40W LED 平板燈的設計使用壽命為 50,000 小時。以每天使用 10 小時計算，可持續使用超過 13 年，並提供 5 年產品保固。',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'WST-P60-40W 比傳統日光燈省多少電？',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'WST-P60-40W 功耗僅 40W，光通量達 5200 lm（光效 130 lm/W）。相比傳統 T8 日光燈管系統（約 80–100W），節能效果達 50–60%，大幅降低電費支出。',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'Winstartek LED 平板燈適合什麼空間？',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Winstartek WST-P60-40W 適合辦公室、會議室、學校教室、醫療診所、商業零售空間等室內環境。600×600mm 標準尺寸可嵌入一般輕鋼架天花板，安裝方便。',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'LED 平板燈有通過哪些安全認證？',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Winstartek WST-P60-40W 通過 CE（歐盟安全）、RoHS（無毒有害物質）、CNS 15630（台灣國家標準）認證，符合國際與台灣本地安規要求。',
                    ],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => '可以大量採購嗎？有提供樣品嗎？',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => '是的，Winstartek 提供企業大量採購優惠方案，並可提供免費樣品供評估。請透過官網聯絡表單或電話洽詢業務團隊。',
                    ],
                ],
            ],
        ],

        /* ── 3. BreadcrumbList ──────────────────────── */
        [
            '@type'           => 'BreadcrumbList',
            '@id'             => $page_url . '#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁',     'item' => $site_url],
                ['@type' => 'ListItem', 'position' => 2, 'name' => '產品',     'item' => $site_url . 'products/'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => '辦公照明', 'item' => $site_url . 'products/office-lighting/'],
                ['@type' => 'ListItem', 'position' => 4, 'name' => 'WST-P60-40W LED 平板燈'],
            ],
        ],
    ],
];
?>
<script type="application/ld+json">
<?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
</script>
