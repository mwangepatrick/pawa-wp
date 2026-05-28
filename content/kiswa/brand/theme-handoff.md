# Helpy Theme — Brand Handoff Guide (kiswakenya)

## How Options Are Stored
- **Theme:** Helpy Child (`helpy-child`)
- **Framework:** Kirki (WordPress Customizer)
- **Storage:** `wp_options` table, key: `theme_mods_helpy`
- **Read/Write:** `get_theme_mod('key')` / `set_theme_mod('key', value)`
- **REST endpoint:** `POST /wp-json/customize/v1/settings` or via WP-CLI

---

## 1. Logo Settings

| Setting Key | Value to Set | Notes |
|---|---|---|
| `header_logo` | WP media URL (dark logo) | Primary logo shown on light backgrounds |
| `header_secondary_logo` | WP media URL (white logo) | Used on dark/coloured headers |
| `footer_logo_image` | WP media URL | Footer logo |
| WordPress core `custom_logo` | Media attachment ID: `6004` | Already set ✅ |

**Current logo uploaded:**
- File: `kiswa.jpg`
- WP Media ID: `6004`
- URL: `https://kiswakenya.org/wp-content/uploads/2026/05/kiswa.jpg`

> ⚠️ Still needed: white/SVG logo variant for `header_secondary_logo`

---

## 2. Contact Info (Header)

| Setting Key | Value to Set |
|---|---|
| `header_phone` | `+254 745 696692` |
| `header_email` | `kiswagroup2@gmail.com` |
| `header_address` | `Kisumu, Kenya` |
| `header_address_link` | Google Maps URL for Kisumu |
| `header_button_text` | `Donate Now` |
| `header_button_link` | `https://kiswakenya.org/donation/` |

---

## 3. Social Media Links

| Setting Key | Value to Set |
|---|---|
| `header_facebook_link` | `https://www.facebook.com/kiswakenya/` |
| `header_twitter_link` | `https://x.com/kiswagroup2` |
| `header_instagram_link` | *(not found — leave `#`)* |
| `header_linkedin_link` | *(not found — leave `#`)* |
| `header_youtube_link` | *(not found — leave `#`)* |

---

## 4. Theme Colors

| Setting Key | Current Default | KISWA Brand Value | Role |
|---|---|---|---|
| `vl_color_body` | `#514F4C` | `#1E1B4B` | Body text |
| `vl_core_color_1` | `#181713` | `#1E1B4B` | Primary dark colour |
| `vl_core_color_2` | `#FBD459` | `#F59E0B` | Accent / highlight |
| `vl_core_color_3` | `#95999D` | `#9CA3AF` | Muted / grey |
| `vl_core_color_white` | `#ffffff` | `#ffffff` | White (no change) |
| `vl_core_bg_1` | `#FBD459` | `#F59E0B` | Primary background accent |
| `vl_core_bg_2` | `#F6F4EE` | `#F5F3FF` | Section alt background |
| `vl_core_bg_3` | `#f8f9fa` | `#f8f9fa` | Light bg (no change) |
| `vl_core_bg_black` | `#181713` | `#1E1B4B` | Dark background |
| `vl_preloader_bg` | `#FBD459` | `#7E22CE` | Preloader background (brand purple) |
| `breadcrumb_bg_color` | `#e7eaf3` | `#F5F3FF` | Breadcrumb background |
| `footer_bg_color` | *(empty)* | `#1E1B4B` | Footer background |

---

## 5. Typography

| Setting Key | Font Family | Variant | Target Element |
|---|---|---|---|
| `helpy_typo_body` | `Inter` | `400` | `body` |
| `helpy_typo_h1` | `Poppins` | `700` | `h1` |
| `helpy_typo_h2` | `Poppins` | `700` | `h2` |
| `helpy_typo_h3` | `Poppins` | `600` | `h3` |
| `helpy_typo_h4` | `Poppins` | `600` | `h4` |
| `helpy_typo_h5` | `Poppins` | `500` | `h5` |
| `helpy_typo_h6` | `Poppins` | `500` | `h6` |

Each typography setting takes an object:
```json
{
  "font-family": "Poppins",
  "variant": "700",
  "color": "",
  "font-size": "",
  "line-height": "",
  "text-align": ""
}
```

---

## 6. Footer Settings

| Setting Key | Value to Set |
|---|---|
| `footer_copyright` | `Copyright © 2025 KISWA Kenya. All Rights Reserved.` |
| `footer_copyright_switch` | `true` |
| `footer_social_switch` | `true` |
| `footer_bottom_copyright_area_switch` | `true` |
| `footer_widget_number` | `4` |

---

## 7. Header Switches (Recommended On)

| Setting Key | Recommended Value | Purpose |
|---|---|---|
| `header_top_button_switch` | `on` | Show "Donate Now" button in header |
| `header_backtotop_switch` | `on` | Back to top button |
| `header_side_social_switch` | `on` | Social icons in offcanvas |
| `footer_social_switch` | `on` | Social icons in footer |

---

## How to Apply (confirmed methods)

> ✅ **Confirmed working:** `set_theme_mod()` via FTP-deployed PHP file  
> ❌ `/customize/v1/settings` REST endpoint — not available (plugin not installed)  
> ❌ `/wp/v2/settings` — does not expose theme mods  
> **Server path:** `/home1/kiswak/public_html`

### Method A — WordPress Customizer (browser)
Go to: `https://kiswakenya.org/wp-admin/customize.php`
Navigate to **Helpy Panel** and update fields manually.

### Method B — WP-CLI (if SSH/terminal access)
```bash
wp theme mod set vl_core_color_2 '#F59E0B' --path=/home1/kiswak/public_html
wp theme mod list --path=/home1/kiswak/public_html
```

### Method C — FTP + PHP file (confirmed ✅)
Upload a PHP script via FTP, call it over HTTP, then delete it:
```php
<?php
require('/home1/kiswak/public_html/wp-load.php');

set_theme_mod('vl_core_color_2', '#F59E0B');
set_theme_mod('vl_core_color_1', '#1E1B4B');
set_theme_mod('vl_color_body',   '#1E1B4B');
// ... add all settings

echo json_encode(['done' => true]);
```
Upload to `/apply-brand.php`, run `curl https://kiswakenya.org/apply-brand.php`, then delete via FTP.

> ⚠️ Always delete the PHP file immediately after running it.

---

## Status
- [ ] Logo (dark version) — set via `header_logo`
- [ ] Logo (white version) — still needed
- [ ] Contact info — phone, email, address
- [ ] Social links — Facebook + Twitter confirmed
- [ ] Brand colors — ready to apply (see table above)
- [ ] Typography — Poppins + Inter + Lora ready to apply
- [ ] Footer copyright text
- [ ] Header button → "Donate Now"
