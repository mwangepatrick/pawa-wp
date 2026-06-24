# MICCOS Partners Directory Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Partners directory to miccsofkenya.org — a `/partners/` listing page with auto-generated partner cards, and individual profile pages for 9 member organisations.

**Architecture:** A `[miccos_partners]` shortcode (added to `pawa-downloads` plugin) queries child pages of the Partners page and renders a CSS grid of cards. Individual partner pages are regular WordPress pages with an Elementor layout built from a consistent 5-section structure (hero / about / focus / contact / gallery).

**Tech Stack:** WordPress, VL Core theme, Elementor 4.1.1 (free), pawa-downloads plugin, pawa-wp-mcp MCP tools (`wp_create_post`, `wp_update_elementor_data`, `ftp_upload`, `wp_add_menu_item`)

## Global Constraints

- Site alias: `miccos` (https://miccsofkenya.org)
- FTP root: `/public_html` — plugin path is `wp-content/plugins/pawa-downloads/`
- WSL source data: `/mnt/c/Users/ADMIN/Downloads/members-20260624T121433Z-3-001/partners-members/partner-extracts/`
- Site colours — Primary: `#000080` (navy), Secondary: `#9C2747`, Accent: `#A1C457`, Text: `#7A7A7A`
- Fonts — UI: Roboto, Headings: Roboto Slab
- All partner pages: page template must be `elementor_header_footer` (keeps VL Core header/footer — NOT canvas)
- Elementor data style: classic sections (not containers)
- Partners listing page slug: `partners` (shortcode resolves parent dynamically via `get_page_by_path('partners')`)
- Spec: `docs/superpowers/specs/2026-06-24-miccos-partners-design.md`

---

## File Map

| Action | Path | Purpose |
|--------|------|---------|
| **Create** | `plugins/pawa-downloads/includes/partners.php` | `[miccos_partners]` shortcode — card grid renderer |
| **Modify** | `plugins/pawa-downloads/pawa-downloads.php` | Add `require_once` for partners.php |

WordPress changes (via MCP, no local files):
- Create page: "Partners" (listing)
- Create 9 partner pages (children of Partners)
- Add Partners link to primary nav menu

---

## Task 1: Partners shortcode — write, wire, deploy

**Files:**
- Create: `plugins/pawa-downloads/includes/partners.php`
- Modify: `plugins/pawa-downloads/pawa-downloads.php` (after line 62)

**Interfaces:**
- Produces: `[miccos_partners]` shortcode, callable in any Elementor Shortcode widget
- Output: `.miccos-partners-grid` div containing `.miccos-partner-card` elements

- [ ] **Step 1: Create `plugins/pawa-downloads/includes/partners.php`**

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'miccos_partners', 'miccos_partners_shortcode' );

function miccos_partners_shortcode() {
    $page = get_page_by_path( 'partners' );
    if ( ! $page ) {
        return '<p>Partners listing not configured.</p>';
    }

    $partners = get_posts( [
        'post_type'      => 'page',
        'post_parent'    => $page->ID,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'posts_per_page' => -1,
    ] );

    if ( empty( $partners ) ) {
        return '<p>No partners found.</p>';
    }

    ob_start();
    ?>
    <style>
    .miccos-partners-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin: 2rem 0;
    }
    .miccos-partner-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: box-shadow .2s, transform .2s;
    }
    .miccos-partner-card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,.14);
        transform: translateY(-3px);
    }
    .miccos-partner-logo-wrap {
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 160px;
    }
    .miccos-partner-logo-wrap img {
        max-height: 120px;
        max-width: 80%;
        object-fit: contain;
    }
    .miccos-partner-logo-fallback {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #000080;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        font-weight: 700;
        font-family: Roboto, sans-serif;
        letter-spacing: 1px;
    }
    .miccos-partner-body {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .miccos-partner-name {
        font-family: Roboto, sans-serif;
        font-size: 1rem;
        font-weight: 600;
        color: #000080;
        margin: 0 0 .5rem;
        line-height: 1.3;
    }
    .miccos-partner-excerpt {
        font-family: Roboto, sans-serif;
        font-size: .875rem;
        color: #7a7a7a;
        flex: 1;
        margin: 0 0 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .miccos-partner-btn {
        display: inline-block;
        padding: .5rem 1.25rem;
        background: #000080;
        color: #fff !important;
        border-radius: 4px;
        text-decoration: none !important;
        font-size: .875rem;
        font-weight: 500;
        font-family: Roboto, sans-serif;
        align-self: flex-start;
        transition: background .2s;
    }
    .miccos-partner-btn:hover { background: #00006a; }
    @media (max-width: 1024px) {
        .miccos-partners-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 540px) {
        .miccos-partners-grid { grid-template-columns: 1fr; }
    }
    </style>
    <div class="miccos-partners-grid">
    <?php foreach ( $partners as $partner ) :
        $has_thumb = has_post_thumbnail( $partner->ID );
        $words     = preg_split( '/\s+/', $partner->post_title );
        $initials  = implode( '', array_map( fn( $w ) => strtoupper( $w[0] ?? '' ), array_slice( $words, 0, 3 ) ) );
    ?>
        <div class="miccos-partner-card">
            <div class="miccos-partner-logo-wrap">
                <?php if ( $has_thumb ) : ?>
                    <img src="<?php echo esc_url( get_the_post_thumbnail_url( $partner->ID, 'medium' ) ); ?>"
                         alt="<?php echo esc_attr( $partner->post_title ); ?> logo">
                <?php else : ?>
                    <div class="miccos-partner-logo-fallback" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
                <?php endif; ?>
            </div>
            <div class="miccos-partner-body">
                <h3 class="miccos-partner-name"><?php echo esc_html( $partner->post_title ); ?></h3>
                <?php if ( $partner->post_excerpt ) : ?>
                    <p class="miccos-partner-excerpt"><?php echo esc_html( $partner->post_excerpt ); ?></p>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_permalink( $partner->ID ) ); ?>" class="miccos-partner-btn">View Profile</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

- [ ] **Step 2: Wire into main plugin file**

In `plugins/pawa-downloads/pawa-downloads.php`, after line 62 (after `require_once PAWA_DL_DIR . 'includes/styles.php';`), add:

```php
require_once PAWA_DL_DIR . 'includes/partners.php';
```

- [ ] **Step 3: FTP-upload `partners.php` to site**

Use `ftp_upload` MCP tool:
- site: `miccos`
- local path: `plugins/pawa-downloads/includes/partners.php`
- remote path: `wp-content/plugins/pawa-downloads/includes/partners.php`

- [ ] **Step 4: FTP-upload the modified `pawa-downloads.php`**

Use `ftp_upload` MCP tool:
- site: `miccos`
- local path: `plugins/pawa-downloads/pawa-downloads.php`
- remote path: `wp-content/plugins/pawa-downloads/pawa-downloads.php`

- [ ] **Step 5: Verify shortcode registered**

Use `wp_inspect` MCP tool on site `miccos`. Search the output for `miccos_partners` in the registered shortcodes list.
Expected: shortcode appears in the list.

- [ ] **Step 6: Commit**

```bash
git add plugins/pawa-downloads/includes/partners.php plugins/pawa-downloads/pawa-downloads.php
git commit -m "feat: add [miccos_partners] shortcode to pawa-downloads plugin"
```

---

## Task 2: Partners listing page

**WordPress changes via MCP.**

- [ ] **Step 1: Create the Partners page**

Use `wp_create_post`:
```json
{
  "site": "miccos",
  "title": "Partners",
  "slug": "partners",
  "type": "page",
  "status": "publish",
  "content": ""
}
```
Note the returned post ID — call it `PARTNERS_PAGE_ID`.

- [ ] **Step 2: Build Elementor content — hero + shortcode grid**

Use `wp_update_elementor_data` with post_id = `PARTNERS_PAGE_ID` and the following elements array:

```json
[
  {
    "id": "plh001",
    "elType": "section",
    "settings": {
      "background_background": "classic",
      "background_color": "#000080",
      "padding": {"unit": "px", "top": "80", "right": "30", "bottom": "80", "left": "30", "isLinked": false}
    },
    "elements": [
      {
        "id": "plc001",
        "elType": "column",
        "settings": {"_column_size": 100},
        "elements": [
          {
            "id": "plw001",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "Our Partners",
              "align": "center",
              "title_color": "#ffffff",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 42},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "plw002",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "<p style=\"text-align:center;color:#ffffff;\">MICCOS brings together civil society organisations across Migori County working towards sustainable development, justice, and community empowerment.</p>"
            },
            "elements": []
          }
        ]
      }
    ]
  },
  {
    "id": "plh002",
    "elType": "section",
    "settings": {
      "padding": {"unit": "px", "top": "60", "right": "30", "bottom": "60", "left": "30", "isLinked": false}
    },
    "elements": [
      {
        "id": "plc002",
        "elType": "column",
        "settings": {"_column_size": 100},
        "elements": [
          {
            "id": "plw003",
            "elType": "widget",
            "widgetType": "shortcode",
            "settings": {
              "shortcode": "[miccos_partners]"
            },
            "elements": []
          }
        ]
      }
    ]
  }
]
```

- [ ] **Step 3: Set page template to keep header/footer**

Use `wp_update_elementor_page_settings` with post_id = `PARTNERS_PAGE_ID`:
```json
{
  "site": "miccos",
  "post_id": PARTNERS_PAGE_ID,
  "settings": {
    "template": "elementor_header_footer"
  }
}
```

- [ ] **Step 4: Verify page exists**

Use `wp_get_post` with site `miccos` and the `PARTNERS_PAGE_ID`. Confirm title is "Partners" and status is "publish".

---

## Partner Page Elementor Template (reference — used in Tasks 3–11)

All 9 partner pages share this 5-section Elementor structure. The JSON below is the **base template** — replace the ALL_CAPS placeholders with partner-specific values for each task.

Placeholders:
- `PARTNER_ACRONYM` — short name e.g. "CASO"
- `PARTNER_FULL_NAME` — full org name
- `PARTNER_MISSION` — one-line mission from profile
- `PARTNER_ABOUT` — HTML paragraph(s) of the introduction/profile narrative
- `PARTNER_FOCUS` — HTML unordered list of thematic areas
- `PARTNER_ADDRESS` — HTML with address, phone, email, website (each on its own line using `<br>`)
- `PARTNER_CONTACT_PERSON` — HTML with contact person name + email

```json
[
  {
    "id": "PPID_h1",
    "elType": "section",
    "settings": {
      "background_background": "classic",
      "background_color": "#000080",
      "padding": {"unit": "px", "top": "70", "right": "40", "bottom": "70", "left": "40", "isLinked": false}
    },
    "elements": [
      {
        "id": "PPID_c1",
        "elType": "column",
        "settings": {"_column_size": 100},
        "elements": [
          {
            "id": "PPID_w1",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "PARTNER_ACRONYM",
              "align": "center",
              "title_color": "#A1C457",
              "header_size": "h3",
              "typography_typography": "custom",
              "typography_font_family": "Roboto",
              "typography_font_size": {"unit": "px", "size": 18},
              "typography_font_weight": "500"
            },
            "elements": []
          },
          {
            "id": "PPID_w2",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "PARTNER_FULL_NAME",
              "align": "center",
              "title_color": "#ffffff",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 34},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "PPID_w3",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "<p style=\"text-align:center;color:rgba(255,255,255,0.85);\">PARTNER_MISSION</p>"
            },
            "elements": []
          }
        ]
      }
    ]
  },
  {
    "id": "PPID_h2",
    "elType": "section",
    "settings": {
      "padding": {"unit": "px", "top": "60", "right": "60", "bottom": "60", "left": "60", "isLinked": false}
    },
    "elements": [
      {
        "id": "PPID_c2",
        "elType": "column",
        "settings": {"_column_size": 100},
        "elements": [
          {
            "id": "PPID_w4",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "About Us",
              "title_color": "#000080",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 28},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "PPID_w5",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "PARTNER_ABOUT"
            },
            "elements": []
          }
        ]
      }
    ]
  },
  {
    "id": "PPID_h3",
    "elType": "section",
    "settings": {
      "background_background": "classic",
      "background_color": "#f5f5f5",
      "padding": {"unit": "px", "top": "60", "right": "60", "bottom": "60", "left": "60", "isLinked": false}
    },
    "elements": [
      {
        "id": "PPID_c3",
        "elType": "column",
        "settings": {"_column_size": 100},
        "elements": [
          {
            "id": "PPID_w6",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "Our Focus Areas",
              "title_color": "#000080",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 28},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "PPID_w7",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "PARTNER_FOCUS"
            },
            "elements": []
          }
        ]
      }
    ]
  },
  {
    "id": "PPID_h4",
    "elType": "section",
    "settings": {
      "padding": {"unit": "px", "top": "60", "right": "60", "bottom": "60", "left": "60", "isLinked": false}
    },
    "elements": [
      {
        "id": "PPID_c4a",
        "elType": "column",
        "settings": {"_column_size": 50},
        "elements": [
          {
            "id": "PPID_w8",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "Contact Information",
              "title_color": "#000080",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 22},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "PPID_w9",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "PARTNER_ADDRESS"
            },
            "elements": []
          }
        ]
      },
      {
        "id": "PPID_c4b",
        "elType": "column",
        "settings": {"_column_size": 50},
        "elements": [
          {
            "id": "PPID_w10",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "Contact Person",
              "title_color": "#000080",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 22},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "PPID_w11",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "PARTNER_CONTACT_PERSON"
            },
            "elements": []
          }
        ]
      }
    ]
  },
  {
    "id": "PPID_h5",
    "elType": "section",
    "settings": {
      "background_background": "classic",
      "background_color": "#f5f5f5",
      "padding": {"unit": "px", "top": "60", "right": "60", "bottom": "60", "left": "60", "isLinked": false}
    },
    "elements": [
      {
        "id": "PPID_c5",
        "elType": "column",
        "settings": {"_column_size": 100},
        "elements": [
          {
            "id": "PPID_w12",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "Gallery",
              "title_color": "#000080",
              "typography_typography": "custom",
              "typography_font_family": "Roboto Slab",
              "typography_font_size": {"unit": "px", "size": 28},
              "typography_font_weight": "600"
            },
            "elements": []
          },
          {
            "id": "PPID_w13",
            "elType": "widget",
            "widgetType": "image-gallery",
            "settings": {
              "wp_gallery": [],
              "gallery_columns": {"unit": "px", "size": 3},
              "gallery_link": "file"
            },
            "elements": []
          }
        ]
      }
    ]
  }
]
```

**When substituting IDs:** Replace every `PPID_` prefix with a unique 4-character hex string per page (e.g. `a1b2_h1`, `a1b2_c1` etc.). IDs must be unique within the page but don't need to be globally unique.

**Gallery note:** For partners with no activity photos (OBACODEP, PlanEarthWise), omit sections h5 entirely from the elements array.

**Image note:** Featured images (logos) cannot be set via MCP. After creating each partner page via MCP, upload the logo through WP Admin → Media → Add New, then edit the page and set the Featured Image. The shortcode card will show the initials fallback until a featured image is set.

---

## Task 3: CASO partner page

**Partner data:**
- Full name: Community Actions Support Organization
- Acronym: CASO
- Mission: CASO supports and promotes social, economic, cultural, governance, environmental and technological development actions at the village level to improve living standards in Migori County.
- About: `<p>CASO is a Non-Profit making and non-political Community Based Organisation, Reg. No. MIG/CBO/81/2015 with the Ministry of Labour, Social Security and Services in Migori County.</p><p>CASO supports and promotes social, economic, cultural, governance, environmental and technological development actions of communities at the village (grassroot) levels that improve living standards in Migori County.</p>`
- Focus: `<ul><li>Civic education and information dissemination on legal and human rights</li><li>Women, youth, girls, boys and PWD empowerment and decision-making</li><li>Environmental development and gender equality in resource mobilisation</li><li>Fundraising and resource mobilisation for community projects</li><li>Modern farming techniques, value chain and farm product marketing</li></ul>`
- Address: `<p>P.O Box 404, 40400, Suna Migori<br>Kenya<br><strong>Tel:</strong> 0720385129<br><strong>Email:</strong> <a href="mailto:casoactions@gmail.com">casoactions@gmail.com</a></p>`
- Contact person: `<p><strong>Titus Vincent Odiwuor Orwa</strong><br><a href="mailto:titusorwa@gmail.com">titusorwa@gmail.com</a></p><p><strong>Alternate:</strong> Celline Achieng Ogonjo<br><a href="mailto:cellineogonjo@gmail.com">cellineogonjo@gmail.com</a></p>`
- Gallery: 1 image (image1.jpeg — likely logo; use for featured image, not gallery). Omit gallery section.
- Logo file: `community-actions-support-organization-caso/images/image1.jpeg`

- [ ] **Step 1: Create the CASO page**

Use `wp_create_post`:
```json
{
  "site": "miccos",
  "title": "Community Actions Support Organization",
  "slug": "caso",
  "type": "page",
  "status": "publish",
  "excerpt": "CASO supports and promotes social, economic, cultural, governance, environmental and technological development actions at the village level to improve living standards in Migori County.",
  "parent": PARTNERS_PAGE_ID
}
```
Note returned ID as `CASO_ID`.

- [ ] **Step 2: Apply Elementor layout**

Use `wp_update_elementor_data` with `post_id = CASO_ID`. Use the base template from the reference section above with:
- Replace `PPID_` prefix with `caso_` throughout all IDs
- `PARTNER_ACRONYM` → `CASO`
- `PARTNER_FULL_NAME` → `Community Actions Support Organization`
- `PARTNER_MISSION` → `Supporting and promoting social, economic, cultural, governance, environmental and technological development at the grassroot level in Migori County.`
- `PARTNER_ABOUT` → (About HTML above)
- `PARTNER_FOCUS` → (Focus HTML above)
- `PARTNER_ADDRESS` → (Address HTML above)
- `PARTNER_CONTACT_PERSON` → (Contact person HTML above)
- Omit section `PPID_h5` (gallery) — CASO has only a logo, no activity photos

- [ ] **Step 3: Set page template**

Use `wp_update_elementor_page_settings`:
```json
{"site": "miccos", "post_id": CASO_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Upload logo manually**

Upload `community-actions-support-organization-caso/images/image1.jpeg` via WP Admin → Media → Add New on miccsofkenya.org, then set it as Featured Image on the CASO page.

- [ ] **Step 5: Verify**

Use `wp_get_post` with `CASO_ID`. Confirm title, status publish, parent = `PARTNERS_PAGE_ID`.

---

## Task 4: Forgotten Voices Foundation partner page

**Partner data:**
- Full name: Forgotten Voices Foundation
- Acronym: (none in profile — use FVF tentatively or leave hero subtitle blank)
- Mission: Ending period poverty by donating sanitary pads to school-going girls and growing into a comprehensive empowerment organisation for girls and women.
- About: `<p>Forgotten Voices Foundation was started in 2022 as a campaign initiative towards ending period poverty, by donating sanitary pads to school going girls especially those from humble backgrounds. The campaign impacted lives of many girls who could not afford to be present in school during their menstruation days, which led to lost dignity of girls during menstrual flows and stigma. As the organisation grew it initiated broader empowerment programmes for girls and women.</p>`
- Focus: `<ul><li>Menstrual health and period poverty elimination</li><li>Girl-child education and school retention</li><li>Women and girls empowerment</li></ul>`
- Address: `<p>518-40405, Sare Awendo, Kenya<br><strong>Tel:</strong> +254704762302<br><strong>Email:</strong> <a href="mailto:info.forgottenvoices@gmail.com">info.forgottenvoices@gmail.com</a><br><strong>Website:</strong> <a href="http://forgottenvoicesfoundation.org" target="_blank">forgottenvoicesfoundation.org</a></p>`
- Contact person: `<p><strong>Ruth Akoth</strong><br><a href="mailto:ruthakothe@gmail.com">ruthakothe@gmail.com</a></p><p><strong>Alternate:</strong> Willis Okoth<br><a href="mailto:forgottenvoices6@gmail.com">forgottenvoices6@gmail.com</a></p>`
- Gallery: 1 image (logo only). Omit gallery section.
- Logo file: `forgotten-voices-foundation/images/page-01-image-001.png`

- [ ] **Step 1: Create page**

```json
{
  "site": "miccos",
  "title": "Forgotten Voices Foundation",
  "slug": "forgotten-voices-foundation",
  "type": "page",
  "status": "publish",
  "excerpt": "Ending period poverty and empowering girls and women across Migori County through health, education, and community programmes.",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `FVF_ID`.

- [ ] **Step 2: Apply Elementor layout**

Use `wp_update_elementor_data` with base template, `PPID_` → `fvf_`, partner data substituted, gallery section omitted.

- [ ] **Step 3: Set page template**
```json
{"site": "miccos", "post_id": FVF_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Upload logo manually**

Upload `forgotten-voices-foundation/images/page-01-image-001.png` via WP Admin → Media → Add New, set as Featured Image on the FVF page.

- [ ] **Step 5: Verify** — `wp_get_post` with `FVF_ID`.

---

## Task 5: Funguo CBO partner page

**Partner data:**
- Full name: Funguo Community Based Organization
- Acronym: FUNGUO CBO
- Meaning: Fostering Unlimited Networks for Growth and Outstanding Youth
- Mission: To unlock the potential of youth by fostering networks, growth, leadership, innovation, and sustainable development through integrated health, empowerment, economic, and environmental programs.
- About: `<p>FUNGUO Community Based Organization (FUNGUO CBO) is a youth-led, community-based organisation operating in Migori County, Kenya. Its name means "Fostering Unlimited Networks for Growth and Outstanding Youth."</p><p>FUNGUO works at the intersection of health, economic empowerment, environmental conservation, and gender equity to create lasting change for young people in Migori County.</p>`
- Focus: `<ul><li>Health: Teen pregnancy prevention, HIV awareness, GBV prevention and survivor support</li><li>Youth empowerment: Skills development, mentorship, leadership, ICT and digital skills</li><li>Agribusiness and entrepreneurship: Training, market linkages, financial literacy</li><li>Climate action and environmental conservation: Tree planting, waste management, green practices</li><li>Gender equity and disability inclusion</li><li>Boy-child empowerment: Mentorship and guidance for boys and young men</li></ul>`
- Address: `<p>P.O. Box 45-40400, Migori, Kenya<br><strong>Tel:</strong> 0748399254<br><strong>Email:</strong> <a href="mailto:mitogordonfunguocbo@gmail.com">mitogordonfunguocbo@gmail.com</a></p>`
- Contact person: `<p><strong>Mito Gordon Osinda</strong><br><a href="mailto:mitogordonfunguocbo@gmail.com">mitogordonfunguocbo@gmail.com</a><br><a href="mailto:funguo76@outlook.com">funguo76@outlook.com</a></p>`
- Gallery: 13 images available. Include gallery section (leave `wp_gallery: []` — images must be uploaded manually via WP Admin).
- Logo files: `funguo-cbo/images/` (13 images — first image is likely the logo)

- [ ] **Step 1: Create page**

```json
{
  "site": "miccos",
  "title": "Funguo Community Based Organization",
  "slug": "funguo-cbo",
  "type": "page",
  "status": "publish",
  "excerpt": "A youth-led organisation in Migori County fostering networks, growth, leadership and sustainable development through health, empowerment, and environmental programmes.",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `FUNGUO_ID`.

- [ ] **Step 2: Apply Elementor layout**

Use `wp_update_elementor_data` with base template, `PPID_` → `fung_`, all 5 sections included (gallery section with empty `wp_gallery: []`).

- [ ] **Step 3: Set page template**
```json
{"site": "miccos", "post_id": FUNGUO_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Upload images manually**

Upload all images from `funguo-cbo/images/` via WP Admin → Media → Add New. Set the logo as Featured Image. Add the activity photos to the Elementor gallery widget.

- [ ] **Step 5: Verify** — `wp_get_post` with `FUNGUO_ID`.

---

## Task 6: OBACODEP partner page

**Partner data:**
- Full name: Obama Health and Community Development Programme
- Acronym: OBACODEP
- Mission: Community-based support for HIV/AIDS, orphans and vulnerable children, girl-child education, and sustainable development in Sare-Awendo, Migori County.
- About: `<p>Obama Health and Community Development Programme (OBACODEP) is a community-based organisation located at Obama Village Inn Plaza, Obama Market Centre, Kombok, Sare-Awendo, Kenya. OBACODEP works with people living with HIV/AIDS, orphans and vulnerable children (OVCs), and marginalised communities across Migori County.</p>`
- Focus: `<ul><li>HIV/AIDS awareness, VCT, guidance and counselling</li><li>Support to people living with HIV/AIDS (PLWHAs) and OVCs</li><li>Girl-child education and school uniforms provision</li><li>Income-generating activities and livelihood support</li><li>Environmental conservation and food security</li><li>MDGs localisation and civic/voter education</li></ul>`
- Address: `<p>Obama Village Inn Plaza, Obama Market Centre, Kombok<br>Sare-Awendo, Kenya<br>P.O. Box 696<br><strong>Tel:</strong> 0722 608554 / 0722 237409<br><strong>Email:</strong> <a href="mailto:obacodep@yahoo.com">obacodep@yahoo.com</a></p>`
- Contact person: `<p>Contact details not specified in profile. Please update via WP Admin.</p>`
- Gallery: No images. Omit gallery section.

- [ ] **Step 1: Create page**

```json
{
  "site": "miccos",
  "title": "Obama Health and Community Development Programme",
  "slug": "obacodep",
  "type": "page",
  "status": "publish",
  "excerpt": "Community-based support for HIV/AIDS, orphans and vulnerable children, and sustainable development across Migori County.",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `OBAC_ID`.

- [ ] **Step 2: Apply Elementor layout**

Use `wp_update_elementor_data` with base template, `PPID_` → `obac_`, gallery section omitted (no images), contact person section uses placeholder text noted above.

- [ ] **Step 3: Set page template**
```json
{"site": "miccos", "post_id": OBAC_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Verify** — `wp_get_post` with `OBAC_ID`.

---

## Task 7: PlanEarthWise partner page

**Partner data:**
- Full name: PlanEarthWise Community Based Organization
- Acronym: PlanEarthWise
- Tagline: "Make Waves Not Waste!"
- Mission: To foster sustainable development by engaging communities, especially youth, in environmental conservation, health promotion, education support, and economic empowerment.
- About: `<p>PlanEarthWise CBO is a community-based organisation rooted in Migori County, Kenya, dedicated to empowering local communities — especially youth — through environmental conservation, health awareness, education, and sustainable development.</p><p>We are community-driven, committed to environmental stewardship, waste management, and preservation of our natural landscapes. We believe sustainable change begins with local action and informed citizenship.</p>`
- Focus: `<ul><li>Waste management: Community clean-ups, waste segregation, recycling hubs, policy advocacy</li><li>Reforestation: Planting fruit trees, shade and exotic trees in schools and public spaces</li><li>Youth vocational training: Tailoring, welding, carpentry, IT, interior design</li><li>Climate resilience and SDGs engagement</li></ul>`
- Address: `<p>Migori Industrial Training Center, Opp. County Government Offices<br>P.O. Box 1234, Migori, Kenya<br><strong>Email:</strong> <a href="mailto:planearthwise@gmail.com">planearthwise@gmail.com</a><br><strong>Website:</strong> <a href="https://www.planearthwise.com" target="_blank">www.planearthwise.com</a></p>`
- Contact person: `<p><strong>Valentine Otieno</strong><br>+254 793 810 118</p><p><strong>Brendah Muga</strong><br>+254 711 354 430</p><p><strong>John Kasuku</strong><br>+254 768 249 150</p>`
- Gallery: No images. Omit gallery section.

- [ ] **Step 1: Create page**

```json
{
  "site": "miccos",
  "title": "PlanEarthWise Community Based Organization",
  "slug": "planearthwise-cbo",
  "type": "page",
  "status": "publish",
  "excerpt": "Empowering communities in Migori County through environmental conservation, waste management, reforestation, and youth vocational training.",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `PEW_ID`.

- [ ] **Step 2: Apply Elementor layout**

`wp_update_elementor_data`, `PPID_` → `pewc_`, gallery section omitted.

- [ ] **Step 3: Set page template**
```json
{"site": "miccos", "post_id": PEW_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Verify** — `wp_get_post` with `PEW_ID`.

---

## Task 8: UAWGO partner page

**Partner data:**
- Full name: Usalama Africa Women and Girls Organization
- Acronym: UAWGO
- Mission: Improving the living conditions of poor and marginalised groups through community initiative, participation and sensitisation with a focus on women and youth.
- About: `<p>Usalama Africa Women and Girls Organisation (UAWGO) is an independent, non-governmental and non-profitable social organisation established by a group of like-minded women and youth with experiences in different sectors to promote capacity development and provide opportunities to women and youth.</p><p>Registered with Public Benefits Organisation Regulatory Authority (Reg. No. OP.218/051/13-0153/8910), Social Welfare Council (MCSC/041/2024), and with PIN P051429762R. UAWGO works primarily in Nairobi, Siaya and Migori counties and extends to unreached pockets in western and rift valley regions.</p>`
- Focus: `<ul><li>Women and youth professionalism and capacity building</li><li>Health and education support</li><li>Research and community development</li><li>Environment and climate change</li><li>Women and youth economic empowerment</li><li>Social and economic advancement of marginalised groups</li></ul>`
- Address: `<p>P.O Box 123, Macalder, Migori County, Kenya<br><strong>Tel:</strong> +254 722805001<br><strong>Email:</strong> <a href="mailto:usalamaafricanwomen@gmail.com">usalamaafricanwomen@gmail.com</a><br><strong>Website:</strong> <a href="http://www.usalamaafricawomen.org" target="_blank">www.usalamaafricawomen.org</a></p>`
- Contact person: `<p><strong>Mrs. Selestine Otom</strong><br><a href="mailto:otomselestine@yahoo.com">otomselestine@yahoo.com</a></p>`
- Gallery: 15 images available. Include gallery section.
- Logo: First image from `usalama-africa-women-and-girls-organization-uawgo/images/`

- [ ] **Step 1: Create page**

```json
{
  "site": "miccos",
  "title": "Usalama Africa Women and Girls Organization",
  "slug": "uawgo",
  "type": "page",
  "status": "publish",
  "excerpt": "Empowering women and youth in Migori County through capacity building, health, education, economic empowerment, and climate action.",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `UAWGO_ID`.

- [ ] **Step 2: Apply Elementor layout**

`wp_update_elementor_data`, `PPID_` → `uawg_`, all 5 sections included.

- [ ] **Step 3: Set page template**
```json
{"site": "miccos", "post_id": UAWGO_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Upload images manually**

Upload all 15 images from `usalama-africa-women-and-girls-organization-uawgo/images/` via WP Admin → Media. Set logo as Featured Image. Add activity photos to gallery widget.

- [ ] **Step 5: Verify** — `wp_get_post` with `UAWGO_ID`.

---

## Task 9: WE4HIM partner page

**Partner data:**
- Full name: WE4HIM Community Based Organization
- Acronym: WE4HIM
- Mission: Equipping boys and men in Migori County with skills and opportunities to drive social, political, environmental and economic transformation.
- About: `<p>WE4HIM CBO is based in Migori County, building the next generation of male leaders. While most civil society organisations focus on women and girls, WE4HIM fills the critical gap: engaging boys and men as active partners for positive change. "Wanaume Imara, Jamii Imara — Strong Men, Strong Communities."</p><p>Boys in Migori County are dropping out of school for gold mining, sand harvesting, and boda boda business. Teen fathers are stigmatised. Older men die silently from prostate cancer. Families along Lake Victoria battle sickle cell. WE4HIM believes that engaging men is not competing with women's empowerment, but completing it.</p>`
- Focus: `<ul><li>Boy-child education and school retention</li><li>Men's health: Prostate cancer awareness, HIV prevention</li><li>Sickle cell awareness in Lake Victoria communities</li><li>Climate change adaptation for men</li><li>Teen father support and family responsibility</li><li>Skills development at boda boda stages, mining sites, and beaches</li></ul>`
- Address: `<p>P.O Box 40400, Suna-Migori, Kenya<br><strong>Tel:</strong> 0786115629<br><strong>Email:</strong> <a href="mailto:we4himcbo@gmail.com">we4himcbo@gmail.com</a></p>`
- Contact person: `<p><strong>Emilly Moraa Moturi</strong><br><a href="mailto:moraa009@gmail.com">moraa009@gmail.com</a></p><p><strong>Alternate:</strong> +254 723 142 167</p>`
- Gallery: 4 images available. Include gallery section.
- Logo: `we4him-community-based-organization/images/image1.png`

- [ ] **Step 1: Create page**

```json
{
  "site": "miccos",
  "title": "WE4HIM Community Based Organization",
  "slug": "we4him-cbo",
  "type": "page",
  "status": "publish",
  "excerpt": "Engaging boys and men in Migori County as active partners for positive change through health, education, skills development, and climate adaptation.",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `W4H_ID`.

- [ ] **Step 2: Apply Elementor layout**

`wp_update_elementor_data`, `PPID_` → `w4hm_`, all 5 sections included.

- [ ] **Step 3: Set page template**
```json
{"site": "miccos", "post_id": W4H_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 4: Upload images manually**

Upload 4 images from `we4him-community-based-organization/images/` via WP Admin → Media. Set logo (image1.png) as Featured Image.

- [ ] **Step 5: Verify** — `wp_get_post` with `W4H_ID`.

---

## Task 10: WECKMA CBO partner page

**Source file:** `/mnt/c/Users/ADMIN/Downloads/members-20260624T121433Z-3-001/partners-members/partner-extracts/weckma-cbo/index.md`

Read the full `index.md` before executing this task. Extract: full name, mission, introduction, objectives/focus areas, contact info, contact person.

**Known data:**
- Full name: WECKMA CBO (full expansion not provided in available extract)
- P.O Box 520-40400 Suna | Tel: 0723352071 | Email: weckma@yahoo.com
- Gallery: 13 images available.

- [ ] **Step 1: Read source file**

Read `/mnt/c/Users/ADMIN/Downloads/members-20260624T121433Z-3-001/partners-members/partner-extracts/weckma-cbo/index.md` and extract: mission, introduction (2–3 paragraphs), focus areas (as bullet list), any additional contact details, contact person name and email.

- [ ] **Step 2: Create page**

```json
{
  "site": "miccos",
  "title": "WECKMA CBO",
  "slug": "weckma-cbo",
  "type": "page",
  "status": "publish",
  "excerpt": "[Use one-sentence mission extracted in Step 1]",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `WECK_ID`.

- [ ] **Step 3: Apply Elementor layout**

`wp_update_elementor_data`, `PPID_` → `weck_`, all 5 sections included (gallery with 13 images — leave `wp_gallery: []`, fill manually). Populate PARTNER_ABOUT, PARTNER_FOCUS, PARTNER_ADDRESS, PARTNER_CONTACT_PERSON from data extracted in Step 1.

- [ ] **Step 4: Set page template**
```json
{"site": "miccos", "post_id": WECK_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 5: Upload images manually**

Upload 13 images from `weckma-cbo/images/` via WP Admin → Media. Set logo as Featured Image. Add activity photos to gallery widget.

- [ ] **Step 6: Verify** — `wp_get_post` with `WECK_ID`.

---

## Task 11: YEFCO partner page

**Source file:** `/mnt/c/Users/ADMIN/Downloads/members-20260624T121433Z-3-001/partners-members/partner-extracts/yefco-youth-empowerment-for-safe-futures-organization/index.md`

Read the full `index.md` before executing this task.

**Known data:**
- Full name: Youth Empowerment for Safe Futures Organization
- Acronym: YEFCO
- Gallery: 3 images available.

- [ ] **Step 1: Read source file**

Read the index.md above. Extract: mission, introduction, focus areas, address, phone, email, website, contact person.

- [ ] **Step 2: Create page**

```json
{
  "site": "miccos",
  "title": "Youth Empowerment for Safe Futures Organization",
  "slug": "yefco",
  "type": "page",
  "status": "publish",
  "excerpt": "[Use one-sentence mission extracted in Step 1]",
  "parent": PARTNERS_PAGE_ID
}
```
Note ID as `YEFCO_ID`.

- [ ] **Step 3: Apply Elementor layout**

`wp_update_elementor_data`, `PPID_` → `yefc_`, all 5 sections included (gallery with 3 images). Populate from data extracted in Step 1.

- [ ] **Step 4: Set page template**
```json
{"site": "miccos", "post_id": YEFCO_ID, "settings": {"template": "elementor_header_footer"}}
```

- [ ] **Step 5: Upload images manually**

Upload 3 images from `yefco-youth-empowerment-for-safe-futures-organization/images/` via WP Admin → Media. Set logo as Featured Image.

- [ ] **Step 6: Verify** — `wp_get_post` with `YEFCO_ID`.

---

## Task 12: Add Partners to nav menu

- [ ] **Step 1: Get current menus**

Use `wp_get_menus` on site `miccos`. Note the primary menu ID.

- [ ] **Step 2: Get current menu items**

Use `wp_get_menu_items` with the primary menu ID. Note the current item order — Partners goes after the last existing top-level item.

- [ ] **Step 3: Add Partners page to menu**

Use `wp_add_menu_item`:
```json
{
  "site": "miccos",
  "menu_id": PRIMARY_MENU_ID,
  "object_id": PARTNERS_PAGE_ID,
  "object": "page",
  "type": "post_type",
  "title": "Partners",
  "position": [last_position + 1]
}
```

- [ ] **Step 4: Verify**

Use `wp_get_menu_items` again with the primary menu ID. Confirm "Partners" appears as a top-level item linking to `/partners/`.

- [ ] **Step 5: Commit plan completion note**

```bash
git add -A
git commit -m "feat: implement MICCOS partners directory — shortcode, listing page, 9 partner profiles, nav menu"
```

---

## Post-Implementation Checklist

- [ ] Visit `https://miccsofkenya.org/partners/` — card grid renders, no PHP errors
- [ ] All 9 partner cards visible with correct names and excerpts
- [ ] Click any card → correct partner profile page loads with all 5 sections (or 4 for OBACODEP/PlanEarthWise)
- [ ] "Partners" appears in site nav menu
- [ ] Create a test draft page, set parent to Partners → confirm it does NOT appear in the live card grid (status = draft)
- [ ] Upload logo for one partner via WP Admin → set as Featured Image → card grid shows logo instead of initials fallback
- [ ] Regenerate Elementor CSS: WP Admin → Elementor → Tools → Regenerate Files & Data
