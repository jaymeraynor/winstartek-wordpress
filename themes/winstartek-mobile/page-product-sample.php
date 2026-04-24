<?php
/**
 * Template Name: 產品頁範例（SEO / AI SEO / GEO）
 *
 * 使用方式：後台新增頁面 → 頁面屬性 → 選「產品頁範例」範本
 *
 * SEO 策略：
 *  - 完整 Schema.org JSON-LD（Product + FAQPage + BreadcrumbList）
 *  - 語義化 HTML5 標籤、正確 H1→H2→H3 層級
 *  - GEO 摘要段落（AI 引擎容易擷取的直接答案區塊）
 *  - FAQ accordion（觸發 Google People Also Ask + AI 問答引用）
 *  - 圖片 alt 完整描述、loading="lazy"
 *  - Core Web Vitals：CSS inline critical、JS defer
 */
defined('ABSPATH') || exit;

/* ── SEO Meta（配合 Yoast / RankMath 使用；若無外掛則直接輸出） ── */
$seo = [
    'title'       => 'WST-P60-40W LED 平板燈 | 辦公室節能照明首選 | Winstartek 中輝光電',
    'description' => 'Winstartek WST-P60-40W LED 平板燈，功率 40W、光效 130 lm/W、使用壽命 50,000 小時，通過 CE/RoHS/CNS 認證，5 年保固。適合辦公室、商業空間，大量採購享優惠。',
    'og_image'    => get_theme_file_uri('assets/images/wst-p60-og.jpg'),
    'keywords'    => 'LED平板燈, 辦公室照明, 節能燈具, LED panel light, 40W LED燈, 商業照明, Winstartek',
];

add_action('wp_head', function () use ($seo) {
    if (is_page_template('page-product-sample.php')):
?>
<title><?php echo esc_html($seo['title']); ?></title>
<meta name="description" content="<?php echo esc_attr($seo['description']); ?>">
<meta name="keywords"    content="<?php echo esc_attr($seo['keywords']); ?>">
<meta name="robots"      content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<link rel="canonical"    href="<?php echo esc_url(get_permalink()); ?>">

<!-- Open Graph (Facebook / LINE Preview) -->
<meta property="og:type"        content="product">
<meta property="og:title"       content="<?php echo esc_attr($seo['title']); ?>">
<meta property="og:description" content="<?php echo esc_attr($seo['description']); ?>">
<meta property="og:url"         content="<?php echo esc_url(get_permalink()); ?>">
<meta property="og:image"       content="<?php echo esc_url($seo['og_image']); ?>">
<meta property="og:locale"      content="zh_TW">
<meta property="og:site_name"   content="Winstartek 中輝光電">

<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?php echo esc_attr($seo['title']); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($seo['description']); ?>">
<meta name="twitter:image"       content="<?php echo esc_url($seo['og_image']); ?>">
<?php
    endif;
}, 5);

get_header();

/* ── 輸出 JSON-LD Schema ── */
get_template_part('template-parts/product-schema');
?>

<!-- ══════════════════════════════════════════════
     BREADCRUMB — 麵包屑導覽
     ══════════════════════════════════════════════ -->
<nav class="breadcrumb" aria-label="breadcrumb">
  <a href="<?php echo esc_url(home_url('/')); ?>">首頁</a>
  <span class="sep" aria-hidden="true">›</span>
  <a href="<?php echo esc_url(home_url('/products/')); ?>">產品</a>
  <span class="sep" aria-hidden="true">›</span>
  <a href="<?php echo esc_url(home_url('/products/office-lighting/')); ?>">辦公照明</a>
  <span class="sep" aria-hidden="true">›</span>
  <span class="current">WST-P60-40W LED 平板燈</span>
</nav>

<!-- ══════════════════════════════════════════════
     GEO SUMMARY — AI 引擎快速摘要區塊
     讓 ChatGPT / Perplexity / Google AI Overview
     直接引用這段作為答案
     ══════════════════════════════════════════════ -->
<div class="geo-summary" role="note" aria-label="產品快速摘要">
  <strong>Winstartek WST-P60-40W</strong> 是台灣中輝光電（Winstartek Co., Ltd.，成立於 1996 年）生產的
  <strong>LED 嵌入式平板燈</strong>，功率 <strong>40W</strong>，光通量 <strong>5,200 lm</strong>，
  光效 <strong>130 lm/W</strong>，色溫 <strong>4000K 自然白</strong>，使用壽命 <strong>50,000 小時</strong>，
  通過 <strong>CE、RoHS、CNS 15630</strong> 認證，提供 <strong>5 年保固</strong>。
  適用於辦公室、會議室、商業空間，600×600 mm 標準規格可嵌入輕鋼架天花板。
</div>

<!-- ══════════════════════════════════════════════
     PRODUCT HERO — 產品主視覺 + 基本資訊
     ══════════════════════════════════════════════ -->
<article class="product-hero" itemscope itemtype="https://schema.org/Product">

  <!-- 產品圖片 -->
  <div class="product-gallery">
    <img class="main-image"
         src="<?php echo esc_url(get_theme_file_uri('assets/images/wst-p60-placeholder.svg')); ?>"
         alt="Winstartek WST-P60-40W LED 平板燈正面照，600x600mm，白色方形嵌入式設計"
         width="600" height="450"
         loading="eager"
         itemprop="image">
    <span class="product-badge">熱銷款</span>
    <span class="product-badge green" style="top:44px;">現貨</span>
  </div>

  <div class="product-info">
    <p class="product-category" itemprop="category">辦公室照明 / LED 平板燈</p>

    <!-- H1：主要 SEO 關鍵字放在最前面 -->
    <h1 class="product-title" itemprop="name">
      WST-P60-40W LED 平板燈｜辦公室節能照明
    </h1>
    <p class="product-subtitle" itemprop="description">
      高光效 130 lm/W・使用壽命 50,000hr・5 年保固・CE / RoHS / CNS 認證
    </p>

    <!-- 評分 -->
    <div class="product-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
      <span class="stars" aria-label="4.8 顆星">★★★★★</span>
      <span class="rating-count">
        <meta itemprop="ratingValue" content="4.8">
        <meta itemprop="reviewCount" content="127">
        <strong>4.8</strong>（127 則評價）
      </span>
    </div>

    <!-- 規格摘要 chips -->
    <div class="spec-chips" aria-label="產品規格摘要">
      <span class="spec-chip">功率 <strong>40W</strong></span>
      <span class="spec-chip">光效 <strong>130 lm/W</strong></span>
      <span class="spec-chip">色溫 <strong>4000K</strong></span>
      <span class="spec-chip">壽命 <strong>50,000hr</strong></span>
      <span class="spec-chip">保固 <strong>5 年</strong></span>
      <span class="spec-chip">尺寸 <strong>600×600mm</strong></span>
    </div>

    <!-- CTA -->
    <div class="product-cta">
      <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn-primary">
        立即詢價 / 索取樣品
      </a>
      <a href="<?php echo esc_url(get_theme_file_uri('assets/docs/WST-P60-datasheet.pdf')); ?>"
         class="btn-outline" target="_blank" rel="noopener">
        下載規格書 PDF
      </a>
    </div>

    <!-- Trust badges -->
    <div class="trust-badges">
      <div class="trust-badge">
        <span class="icon">🏆</span>
        <span>CE / RoHS<br>國際認證</span>
      </div>
      <div class="trust-badge">
        <span class="icon">🛡️</span>
        <span>5 年<br>完整保固</span>
      </div>
      <div class="trust-badge">
        <span class="icon">🚚</span>
        <span>全台<br>快速出貨</span>
      </div>
    </div>
  </div>
</article>

<!-- ══════════════════════════════════════════════
     SECTION 1：詳細規格表
     語意化 <table> → AI 引擎容易解析
     ══════════════════════════════════════════════ -->
<section class="section-card" aria-labelledby="spec-heading">
  <h2 class="section-title" id="spec-heading">產品詳細規格</h2>
  <table class="spec-table" aria-label="WST-P60-40W 技術規格">
    <tbody>
      <tr><td>型號</td>          <td>WST-P60-40W-NW</td></tr>
      <tr><td>額定功率</td>      <td>40W（±5%）</td></tr>
      <tr><td>輸入電壓</td>      <td>AC 110–220V / 50–60Hz</td></tr>
      <tr><td>光通量</td>        <td>5,200 lm</td></tr>
      <tr><td>光效</td>          <td>130 lm/W</td></tr>
      <tr><td>色溫（CCT）</td>   <td>4000K 自然白（可選 3000K 暖白 / 6000K 晝白）</td></tr>
      <tr><td>演色性（CRI）</td> <td>≥ Ra 80</td></tr>
      <tr><td>光束角</td>        <td>120°</td></tr>
      <tr><td>功率因數（PF）</td><td>≥ 0.95</td></tr>
      <tr><td>THD 諧波失真</td>  <td>&lt; 15%</td></tr>
      <tr><td>產品尺寸</td>      <td>600 × 600 × 10 mm</td></tr>
      <tr><td>開孔尺寸</td>      <td>580 × 580 mm（嵌入式）</td></tr>
      <tr><td>重量</td>          <td>2.1 kg</td></tr>
      <tr><td>外殼材質</td>      <td>鋁合金框架 + 導光板</td></tr>
      <tr><td>防護等級</td>      <td>IP40</td></tr>
      <tr><td>工作溫度</td>      <td>-20°C ～ +45°C</td></tr>
      <tr><td>使用壽命</td>      <td>50,000 小時（L70B50）</td></tr>
      <tr><td>保固年限</td>      <td>5 年</td></tr>
      <tr><td>安規認證</td>      <td>CE、RoHS、CNS 15630</td></tr>
    </tbody>
  </table>
</section>

<!-- ══════════════════════════════════════════════
     SECTION 2：產品特色
     條列式 → Featured Snippet 最佳化
     ══════════════════════════════════════════════ -->
<section class="section-card" aria-labelledby="features-heading">
  <h2 class="section-title" id="features-heading">六大產品優勢</h2>
  <ul class="feature-list">
    <li>
      <strong>極高光效 130 lm/W</strong>——相比傳統 T8 日光燈（約 70–80 lm/W），相同亮度耗電減少 50% 以上，每年為每盞燈節省約 NT$ 600–900 電費。
    </li>
    <li>
      <strong>超長壽命 50,000 小時</strong>——每日使用 10 小時可超過 13 年不需更換，大幅降低維護人力與燈具汰換成本。
    </li>
    <li>
      <strong>無頻閃、護眼設計</strong>——採用恆流驅動，頻閃指數（SVM）&lt; 0.4，符合 IEEE 1789-2015 護眼標準，適合長時間辦公環境。
    </li>
    <li>
      <strong>即時全亮、無需預熱</strong>——開燈即達 100% 亮度，不像傳統燈管需要 30 秒暖機，提升使用便利性。
    </li>
    <li>
      <strong>標準 600×600mm 免改裝</strong>——直接嵌入現有輕鋼架天花板，無需額外改裝工程，適合大面積辦公室快速換裝。
    </li>
    <li>
      <strong>三重國際認證、5 年保固</strong>——通過 CE、RoHS、CNS 15630 認證，原廠 5 年零件與人工保固，企業採購後顧無憂。
    </li>
  </ul>
</section>

<!-- ══════════════════════════════════════════════
     SECTION 3：適用場景
     語意豐富、長尾關鍵字覆蓋
     ══════════════════════════════════════════════ -->
<section class="section-card" aria-labelledby="usecase-heading">
  <h2 class="section-title" id="usecase-heading">適用場景</h2>
  <p style="font-size:.95rem;line-height:1.8;color:var(--text);">
    Winstartek WST-P60-40W LED 平板燈廣泛應用於以下商業與公共空間：
  </p>
  <ul class="feature-list" style="margin-top:12px;">
    <li><strong>辦公室、開放式工作區</strong>——均勻無眩光照明，提升員工專注力與生產效率</li>
    <li><strong>會議室、簡報空間</strong>——自然白 4000K 呈現文件與簡報色彩真實感</li>
    <li><strong>學校教室、圖書館</strong>——護眼低頻閃，符合教育部教室照明標準</li>
    <li><strong>醫療診所、藥局</strong>——高演色性 CRI ≥ Ra80，準確呈現膚色與藥品顏色</li>
    <li><strong>商業零售、展示空間</strong>——均勻照明提升商品視覺吸引力</li>
    <li><strong>政府機關、公共建築</strong>——符合節能法規，通過 CNS 認證可申請節能補助</li>
  </ul>
</section>

<!-- ══════════════════════════════════════════════
     SECTION 4：客戶評價
     User-generated content → SEO 新鮮度 + 信任度
     ══════════════════════════════════════════════ -->
<section class="section-card" aria-labelledby="reviews-heading">
  <h2 class="section-title" id="reviews-heading">客戶評價</h2>

  <div class="review-card" itemprop="review" itemscope itemtype="https://schema.org/Review">
    <div class="review-meta">
      <span class="reviewer-name" itemprop="author">陳先生・台北辦公室設計師</span>
      <span class="review-date" itemprop="datePublished">2024-11-15</span>
    </div>
    <div class="review-stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
      <meta itemprop="ratingValue" content="5">★★★★★
    </div>
    <p class="review-text" itemprop="reviewBody">
      安裝簡便，光線均勻不刺眼，整體辦公室亮度提升很多。比舊燈省了將近 60% 的電費，半年內就回收了設備成本，非常推薦。
    </p>
  </div>

  <div class="review-card" itemprop="review" itemscope itemtype="https://schema.org/Review">
    <div class="review-meta">
      <span class="reviewer-name" itemprop="author">王小姐・連鎖餐飲採購主管</span>
      <span class="review-date" itemprop="datePublished">2025-01-08</span>
    </div>
    <div class="review-stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
      <meta itemprop="ratingValue" content="5">★★★★★
    </div>
    <p class="review-text" itemprop="reviewBody">
      一次採購 200 片，品質非常穩定，每一片亮度都很一致。Winstartek 售後服務到位，五年保固讓公司採購部門很放心。
    </p>
  </div>

  <div class="review-card" itemprop="review" itemscope itemtype="https://schema.org/Review">
    <div class="review-meta">
      <span class="reviewer-name" itemprop="author">林先生・學校總務主任</span>
      <span class="review-date" itemprop="datePublished">2025-03-20</span>
    </div>
    <div class="review-stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
      <meta itemprop="ratingValue" content="5">★★★★★
    </div>
    <p class="review-text" itemprop="reviewBody">
      全校 40 間教室換裝，學生和老師反映比以前舒適很多，眼睛不容易疲勞。通過 CNS 認證還幫我們申請到政府節能補助，CP 值非常高。
    </p>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     SECTION 5：FAQ
     Google PAA + AI 問答引用 最佳化
     ══════════════════════════════════════════════ -->
<section class="section-card" aria-labelledby="faq-heading">
  <h2 class="section-title" id="faq-heading">常見問題 FAQ</h2>
  <ul class="faq-list" role="list">

    <li class="faq-item">
      <button class="faq-question" aria-expanded="false" aria-controls="faq-1">
        Winstartek WST-P60-40W 的使用壽命是多久？
      </button>
      <div class="faq-answer" id="faq-1" role="region">
        WST-P60-40W 設計壽命為 <strong>50,000 小時（L70B50 標準）</strong>。以每天使用 10 小時計算，可持續使用超過 13 年，大幅降低更換頻率與維護成本，並享有 5 年原廠保固。
      </div>
    </li>

    <li class="faq-item">
      <button class="faq-question" aria-expanded="false" aria-controls="faq-2">
        比傳統日光燈省多少電？
      </button>
      <div class="faq-answer" id="faq-2" role="region">
        WST-P60-40W 功耗 40W，光通量 5,200 lm，光效達 130 lm/W。相比傳統 T8 日光燈系統（約 80–100W），<strong>節電 50–60%</strong>。以每日 10 小時、電費 NT$4/度計算，每年每盞省約 NT$876。
      </div>
    </li>

    <li class="faq-item">
      <button class="faq-question" aria-expanded="false" aria-controls="faq-3">
        適合哪些空間安裝？
      </button>
      <div class="faq-answer" id="faq-3" role="region">
        適合<strong>辦公室、會議室、學校教室、醫療診所、商業零售、政府機關</strong>等室內空間。600×600mm 標準規格可直接嵌入輕鋼架天花板，無需改裝。
      </div>
    </li>

    <li class="faq-item">
      <button class="faq-question" aria-expanded="false" aria-controls="faq-4">
        有通過哪些安全認證？
      </button>
      <div class="faq-answer" id="faq-4" role="region">
        通過 <strong>CE（歐盟安全）、RoHS（無毒有害物質限制）、CNS 15630（台灣國家標準）</strong> 三項認證，可申請政府節能補助，符合企業 ESG 節能要求。
      </div>
    </li>

    <li class="faq-item">
      <button class="faq-question" aria-expanded="false" aria-controls="faq-5">
        可以大量採購嗎？有提供樣品嗎？
      </button>
      <div class="faq-answer" id="faq-5" role="region">
        提供<strong>企業大量採購優惠方案</strong>，10 片以上可享批量折扣，並提供免費樣品供工程評估。請填寫詢價表單或電洽業務團隊，通常 1 個工作天內回覆。
      </div>
    </li>

  </ul>
</section>

<!-- ══════════════════════════════════════════════
     SECTION 6：相關產品（內部連結 → SEO）
     ══════════════════════════════════════════════ -->
<section class="section-card" aria-labelledby="related-heading">
  <h2 class="section-title" id="related-heading">相關產品</h2>
  <div class="related-grid">
    <a class="related-card" href="<?php echo esc_url(home_url('/products/wst-p60-30w/')); ?>">
      <img src="<?php echo esc_url(get_theme_file_uri('assets/images/wst-p60-placeholder.svg')); ?>"
           alt="WST-P60-30W LED 平板燈 30W 省電款" loading="lazy" width="300" height="300">
      <div class="related-info">
        <p class="related-name">WST-P60-30W LED 平板燈</p>
        <p class="related-sub">30W・3,900 lm・小坪數首選</p>
      </div>
    </a>
    <a class="related-card" href="<?php echo esc_url(home_url('/products/wst-dl-15w/')); ?>">
      <img src="<?php echo esc_url(get_theme_file_uri('assets/images/wst-p60-placeholder.svg')); ?>"
           alt="WST-DL-15W LED 崁燈 15W 筒燈" loading="lazy" width="300" height="300">
      <div class="related-info">
        <p class="related-name">WST-DL-15W LED 崁燈</p>
        <p class="related-sub">15W・局部照明・多色溫</p>
      </div>
    </a>
    <a class="related-card" href="<?php echo esc_url(home_url('/products/wst-tri-40w/')); ?>">
      <img src="<?php echo esc_url(get_theme_file_uri('assets/images/wst-p60-placeholder.svg')); ?>"
           alt="WST-TRI-40W LED 三防燈 IP65 防水防塵" loading="lazy" width="300" height="300">
      <div class="related-info">
        <p class="related-name">WST-TRI-40W 三防燈</p>
        <p class="related-sub">40W・IP65 防水・廠房專用</p>
      </div>
    </a>
    <a class="related-card" href="<?php echo esc_url(home_url('/products/wst-smart-40w/')); ?>">
      <img src="<?php echo esc_url(get_theme_file_uri('assets/images/wst-p60-placeholder.svg')); ?>"
           alt="WST-SMART-40W 智慧 LED 平板燈 可調光調色" loading="lazy" width="300" height="300">
      <div class="related-info">
        <p class="related-name">WST-SMART-40W 智慧燈</p>
        <p class="related-sub">40W・可調光調色・APP 控制</p>
      </div>
    </a>
  </div>
</section>

<!-- 底部間距（避免被 sticky CTA 遮住） -->
<div class="product-spacer" aria-hidden="true"></div>

<!-- ══════════════════════════════════════════════
     STICKY CTA BAR（手機固定詢價按鈕）
     ══════════════════════════════════════════════ -->
<div class="sticky-cta" role="complementary" aria-label="快速詢價">
  <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn-primary">立即詢價</a>
  <a href="tel:+886212345678" class="btn-outline">📞 電話洽詢</a>
</div>

<?php get_footer(); ?>
