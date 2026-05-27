# Pawa Downloads — Design Spec

**Date:** 2026-05-27
**Status:** Approved
**Author:** Rahisi Solutions

---

## Overview

Repackage the `mwangaza-downloads` WordPress plugin into a generic, distributable plugin called **Pawa Downloads**, structured for eventual submission to the WordPress.org plugin repository.

The plugin lets site admins manage downloadable files (PDF, Word, Excel) via a custom post type and render them as a styled card grid using a shortcode.

---

## Plugin Identity

| Field        | Value                          |
|--------------|-------------------------------|
| Plugin Name  | Pawa Downloads                 |
| Slug / folder | `pawa-downloads`              |
| Text domain  | `pawa-downloads`               |
| Function prefix | `pawa_dl_`                  |
| Shortcode    | `[pawa_downloads]`             |
| CPT slug     | `pawa_download`                |
| Meta key     | `_pawa_dl_file_url`            |
| Version      | `1.0.0`                        |
| Author       | Rahisi Solutions               |
| Author URI   | https://rahisisolutions.com    |
| License      | GPL-2.0-or-later               |

The CPT slug is `pawa_download` (not `download`) to avoid conflicts with Easy Digital Downloads, WooCommerce, and other popular download plugins.

---

## File Structure

```
pawa-downloads/
├── pawa-downloads.php       ← plugin header, constants, loads includes/
├── readme.txt               ← WP.org listing file
├── uninstall.php            ← cleanup on plugin deletion
├── includes/
│   ├── cpt.php              ← registers pawa_download CPT
│   ├── meta-box.php         ← file URL meta field + media library picker
│   ├── shortcode.php        ← [pawa_downloads] shortcode + card grid renderer
│   └── styles.php           ← frontend CSS (injected only on pages using shortcode)
└── languages/
    └── pawa-downloads.pot   ← i18n translation template
```

---

## Architecture

### Main file (`pawa-downloads.php`)

Defines two constants:

- `PAWA_DL_VERSION` — plugin version string
- `PAWA_DL_DIR` — absolute path to the plugin folder (used for `require` paths)

Requires all four `includes/` files on `plugins_loaded` (priority 10). No logic lives in the main file beyond constants and includes.

### `includes/cpt.php`

Registers the `pawa_download` custom post type:

- Labels fully translated via `__()` with text domain `pawa-downloads`
- `public: false`, `show_ui: true`, `show_in_rest: false`
- Supports: `title`, `editor`
- Menu icon: `dashicons-download`, position 20
- `rewrite: false` (not a public-facing CPT)

### `includes/meta-box.php`

Adds a **Download File** meta box to the `pawa_download` edit screen:

- Text input for the file URL
- **Choose File** button that opens the WP media library picker (JS via `wp.media`)
- Nonce verification on save (`pawa_dl_save_download` action)
- Sanitised with `esc_url_raw()` + `sanitize_text_field()` before storing
- Stored under meta key `_pawa_dl_file_url`
- Hooks: `add_meta_boxes`, `save_post_pawa_download`

### `includes/shortcode.php`

Registers `[pawa_downloads]` shortcode:

- Queries all published `pawa_download` posts, ordered by date DESC
- Renders a `.pawa-dl-grid` card grid; each card contains:
  - SVG icon colour-coded by file extension (PDF=red, DOC/DOCX=blue, XLS/XLSX=green, fallback=grey)
  - Post title
  - Post content (description, via `wpautop`)
  - Download button linking to the file URL (or "Coming soon" if no URL)
- All output escaped with `esc_html()` / `esc_url()` / `wp_kses_post()`
- Returns empty-state message if no downloads published

### `includes/styles.php`

Hooks into `wp_head` and injects a `<style>` block only when the current page uses the `[pawa_downloads]` shortcode (checked via `has_shortcode()`):

- Responsive grid: `auto-fill, minmax(300px, 1fr)`, collapses to 1 column on ≤600px
- Card hover: lift + shadow (`transform: translateY(-2px)`)
- Download button accent colour: `#2a7c5f` (green) — override documented in `readme.txt`
- CSS class prefix: `.pawa-dl-*` throughout to avoid theme conflicts

### `uninstall.php`

Runs only when the plugin is deleted (not deactivated):

- Queries all `pawa_download` posts (any status) and deletes them with `wp_delete_post($id, true)` (force-delete, bypasses trash)
- Cleans up any orphaned `_pawa_dl_file_url` post meta

### `languages/pawa-downloads.pot`

Translation template generated from all `__()`, `esc_html__()`, `esc_html_e()` calls in the plugin. Allows translators to produce `.po`/`.mo` files without touching plugin code.

### `readme.txt`

Follows the [WordPress.org readme standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/):

```
=== Pawa Downloads ===
Contributors: rahisisolutions
Tags: downloads, file manager, custom post type, shortcode, pdf
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

Sections: Description, Installation, Frequently Asked Questions (customising button colour, supported file types), Changelog, Upgrade Notice.

---

## What Changes from the Mwangaza Original

| Original | Rebranded |
|---|---|
| `mwangaza_*` functions | `pawa_dl_*` functions |
| CPT slug `download` | CPT slug `pawa_download` |
| Shortcode `[mwangaza_downloads]` | Shortcode `[pawa_downloads]` |
| Meta key `_download_file_url` | Meta key `_pawa_dl_file_url` |
| CSS class `.mwangaza-*` | CSS class `.pawa-dl-*` |
| No `uninstall.php` | `uninstall.php` with full cleanup |
| No i18n | All strings wrapped in translation functions |
| No `readme.txt` | Full WP.org `readme.txt` |
| Single PHP file | Multi-file `includes/` structure |

---

## GitHub Repository

- **Owner:** mwangepatrick
- **Name:** pawa-downloads
- **URL:** https://github.com/mwangepatrick/pawa-downloads
- **Visibility:** Public
- **Initial commit:** Full plugin scaffold
- **`.gitignore`:** Standard WordPress plugin gitignore (excludes `.DS_Store`, `*.log`, etc.)

---

## Out of Scope (v1.0.0)

- Admin settings page (button colour configurable via CSS override only — documented)
- Download tracking / analytics
- Category/tag taxonomy for downloads
- REST API exposure of the CPT
- Block editor block (Gutenberg)

These can be addressed in future versions.

---

## Success Criteria

1. Plugin installs and activates on WordPress 5.8+ without errors
2. `pawa_download` CPT appears in the admin sidebar
3. Admin can create a download entry, attach a file URL via media picker, and publish
4. `[pawa_downloads]` shortcode renders the card grid correctly on the front end
5. Plugin deletes cleanly (no orphaned data) via the Delete button in the plugins screen
6. No PHP warnings or notices at `WP_DEBUG = true`
7. All user-facing strings are wrapped for translation
8. `readme.txt` passes the WP.org plugin checker
