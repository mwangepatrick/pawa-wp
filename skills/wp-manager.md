---
name: wp-manager
description: Use when managing WordPress sites - provides guidance on using pawa-wp-mcp tools for content management, health checks, file operations, and design analysis. Trigger when user mentions a WordPress site, asks to inspect/update/create content, or wants to audit a site.
---

# WordPress Site Manager

You have access to the `pawa-wp-mcp` MCP server which manages WordPress sites via REST API, FTP, and Playwright.

## Behavioral Rules

1. **Always use site aliases** — the `site` parameter is always the short alias from `sites.json` (e.g. `"mysite"`), never a raw URL
2. **Inspect before advising** — when asked about any visual or design issue, call `wp_inspect` first to get a live screenshot before responding
3. **Audit before debugging** — for "something looks broken" reports, call `wp_audit` first to check console errors and 4xx/5xx responses before theorizing
4. **Fan out health checks** — when checking updates across all sites, call `wp_get_plugins` in parallel for all known site aliases
5. **Confirm before modifying** — before calling `wp_create_post`, `wp_update_post`, `wp_delete_post`, `ftp_upload`, or `ftp_delete`, confirm the target site and intended action with the user
6. **Detect Elementor before editing** — call `wp_get_plugins` and check for `elementor/elementor.php` in the active plugin list before any content edit. If active, never use `wp_update_post`; always use the Elementor workflow below
7. **Disable server cache before a multi-step edit session** — call `wp_disable_server_cache` at the start, then `wp_enable_server_cache` when done
8. **Never use wp_inspect screenshots to verify content changes** — screenshots may show a stale Elementor or server-cached render even when the database is already correct. Always verify by HTTP GET + string check on the live URL instead
9. **MCP server must be restarted after adding new tools** — newly added tools are invisible to the current Claude session until the MCP server is restarted (Claude Code → Settings → MCP). If a tool was just added and doesn't appear, restart before assuming it's broken
10. **Confirm stats and numbers with the user before committing** — template sites often carry placeholder stats (e.g. "3k+ Global Partners", "93+ Projects Complete") that look credible but are fake. Always present proposed numbers to the user for approval; never invent replacements

## Template Placeholder Detection

Before proposing any edits, scan the fetched page content for signs of placeholder data:

- **Addresses** in a country that doesn't match the organisation (e.g. a Kenyan CBO with a Texas address)
- **Emails** from generic domains (`@disasterelief.com`, `@charity.com`, `@helpy.vikinglab.agency`)
- **Phone numbers** in `+00 123 456 789` format
- **Stats** that are wildly out of scale (e.g. "3k+ Global Partners" for a village CBO)
- **Image URLs** pointing to a theme agency's demo server (e.g. `vikinglab.agency`)

When these are present, treat the **entire page** as a template that needs a full content pass, not a partial edit. Surface the full list of placeholders to the user before touching anything.

For organisational facts (stats, founding year, member count), always present the proposed values to the user for confirmation before applying them. Never invent numbers.

---

## Content-from-Document Workflow

When the user provides source documents (PDF, constitution, brief) to populate a page:

1. Extract all factual fields: name, location, mission, vision, objectives, contacts, year founded
2. Map each extracted fact to a specific widget ID on the page
3. Present the mapping as a table ("this widget currently says X → will become Y") for user review
4. Only then apply the patches

This avoids incorrect assumptions and gives the user a chance to correct facts before they go live.

---

## Elementor Editing Workflow

`wp_update_post` writes to `post_content`. Elementor **ignores** `post_content` and renders from `_elementor_data` meta. Updating `post_content` has no visual effect on Elementor pages.

### Step 1 — Fetch and inspect the data

Fetch `_elementor_data` via REST with Python (do not rely on `wp_get_post` which doesn't return meta):

```python
import requests, base64, json

token = base64.b64encode(b"USER:APP_PASSWORD").decode()
headers = {"Authorization": f"Basic {token}"}
resp = requests.get(
    "https://SITE/wp-json/wp/v2/pages/PAGE_ID?context=edit&_fields=meta",
    headers=headers
)
ed = resp.json()["meta"]["_elementor_data"]
data = json.loads(ed)
```

If `json.loads` fails, the data likely contains a shortcode with unescaped quotes (e.g. `[contact-form-7 id="x" title="y"]`). Fix before parsing:

```python
ed_fixed = ed.replace('[contact-form-7 id="x" title="y"]',
                      '[contact-form-7 id=\\"x\\" title=\\"y\\"]')
```

### Step 2 — Find widgets by ID

Walk the nested `elements` tree. Text-bearing widget types: `heading`, `vl-heading`, `text-editor`, `icon-list`, `button`. Each node has an `"id"` and `"settings"` dict. Print the tree with a recursive walker to map widget IDs before modifying anything.

### Step 3 — Patch by widget ID (never search/replace)

Always target widgets directly by ID. **Do not use string search/replace on the raw JSON** — replacement order bugs silently corrupt text (a broad pattern matches inside a longer string you intended to replace later).

```python
def find_widget(nodes, wid):
    for n in nodes:
        if n["id"] == wid: return n
        found = find_widget(n.get("elements", []), wid)
        if found: return found

find_widget(data, "WIDGET_ID")["settings"]["title"] = "New text"
```

### Step 4 — Push the updated meta

```python
updated = json.dumps(data, ensure_ascii=False)
requests.post(
    "https://SITE/wp-json/wp/v2/pages/PAGE_ID",
    headers={**headers, "Content-Type": "application/json"},
    json={"meta": {"_elementor_data": updated}}
)
```

**Always fetch fresh data immediately before patching.** Never reuse a previously saved JSON file — it may predate earlier edits and will silently revert them.

### Step 5 — Regenerate rendered HTML via PHP

Updating `_elementor_data` via REST does NOT regenerate `post_content`. Elementor's `$document->save()` hook doesn't fire. The page will still render old content. Upload and execute a PHP script:

```php
<?php
require_once '/home1/ACCOUNT/public_html/wp-load.php';  // see path discovery below
$post_id = PAGE_ID;
\Elementor\Plugin::$instance->files_manager->clear_cache();
$doc = \Elementor\Plugin::$instance->documents->get($post_id);
$doc->save(['elements' => json_decode(get_post_meta($post_id, '_elementor_data', true), true)]);
echo "done";
@unlink(__FILE__);
?>
```

Upload to the web root and GET it:

```python
import ftplib, io, requests
ftp = ftplib.FTP("ftp.SITE"); ftp.login(USER, PASS); ftp.cwd(WP_FTP_ROOT)
ftp.storbinary("STOR _regen.php", io.BytesIO(php_code.encode()))
ftp.quit()
requests.get("https://SITE/_regen.php")
```

The script self-deletes on execution.

### wp_update_elementor_data / wp_clear_elementor_cache tools

Use these MCP tools when available — they wrap Steps 3–4 above. After calling either, still run the PHP regeneration step (Step 5), because the MCP tools do not trigger `$document->save()`.

---

## FTP Path Discovery

**The FTP path and the server PHP filesystem path are different.** FTP root `/` maps to the server's home directory (e.g. `/home1/ACCOUNT/`), and `wp-load.php` lives at `/home1/ACCOUNT/public_html/wp-load.php` — not at an FTP-relative path.

If FTP tools return empty results or the `ftp_root` in `sites.json` is suspected wrong, run this probe first:

```php
<?php
echo getcwd() . "\n";
echo __FILE__ . "\n";
foreach (['/www/wp-load.php', '/public_html/wp-load.php', '/home1/*/public_html/wp-load.php'] as $p)
    echo $p . ': ' . (file_exists($p) ? 'YES' : 'no') . "\n";
@unlink(__FILE__);
?>
```

Upload it via direct `ftplib` (bypassing the MCP FTP tools) and GET it. The output tells you both the correct `wp-load.php` path for PHP scripts and confirms the FTP working directory.

When `ftp_list` returns `[]` for a path that should have files, the `ftp_root` in `sites.json` is wrong. Fix it — don't work around it with hardcoded paths.

---

## Server Cache Awareness

Shared hosting often runs **LiteSpeed** caching at the server level. This is independent of any WordPress plugin and will serve stale HTML even after you've correctly updated the database.

**Symptoms:** REST API confirms the update, `_elementor_data` in the DB is correct, but the live page shows old content.

**Fix:** Call `wp_disable_server_cache` (prepends a cache-disable block to `.htaccess`) before any edit session. Call `wp_enable_server_cache` when done. Do this before testing whether content changes are visible.

Do not assume plugin-level cache clears (Elementor cache, LiteSpeed plugin) are sufficient — they don't affect server-level LiteSpeed caching.

### Cache diagnostic sequence

When changes don't appear on the live page, work through this in order:

1. **Is the database correct?** — Fetch `_elementor_data` via REST with `context=edit` and confirm the new text is in the JSON. If not, the write failed.
2. **Is Elementor rendering from `post_content` cache?** — Run the PHP regeneration script (Step 5 of Elementor workflow) to force `$document->save()`.
3. **Is a cache plugin serving a stale full-page cache?** — Run `wp_get_plugins`, filter for `litespeed-cache`, `w3-total-cache`, `wp-super-cache`, `wp-rocket`. Deactivate with `wp_toggle_plugin` for each.
4. **Is server-level LiteSpeed caching the page before PHP runs?** — Call `wp_disable_server_cache`. This is the last resort and bypasses everything above.

**Important:** `wp_update_post` and `wp_update_elementor_data` both return HTTP 200 on success. A 200 response does **not** mean the change is visible — it only means the write succeeded. Always verify with an HTTP GET + content string check, never by assuming a 200 means "done".

---

## Design Analysis

When you receive a screenshot from `wp_inspect` or `wp_audit`:
- Identify specific layout, typography, spacing, and contrast issues
- Suggest concrete CSS/theme fixes with actual property values — not vague advice like "improve spacing"
- If a staging URL is available, use `wp_compare` to diff against production before recommending changes

---

## Tool Reference

### Content
- `wp_get_posts` — list posts or pages (params: `site`, `type`, `status`, `limit`)
- `wp_get_post` — get single post/page with full content (params: `site`, `id`, `type`)
- `wp_create_post` — create post or page (params: `site`, `title`, `content`, `status`, `type`)
- `wp_update_post` — update title, content, or status — **not for Elementor pages** (params: `site`, `id`, `type`, `title?`, `content?`, `status?`)
- `wp_delete_post` — trash or force-delete (params: `site`, `id`, `type`, `force`)
- `wp_get_media` — list media library items (params: `site`, `limit`)

### Elementor
- `wp_update_elementor_data` — apply search/replace pairs to `_elementor_data` meta (params: `site`, `post_id`, `replacements`). Always follow with PHP regen (Step 5 above).
- `wp_clear_elementor_cache` — delete Elementor CSS cache files via FTP (params: `site`). Does not regenerate `post_content`.

### Site Management
- `wp_toggle_plugin` — activate or deactivate a plugin by slug (params: `site`, `plugin`, `status`)
- `wp_disable_server_cache` — prepend LiteSpeed/Apache cache-disable block to `.htaccess` (params: `site`)
- `wp_enable_server_cache` — remove the cache-disable block from `.htaccess` (params: `site`)

### Site Health
- `wp_get_plugins` — list plugins with update availability (params: `site`)
- `wp_get_theme` — get active theme name and version (params: `site`)
- `wp_get_site_info` — get site name, description, and URL (params: `site`)
- `wp_get_users` — list users with roles (params: `site`)

### File Operations
- `ftp_list` — list remote directory contents (params: `site`, `path`)
- `ftp_read` — read a remote text file (params: `site`, `path`)
- `ftp_upload` — upload a local file to a remote path (params: `site`, `local_path`, `remote_path`)
- `ftp_delete` — delete a remote file (params: `site`, `path`)

### Inspect & Audit
- `wp_inspect` — full-page screenshot of a URL (params: `url`)
- `wp_audit` — screenshot + console errors + 4xx/5xx broken links + performance timing (params: `url`)
- `wp_compare` — side-by-side screenshots of two URLs (params: `url_a`, `url_b`)
