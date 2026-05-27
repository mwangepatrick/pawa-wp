# Pawa Downloads — Design Spec

**Date:** 2026-05-27
**Status:** Approved (v2 — post-audit)
**Author:** Rahisi Solutions

---

## Overview

Repackage the `mwangaza-downloads` WordPress plugin into a generic, distributable plugin called **Pawa Downloads**, structured for eventual submission to the WordPress.org plugin repository.

The plugin lets site admins manage downloadable files (PDF, Word, Excel) via a custom post type and render them as a styled card grid using a shortcode.

---

## Plugin Identity

| Field           | Value                        |
|-----------------|------------------------------|
| Plugin Name     | Pawa Downloads               |
| Slug / folder   | `pawa-downloads`             |
| Text domain     | `pawa-downloads`             |
| Function prefix | `pawa_dl_`                   |
| Shortcode       | `[pawa_downloads]`           |
| CPT slug        | `pawa_download`              |
| Meta key        | `_pawa_dl_file_url`          |
| Version         | `1.0.0`                      |
| Author          | Rahisi Solutions             |
| Author URI      | https://rahisisolutions.com  |
| License         | GPL-2.0-or-later             |

The CPT slug is `pawa_download` (not `download`) to avoid conflicts with Easy Digital Downloads, WooCommerce, and other popular download plugins. The slug is 13 characters — within WordPress's 20-character CPT limit.

---

## File Structure

```
pawa-downloads/
├── pawa-downloads.php       ← plugin header, constants, loads includes/
├── readme.txt               ← WP.org listing file
├── uninstall.php            ← cleanup on plugin deletion
├── LICENSE                  ← GPL-2.0-or-later full text
├── includes/
│   ├── cpt.php              ← registers pawa_download CPT
│   ├── meta-box.php         ← file URL meta field + media library picker
│   ├── shortcode.php        ← [pawa_downloads] shortcode + card grid renderer
│   └── styles.php           ← enqueued frontend CSS
└── languages/
    └── pawa-downloads.pot   ← i18n translation template
```

---

## Architecture

### Main file (`pawa-downloads.php`)

Defines constants:

- `PAWA_DL_VERSION` — plugin version string
- `PAWA_DL_DIR` — absolute filesystem path to the plugin folder
- `PAWA_DL_URL` — web URL to the plugin folder (for enqueuing assets)

All four `includes/` files are required unconditionally at the bottom of the main file (not deferred to a hook). Each include is responsible for hooking its own functions to the correct WordPress actions.

The main file also:

- Calls `load_plugin_textdomain( 'pawa-downloads', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' )` on the `init` hook to activate translations.
- Registers an activation hook that aborts and deactivates the plugin with a friendly admin notice if PHP < 7.4 is detected.

Every PHP file in the plugin (main file and all includes) opens with:
```php
if ( ! defined( 'ABSPATH' ) ) exit;
```

### `includes/cpt.php`

Registers the `pawa_download` custom post type on the `init` hook:

- Labels fully translated via `__()` with text domain `pawa-downloads`
- `public: false`, `show_ui: true`, `show_in_rest: false`
- `show_in_rest: false` is intentional — the CPT uses the classic editor (Gutenberg requires REST). This is expected behaviour.
- Supports: `title`, `editor`
- `capability_type: 'post'` (default) — any user who can edit posts can manage downloads. Intentional; no custom roles defined in v1.
- Menu icon: `dashicons-download`, position 20
- `rewrite: false` (not a public-facing CPT)

### `includes/meta-box.php`

Adds a **Download File** meta box to the `pawa_download` edit screen:

- `wp_enqueue_media()` called on `admin_enqueue_scripts` (scoped to the `pawa_download` post type screen) to make `wp.media` available.
- Text input for the file URL
- **Choose File** button that opens the WP media library picker (JS via `wp.media`)
- Nonce verification on save (`pawa_dl_save_download` action)
- URL sanitised with `esc_url_raw()` only before storing — `sanitize_text_field()` is not used alongside it as it can corrupt percent-encoded characters in valid URLs
- Stored under meta key `_pawa_dl_file_url` (leading `_` hides it from the custom fields panel)
- Hooks: `add_meta_boxes`, `save_post_pawa_download`
- Save callback checks `current_user_can( 'edit_post', $post_id )` before writing

No server-side file-type validation is performed on the URL. Any URL can be stored. The card grid handles unknown extensions gracefully with a fallback icon. This is intentional for flexibility.

### `includes/shortcode.php`

Registers `[pawa_downloads]` shortcode with two optional attributes:

| Attribute | Default | Description |
|-----------|---------|-------------|
| `limit`   | `12`    | Maximum number of downloads to show (`-1` for all) |
| `order`   | `DESC`  | Sort direction: `ASC` or `DESC` by publish date |

Usage examples:
```
[pawa_downloads]
[pawa_downloads limit="6" order="ASC"]
[pawa_downloads limit="-1"]
```

Behaviour:

- Queries published `pawa_download` posts with the given `limit` and `order`
- Renders a `.pawa-dl-grid` card grid; each card contains:
  - SVG icon colour-coded by file extension (PDF=red, DOC/DOCX=blue, XLS/XLSX=green, fallback=grey)
  - Post title
  - Post content (description, via `wpautop`)
  - Download button linking to the file URL (or translatable "Coming soon" text if no URL set)
- All output escaped: `esc_html()` / `esc_url()` / `wp_kses_post()`
- Returns translatable empty-state message if no downloads are published
- All user-facing strings — including "Coming soon", "↓ Download", and the empty-state message — are wrapped in `__()` / `esc_html_e()` with text domain `pawa-downloads`

### `includes/styles.php`

Registers and enqueues a stylesheet using `wp_register_style()` + `wp_enqueue_style()` with handle `pawa-downloads`:

- Styles are inlined via `wp_add_inline_style()` (no separate `.css` file in v1; can be extracted in v2)
- Enqueued on `wp_enqueue_scripts` — **always loaded on the front end**, not conditionally
- Rationale: `has_shortcode()` checks `$post->post_content` only and fails silently when page builders (Elementor, Divi, Beaver Builder) store content in post meta. Since the stylesheet is small (~60 lines), always-loading is the safe and correct approach
- Themes can dequeue via `wp_dequeue_style( 'pawa-downloads' )`
- Responsive grid: `auto-fill, minmax(300px, 1fr)`, collapses to 1 column on ≤600px
- Card hover: lift + shadow (`transform: translateY(-2px)`)
- Download button accent colour: `#2a7c5f` (green) — CSS override documented in `readme.txt`
- CSS class prefix: `.pawa-dl-*` throughout to avoid theme conflicts

### `uninstall.php`

Runs only when the plugin is deleted (not deactivated). Opens with:
```php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
```
This prevents direct execution via URL.

- Queries all `pawa_download` posts (any status) and deletes them with `wp_delete_post( $id, true )` (force-delete, bypasses trash — post meta is cascade-deleted automatically by WordPress)
- No separate meta cleanup step is needed: `wp_delete_post( ..., true )` removes all associated post meta as part of the delete

### `languages/pawa-downloads.pot`

Translation template. Generated with WP-CLI:
```bash
wp i18n make-pot . languages/pawa-downloads.pot --domain=pawa-downloads
```

Run from the plugin root. Covers all `__()`, `esc_html__()`, `esc_html_e()`, `_e()` calls in the plugin.

### `LICENSE`

Full GPL-2.0-or-later license text. Standard for public GitHub repos and WP.org submissions.

### `readme.txt`

Follows the [WordPress.org readme standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/):

```
=== Pawa Downloads ===
Contributors: rahisisolutions
Tags: downloads, file manager, custom post type, shortcode, pdf
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

Sections:
- `== Description ==`
- `== Installation ==`
- `== Frequently Asked Questions ==`
  - How do I change the button colour? (CSS override example)
  - What file types are supported?
  - Does this work with Elementor / page builders?
- `== Screenshots ==` (placeholder; screenshots added before WP.org submission)
- `== Changelog ==`
- `== Upgrade Notice ==`

> **Pre-submission note:** The `Contributors:` value (`rahisisolutions`) must be a registered WordPress.org account. Verify or create the account at https://wordpress.org/support/register.php before submitting.

---

## WP.org Assets (Pre-submission)

WP.org uses a separate SVN `assets/` directory (not bundled in the plugin zip) for:

| File | Size | Purpose |
|------|------|---------|
| `banner-772x250.jpg` | 772×250px | Plugin directory banner |
| `banner-1544x500.jpg` | 1544×500px | Retina banner |
| `icon-128x128.jpg` | 128×128px | Plugin icon |
| `icon-256x256.jpg` | 256×256px | Retina icon |
| `screenshot-1.png` | Any | Admin CPT list view |
| `screenshot-2.png` | Any | Edit screen with meta box |
| `screenshot-3.png` | Any | Front-end card grid |

These are deferred to pre-submission (out of scope for v1.0.0 development) but should be created before the plugin is submitted to WordPress.org.

---

## What Changes from the Mwangaza Original

| Original | Rebranded |
|---|---|
| `mwangaza_*` functions | `pawa_dl_*` functions |
| CPT slug `download` | CPT slug `pawa_download` |
| Shortcode `[mwangaza_downloads]` | Shortcode `[pawa_downloads]` |
| Meta key `_download_file_url` | Meta key `_pawa_dl_file_url` |
| CSS class `.mwangaza-*` | CSS class `.pawa-dl-*` |
| No `uninstall.php` | `uninstall.php` with `WP_UNINSTALL_PLUGIN` guard + cleanup |
| No i18n | All user-facing strings wrapped in translation functions |
| No `readme.txt` | Full WP.org `readme.txt` |
| Single PHP file | Multi-file `includes/` structure |
| Inline `<style>` via `wp_head` | `wp_register_style()` + `wp_add_inline_style()` |
| No shortcode attributes | `limit` and `order` attributes |
| No activation check | PHP 7.4+ enforced on activation |
| `esc_url_raw()` + `sanitize_text_field()` | `esc_url_raw()` only |
| No `wp_enqueue_media()` call | `wp_enqueue_media()` scoped to CPT edit screen |

---

## GitHub Repository

- **Owner:** mwangepatrick
- **Name:** pawa-downloads
- **URL:** https://github.com/mwangepatrick/pawa-downloads
- **Visibility:** Public
- **Initial commit:** Full plugin scaffold
- **`.gitignore`:** `.DS_Store`, `*.log`, `.env`, `node_modules/`, `*.zip`, `Thumbs.db`

---

## Out of Scope (v1.0.0)

- Admin settings page (button colour configurable via CSS override — documented in readme)
- Download tracking / analytics
- Category/tag taxonomy for downloads
- REST API exposure of the CPT
- Block editor (Gutenberg) block
- Separate enqueued `.css` file (styles inlined via `wp_add_inline_style` for now)
- WP.org submission assets (banner, icon, screenshots)

These can be addressed in future versions.

---

## Success Criteria

1. Plugin installs and activates on WordPress 5.8+ / PHP 7.4+ without errors
2. Activation fails gracefully with an admin notice on PHP < 7.4
3. `pawa_download` CPT appears in the admin sidebar
4. Admin can create a download entry, attach a file URL via the media picker, and publish
5. `[pawa_downloads]` renders the card grid correctly on the front end — including on Elementor pages
6. `[pawa_downloads limit="6" order="ASC"]` attributes work correctly
7. Plugin deletes cleanly (no orphaned data) via the Delete button in the plugins screen
8. No PHP warnings or notices at `WP_DEBUG = true`
9. All user-facing strings are wrapped for translation
10. `readme.txt` passes the [WP.org plugin checker](https://wordpress.org/plugins/plugin-check/)
11. Styles can be dequeued by a child theme via `wp_dequeue_style( 'pawa-downloads' )`
