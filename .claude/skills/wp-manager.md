---
name: wp-manager
description: Use when managing WordPress sites with the local pawa-wp-mcp server for content, health, FTP, and inspection tasks.
---

# WordPress Site Manager

Use the `pawa-wp-mcp` MCP server for WordPress work in this workspace.

## Content Folder

Site content, brand assets, and rules live in `content/<site-alias>/` at the workspace root. Always check here before building or editing any site.

```
content/
└── <site-alias>/
    ├── RULES.md           ← hard content rules — READ THIS FIRST
    ├── profile.md         ← org identity, contacts, social
    ├── brand/
    │   ├── logos/         ← logo files
    │   ├── colors/        ← brand palette
    │   ├── typography/    ← font choices
    │   └── theme-handoff.md  ← Kirki theme mod keys + apply method
    ├── pages/             ← one file per page (brief + WP ID)
    ├── posts/             ← post content
    ├── menus/             ← menu structure
    └── media/             ← media manifest
```

- Always read `RULES.md` before creating or editing content for a site
- Cross-reference page files for WP IDs when updating existing pages
- Check `brand/theme-handoff.md` before applying any visual or colour changes
- If `RULES.md` lists required pages, verify all exist before marking work complete

## Rules

1. Always use site aliases from `sites.json`, never raw URLs.
2. Inspect with `wp_inspect` before giving visual design advice.
3. Use `wp_audit` before debugging broken pages.
4. Confirm before any create, update, delete, upload, or delete operation.

## ☠️ Elementor Iron Rules

**These rules exist because bulk Elementor edits have caused live site whiteouts. They are not optional and override pre-approval.**

1. **Snapshot before every edit.** Call `wp_get_post` and record `_elementor_data` before any `wp_update_elementor_data` call. You need this to recover if the edit corrupts the JSON.

2. **One change at a time.** Never pass more than 1–2 replacements per `wp_update_elementor_data` call. Run `wp_inspect` after each call to confirm the page still loads before continuing.

3. **Never bulk search-replace Elementor JSON.** Passing 5+ replacements at once risks injecting unescaped characters that break the JSON structure entirely. The page goes blank. Recovery requires database restoration.

4. **Never edit `post_content` on Elementor pages.** If `_elementor_edit_mode = builder`, the real content is in `_elementor_data`. Overwriting `post_content` does not affect what Elementor renders — it only destroys the fallback.

5. **Stop immediately on blank page.** If `wp_inspect` returns a spinner or blank screen after an Elementor edit, stop all further edits. Restore from the snapshot. Do not attempt more replacements to fix broken JSON.

## Tools

- Content: `wp_get_posts`, `wp_get_post`, `wp_create_post`, `wp_update_post`, `wp_delete_post`, `wp_get_media`
- Health: `wp_get_plugins`, `wp_get_theme`, `wp_get_site_info`, `wp_get_users`
- FTP: `ftp_list`, `ftp_read`, `ftp_upload`, `ftp_delete`
- Inspect: `wp_inspect`, `wp_audit`, `wp_compare`

## Updating Theme Options (set_theme_mod)

`wp_update_settings` only handles WordPress **core settings** (blogname, front page, etc.). It does NOT update theme customizer options (Kirki, theme mods).

Use this priority order when updating `set_theme_mod()` values:

| Priority | Method | Notes |
|---|---|---|
| 1 | **WP Admin Customizer** | Manual: `/wp-admin/customize.php` |
| 2 | **WP-CLI** (SSH) | `wp theme mod set <key> <value> --path=<wp-root>` |
| 3 | **FTP + PHP file** ✅ confirmed | Always works — no SSH needed |
| ❌ | `/customize/v1/settings` REST | Not available by default |
| ❌ | `/wp/v2/settings` REST | Does not expose theme mods |

**FTP + PHP file (Method 3) — step by step:**

1. Write PHP locally:
```php
<?php
require('/home1/<user>/public_html/wp-load.php'); // filesystem path, not FTP path
set_theme_mod('key', 'value');
echo json_encode(['done' => true]);
```
2. `ftp_upload` to site root as `/apply-theme.php`
3. `curl https://<site>/apply-theme.php` to execute
4. `ftp_delete` the file immediately after

**Finding the server filesystem path:**
- cPanel shared: `/home/<user>/public_html` or `/home1/<user>/public_html`
- VPS: `/var/www/html`
- Auto-detect: upload `<?php echo __DIR__;` and call it — it returns the real path
