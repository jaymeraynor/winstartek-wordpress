# Product schema consolidation — phase 1 (2026-11-24)

Approved by Roy 2026-11-24 01:37 (Asia/Taipei). Live at 01:37; Varnish purged by Roy 02:15.

- Product schema mu-plugins consolidated (plan D, option B): all `Offer` objects removed.
  `winstartek-product-schema-offers.php` keeps an Offer-free stub for pages with no main
  product data (filter `winstartek_output_product_stub` disables it).
- `winstartek-standard-product-system.php` rebased on the 2026-07-16 repo version.
- `ws-product-top-standard-v3.php`: BOM removed.
- `ws-product-top-standard.php` / `-v2.php` renamed `.disabled-20261124-0137` on live (not deleted).
- Verification: `verify_schema.py` fresh 12/12 OK, cached 12/12 OK after purge.
- Live-file backups kept in the private project workspace, per BACKUP_WORKFLOW.md (not in this public repo).
- Expected: Search Console may report missing offers/review/rating on Product rich results.

Phase 2 (not started): Rank Math Article/WebPage on /products/ pages, /en/ singles without product data,
front-end DB writes, remaining 07-16 vs live differences (see CHANGES-optionB.md).
