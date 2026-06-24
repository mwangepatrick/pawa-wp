# MICCOS Partners Directory — Design Spec

**Site:** miccsofkenya.org (`miccos` alias)
**Date:** 2026-06-24
**Status:** Approved

## Overview

Add a Partners directory to miccsofkenya.org. Nine member organizations have submitted profile documents. Each partner gets their own page with a consistent Elementor layout. A listing page at `/partners/` auto-generates a card grid from those pages via shortcode.

## Approach

Option C — regular WordPress pages (no custom post type, no new plugin dependencies). Individual partner pages use a saved Elementor template for a consistent layout. A `[miccos_partners]` shortcode handles the auto-generated listing.

**Why not CPT:** The site uses VL Core + Elementor free (no Pro). Elementor free has no archive/loop builder for CPTs. Pages with a saved template achieve the same result with less complexity. The MICCOS team manages partners through WP admin; the page-parent system is familiar and guided.

## URL Structure

```
/partners/                                          ← listing page
/partners/caso/                                     ← CASO profile
/partners/forgotten-voices-foundation/
/partners/funguo-cbo/
/partners/obacodep/
/partners/planearthwise-cbo/
/partners/uawgo/
/partners/we4him-cbo/
/partners/weckma-cbo/
/partners/yefco/
```

WordPress handles URL nesting automatically via Page Attributes → Parent. New partners added in future: create page, set parent to Partners, publish — appears in listing automatically.

## Partners to Import (Initial 9)

| Slug | Full Name | Acronym |
|---|---|---|
| caso | Community Actions Support Organization | CASO |
| forgotten-voices-foundation | Forgotten Voices Foundation | FVF |
| funguo-cbo | Funguo Community Based Organization | FUNGUO CBO |
| obacodep | Obama Health & Community Development Programme | OBACODEP |
| planearthwise-cbo | PlanEarthWise Community Based Organization | PlanEarthWise |
| uawgo | Usalama Africa Women and Girls Organization | UAWGO |
| we4him-cbo | WE4HIM Community Based Organization | WE4HIM |
| weckma-cbo | WECKMA CBO | WECKMA |
| yefco | Youth Empowerment for Safe Futures Organization | YEFCO |

Source files: `C:\Users\ADMIN\Downloads\members-20260624T121433Z-3-001\partners-members\partner-extracts\`
WSL path: `/mnt/c/Users/ADMIN/Downloads/members-20260624T121433Z-3-001/partners-members/partner-extracts/`

## Component 1: Listing Shortcode

**Location:** Added to the existing `plugins/pawa-downloads` plugin (new file `partners-shortcode.php` included from the main plugin file). No new plugin install on the site.

**Shortcode tag:** `[miccos_partners]`

**Behaviour:**
- Queries all published pages with `post_parent` = the ID of the Partners page
- Orders by menu order (allows manual reordering from WP admin)
- Renders a responsive CSS grid of cards

**Card anatomy:**
- Featured image (org logo, square crop)
- Org full name (heading)
- Page excerpt (mission statement, max 2 lines)
- "View Profile" button linking to the partner page

**Grid:** 3 columns desktop → 2 tablet → 1 mobile. Styled via inline `<style>` block scoped to `.miccos-partners-grid` so it doesn't leak.

**Usage on listing page:** Drop `[miccos_partners]` into an Elementor Shortcode widget on the `/partners/` page.

## Component 2: Partners Listing Page

- New WordPress page: title "Partners", slug `partners`
- Built with Elementor
- Sections: hero banner (site colours, heading "Our Partners", short intro text) → shortcode widget with `[miccos_partners]`
- Added to site navigation menu

## Component 3: Elementor Partner Profile Template

One saved Elementor template named **"Partner Profile"** stored as a global page template. Applied to each of the 9 partner pages.

**Template sections in order:**

| # | Section | Content source |
|---|---|---|
| 1 | Hero | Featured image as background, org name + acronym as overlay heading, one-line mission as subheading |
| 2 | About | Rich text block — introduction / profile narrative |
| 3 | Focus Areas | Icon list or columns — thematic areas of work |
| 4 | Contact | Two-column layout: address / phone / email / website / contact person |
| 5 | Gallery | Elementor Image Gallery widget — activity photos |

## Component 4: Per-Partner Page Setup

For each of the 9 partners:
1. Create WP page with correct title and slug
2. Set Page Attributes → Parent: Partners
3. Set Featured Image (org logo from images folder)
4. Write Page Excerpt (mission statement, 1–2 sentences)
5. Apply "Partner Profile" Elementor template
6. Fill in template sections from `index.md` source data
7. Upload activity photos for gallery section
8. Publish

## Client Workflow (Adding Future Partners)

1. WP Admin → Pages → Add New
2. Title = org name, Slug = org slug
3. Page Attributes → Parent = Partners
4. Featured Image = logo
5. Excerpt = mission (shown on listing card)
6. Elementor → Apply Template → "Partner Profile"
7. Fill in sections, add gallery images
8. Publish → appears on `/partners/` automatically

## Out of Scope

- Filtering/search on the listing page (can be added later if membership grows)
- Partner-submitted content / self-service portal
- Download of partner profile PDFs (Pawa Downloads handles this separately if needed)
