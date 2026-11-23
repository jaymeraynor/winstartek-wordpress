#!/usr/bin/env python3
"""winstartek.com 產品頁 JSON-LD 驗證（唯讀，只用 curl 抓公開頁）。

用法：
  python3 verify_schema.py                 # 跑預設 12 頁
  python3 verify_schema.py --all           # 預設 12 頁 + urls.txt 全部 47 個單品網址（普查）
  python3 verify_schema.py URL ...         # 只看指定網址（自動判斷 detail/listing）
  python3 verify_schema.py --html FILE ... # 驗證本地已存的 HTML（離線；kind 依檔名含 cat-/listing 判斷）
  python3 verify_schema.py --cached [...]  # 不加 query 參數，看訪客/Googlebot 實際拿到的快取版（清快取後用）

整併後判準（2026-11-24，Roy 選乙方案：全站產品 schema 不含 Offer）：
  單品頁（detail）
    - 不得出現 Offer / AggregateOffer（任何層級，含 hasVariant 變體）
    - 主 Product 恰 1 份：頂層（script 根或 @graph 第一層）的 Product/ProductGroup 節點合計 = 1
      （hasVariant 裡的變體不算；沒有其他產品資料的頁面由 offers 外掛的無 Offer stub 補這 1 份）
    - BreadcrumbList 恰 1 份
    - 不得有 CollectionPage（含空 ItemList 的 CollectionPage）
    - 同一 Product/ProductGroup @id 不得出現在兩個不同 script
  列表/分類頁（listing）
    - CollectionPage 恰 1、BreadcrumbList 恰 1、無 Product/ProductGroup、無 Offer
  全部：回應開頭不得有 UTF-8 BOM
結束碼：全部 OK=0，有任何 NG=1。
"""
import collections, json, os, re, subprocess, sys

BASE = "https://winstartek.com"
HERE = os.path.dirname(os.path.abspath(__file__))
DEFAULT = {
    "/products/special-application/uvc-sterilization-device/": "detail",
    "/products/indoor-and-commercial-lighting/led-linear-lamp/": "detail",
    "/products/special-application/ward-light/": "detail",
    "/products/indoor-and-commercial-lighting/panel-light/": "detail",
    "/products/indoor-and-commercial-lighting/led-tube-troffer-light/": "detail",
    "/products/indoor-and-commercial-lighting/led-linkable-batten-light/": "detail",
    "/products/outdoor-lighting/street-light/": "detail",
    "/en/products/indoor-and-commercial-lighting/panel-light/": "detail",
    "/product/re-20/": "detail",
    "/products/indoor-and-commercial-lighting/": "listing",
    "/en/products/special-application/": "listing",
    "/products/": "listing",
}
LISTING_TAILS = ('products', 'lighting', 'application', 'source', 'project', 'system', 'label')
OFFER_TYPES = {"Offer", "AggregateOffer"}
PRODUCT_TYPES = {"Product", "ProductGroup"}


def guess_kind(path):
    p = re.sub(r'^https?://[^/]+', '', path).strip('/')
    p = re.sub(r'^(en|ar|de|hi|id|it|ja|ko|pt-br|es|th|vi)/', '', p)
    return "listing" if (p == 'products' or (p.startswith('products/') and p.count('/') == 1 and p.endswith(LISTING_TAILS))) else "detail"


CACHED = False


def fetch(url):
    # 預設加 query 參數避開 Cloudflare/頁面快取，確保看到新版輸出；--cached 時不加
    sep = '&' if '?' in url else '?'
    target = url if CACHED else url + sep + "schema_verify=1"
    r = subprocess.run(["curl", "-sL", "--max-time", "60", "-A", "Mozilla/5.0 schema-verify", target],
                       capture_output=True)
    return r.stdout


def types_of(node):
    t = node.get("@type") if isinstance(node, dict) else None
    return [t] if isinstance(t, str) else [x for x in (t or []) if isinstance(x, str)]


def walk(node, counter, ids, src):
    if isinstance(node, dict):
        ts = types_of(node)
        for x in ts:
            counter[x] += 1
        if node.get("@id") and set(ts) & PRODUCT_TYPES:
            ids[node["@id"]].add(src)
        for v in node.values():
            walk(v, counter, ids, src)
    elif isinstance(node, list):
        for v in node:
            walk(v, counter, ids, src)


def top_nodes(d):
    if isinstance(d, dict):
        if isinstance(d.get("@graph"), list):
            return [g for g in d["@graph"] if isinstance(g, dict)]
        return [d]
    if isinstance(d, list):
        return sum((top_nodes(x) for x in d), [])
    return []


def analyse(raw, label, kind):
    bom = raw.startswith(b"\xef\xbb\xbf")
    html = raw.decode("utf-8", "replace")
    total = collections.Counter()
    ids = collections.defaultdict(set)
    top_products, scripts = 0, []
    for i, m in enumerate(re.finditer(r'<script\b([^>]*application/ld\+json[^>]*)>(.*?)</script>', html, re.S | re.I)):
        attrs = m.group(1).replace('type="application/ld+json"', "").strip()
        src = re.search(r'(?:class|id|data-[a-z-]+)="?([a-z0-9-]+)', attrs)
        src = src.group(1) if src else f"script{i}"
        body = m.group(2).strip()
        try:
            d = json.loads(body)
        except Exception:
            try:
                import html as _h
                d = json.loads(_h.unescape(body))
            except Exception:
                scripts.append(f"{src}(PARSE-ERROR)")
                continue
        tops = ["+".join(types_of(n)) for n in top_nodes(d) if types_of(n)]
        top_products += sum(1 for n in top_nodes(d) if set(types_of(n)) & PRODUCT_TYPES)
        c = collections.Counter()
        walk(d, c, ids, src)
        total += c
        scripts.append(f"{src}:{'/'.join(tops)}")

    problems = []
    if bom:
        problems.append("BOM")
    offers = sum(total.get(t, 0) for t in OFFER_TYPES)
    if offers:
        problems.append(f"Offer/AggregateOffer={offers}")
    if kind == "detail":
        if top_products != 1:
            problems.append(f"主Product={top_products}")
        if total.get("BreadcrumbList", 0) != 1:
            problems.append(f"BreadcrumbList={total.get('BreadcrumbList', 0)}")
        if total.get("CollectionPage", 0):
            problems.append(f"單品頁有CollectionPage={total.get('CollectionPage', 0)}")
    else:
        if total.get("CollectionPage", 0) != 1:
            problems.append(f"CollectionPage={total.get('CollectionPage', 0)}")
        if total.get("BreadcrumbList", 0) != 1:
            problems.append(f"BreadcrumbList={total.get('BreadcrumbList', 0)}")
        if top_products or total.get("Product", 0) or total.get("ProductGroup", 0):
            problems.append("列表頁有Product")
    dup_ids = [k for k, v in ids.items() if len(v) > 1]
    if dup_ids:
        problems.append(f"同@id跨script:{dup_ids}")

    keys = ["Product", "ProductGroup", "Offer", "AggregateOffer", "BreadcrumbList", "FAQPage", "CollectionPage", "ItemList", "WebPage", "Article"]
    counts = " ".join(f"{k}={total.get(k, 0)}" for k in keys if total.get(k, 0))
    status = "OK " if not problems else "NG "
    print(f"{status}[{kind}] {label}\n    scripts: {' | '.join(scripts) or '(none)'}\n    counts: 主Product={top_products} {counts}"
          + (f"\n    問題: {'; '.join(problems)}" if problems else ""))
    return not problems


def check(path, kind):
    url = BASE + path if path.startswith("/") else path
    return analyse(fetch(url), path, kind)


if __name__ == "__main__":
    args = sys.argv[1:]
    if args and args[0] == "--cached":
        CACHED = True
        args = args[1:]
    if args and args[0] == "--html":
        files = args[1:]
        ok = all([analyse(open(f, "rb").read(), f, "listing" if re.search(r'(^|/)(cat-|listing|products\.html)', f) else "detail") for f in files])
        sys.exit(0 if ok else 1)
    if args and args[0] == "--all":
        targets = dict(DEFAULT)
        with open(os.path.join(HERE, "urls.txt"), encoding="utf-8") as fh:
            for line in fh:
                u = line.strip()
                if u:
                    targets.setdefault(re.sub(r'^https?://[^/]+', '', u), guess_kind(u))
    elif args:
        targets = {u: guess_kind(u) for u in args}
    else:
        targets = DEFAULT
    results = [check(p, k) for p, k in targets.items()]
    print(f"\n合計 {len(results)} 頁：OK {sum(results)}、NG {len(results) - sum(results)}")
    sys.exit(0 if all(results) else 1)
