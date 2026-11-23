# 第一階段修訂（乙方案：拿掉全部 Offer）— 2026-11-24 程式專員

只改了沙箱。改後檔案在 `work/schema/proposed/`，對線上備份的 diff 在 `work/schema/diffs/`。上一版（A 方案，保留無價 Offer）留在 `proposed-v1-offerA/` 和 `diffs-v1-offerA/`。

## 各檔改了什麼
- **winstartek-product-schema-offers.php**（v2.0.0）
  - 不再輸出 Offer。stub 裡的 `offers` 拿掉了；`rank_math/json_ld` 和內文 `ws-product-schema` 的處理從「補 offers」改成「拿掉 Product/ProductGroup（含變體）上的 offers」，對 Offer 和 AggregateOffer 都一樣。
  - 內文 `ws-product-schema` 帶的 BreadcrumbList 會拿掉。同一支函式也掛在 `elementor/frontend/the_content`，重複執行不會有副作用（冪等）。
  - 舊版遇到 ProductGroup 沒有變體時會自動補一個 stub 變體，這個行為已取消（不新增任何節點）。
  - 新增 `winstartek_page_has_primary_product_schema()`。頁面符合以下任一條件就不印 stub：有外掛透過 `winstartek_has_primary_product_schema` 宣告、`ws_spt_has_data()` 為真、post_content 或 `_elementor_data` 裡有 `ws-product-schema`。
  - stub 的 name 會先解碼 HTML entity。另加開關 `winstartek_output_product_stub`，預設 true。
  - 相容層：`winstartek_add_offers_to_schema_node()` 保留，但行為改成拿掉 offers；`winstartek_schema_offer()` 保留但已不呼叫。這樣沒備份到的 theme 如果直接呼叫這兩支，也不會 fatal。
  - `wp` hook 的 DB 清理和 sitemap 處理維持原樣（屬第二階段）。
- **winstartek-standard-product-system.php**：以 repo 07-16 版為底，拿掉 AggregateOffer，詳細見 `diffs/*.vs-repo0716.diff`。
- **winstartek-product-category-schema.php**：ItemList 是空的就不輸出 CollectionPage（跟上一版相同）。
- **ws-linear-lamp-product.php**：只加上宣告主產品的 filter。上一版的 offers 呼叫已拿掉。
- **ws-ward-light-schema.php**：加了 ABSPATH 保護和宣告主產品的 filter。上一版的 offers 呼叫已拿掉。
- **ws-product-top-standard-v3.php**：只拿掉 BOM。
- **v1/v2**：上線時改名成 `.disabled-<TS>`，見 `diffs/ws-product-top-standard-v1-v2.RENAME.txt`。

## standard-product-system：07-16 版和線上版（09-21）怎麼處理
- 07-16 有、線上沒有：
  - AggregateOffer：依乙方案拿掉。
  - 「沒有 offers/review/aggregateRating 的 Product 不輸出」過濾：程式碼保留，但預設條件改成「沒有 name 的產品節點才濾掉」。原因是照原條件，乙方案下 ProductGroup 會整個被濾掉。原條件放在開關 `ws_spt_require_offer_like_property` 後面，打開就恢復 07-16 的行為。
  - `ws_spt_is_elementor_editor_request()`：保留，但它沒被任何地方呼叫，沒有作用。
  - 兩個 the_content 對 `_ws_elementor_product_page === '1'` 的跳過條件：**沒有採用，維持線上行為**。repo 裡的備份 JSON 顯示可串接支架燈、教室燈、山型燈、病房燈等頁的這個 meta 都是 '1'，套回去這些頁就不會再套標準模板，版面會變。
- 線上有、07-16 沒有（09-21 之後加的），保留：
  1. CSS 兩行，用來隱藏 Elementor 範本 17560 的 `.elementor-element-581da60` 和 `.elementor-element-069429a`。
  2. 上面說的「不因 _ws_elementor_product_page 跳過標準模板」。
- 新增：註冊 `winstartek_has_primary_product_schema`，條件是 is_singular('product') 且 ws_spt_has_data。

## stub 的判斷
保留不含 Offer 的 stub，只給沒有其他產品資料的頁面用；需要時可以用 filter 一行關掉。理由：
- Google 的 product snippet 規定 offers、review、aggregateRating 至少要有一個（https://developers.google.com/search/docs/appearance/structured-data/product-snippet ）。乙方案下，有主產品資料的 6 頁本來就沒有這三樣，所以 stub 留著或關掉，Rich Result 的結果都一樣：都不會有產品複合式結果，Search Console 也一樣會標「需指定 offers/review/aggregateRating 其一」。
- stub 留著，約 40 頁（page 類型單品 31 頁、/product/* 舊型號、/en/ 單品）還能保有一個 Product 實體（品牌、製造商、圖片），有助 Google、Bing 和 AI 搜尋辨識「這是產品頁」。這幾項 Google 都不拿來排名，也不會受罰。
- 代價：Search Console 產品摘要報告裡的「無效項目」會變多（不影響排名），而且 stub 的 sku 是網址 slug，不是真正的型號。如果 Roy 比較在意報告乾淨，可以加 `add_filter('winstartek_output_product_stub','__return_false');`。

## 檢查結果
- 6 支檔案 `php -l`（PHP 8.4.28）全部 No syntax errors。
- mock WP harness（`harness/`）用 8 個情境測：線上原檔重現了重複 Product、Offer、BOM；改後版本 8 個情境都是 Offer=0、主 Product=1、Breadcrumb=1、沒有 BOM。
- 另把線上全部 19 支 mu-plugin（v1/v2 停用，其中 6 支換成新版）一起載入，沒有 fatal，也沒有函式重複宣告。repo 和備份都沒有其他程式呼叫 offers 外掛的函式。
- `verify_schema.py` 判準已更新。對 harness 輸出跑 8 頁全部 OK；對現在的線上站（`baseline-verify-optionB.txt`）跑是 12 頁全部 NG，也就是上線前的狀態。
