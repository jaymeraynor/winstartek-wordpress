# Dev / ops scripts for winstartek.com

These are CLI maintenance and audit scripts run against the WordPress DB
directly via `SHORTINIT`. They expect `wp-load.php` at `__DIR__/../wp-load.php`.

## Run

All scripts are CLI-only. From this folder:

```bash
PHP="/c/Users/alroy/AppData/Roaming/Local/lightning-services/php-8.0.30+0/bin/win64/php.exe"
"$PHP" -c ../../../_cli_php.ini <script>.php          # dry
"$PHP" -c ../../../_cli_php.ini <script>.php --apply  # write
```

(The `_cli_php.ini` and `_secrets.json` files live in the Local site root,
one level above `app/`. They are NOT in git.)

## Categories

### Audit / read-only
- `_ws_audit.php` — full DB + plugin audit
- `_ws_widget_usage.php` — count Elementor widget usage by plugin
- `_ws_qa.php` — 5 QA checks (mojibake / images / Google code / translation / SEO)
- `_check_*.php` — narrow inspection helpers

### Cleanup (writes)
- `_ws_clean_a.php` — drop ast-block-templates, _elementor_controls_usage, transients, etc.
- `_ws_clean_b.php` — WPML dangling rows
- `_ws_check_leftover.php` — sweep leftover options after plugin folder delete

### Translation infrastructure
- `ws_translate_elementor.php` — DeepL translate `_elementor_data` per page (with ZH unit dict)
- `ws_translate.php` — DeepL translate WPML strings
- `_ws_fix_year_suffix.php` — one-off: fix "28年" mistranslated as surname Nian
- `_ws_fix_image_urls.php` — strip i0.wp.com/winstartek.local from image URLs

### Multilingual structure
- `_ws_clone_hfe.php` — clone EN HFE templates to 11 missing languages
- `_ws_clone_menu.php` — clone EN Primary Menu (10 items) per language
- `_ws_relink_menu_items.php` — point cloned menu items at language-equivalent pages
- `_ws_clone_categories.php` — create News/Knowledge/Latest categories per language + tag posts

### Plugin lifecycle
- `_ws_deactivate_zero.php` — deactivate plugins with 0 usage
- `_ws_reactivate.php` — re-activate after recovery
