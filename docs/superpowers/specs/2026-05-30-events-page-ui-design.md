# Events Page UI Improvement — Design Spec
**Date:** 2026-05-30
**Page:** https://mwangazaintergrated.org/our-events/ (WP page ID: 1943)
**Status:** Approved — ready for implementation planning

---

## What We're Building

A visual upgrade of the `/our-events/` page — no new data, no plugin changes. The 5 real Mwangaza events are already live; this improves how they look.

**Two components:**
1. Replace the broken hero section (yellow spinner, 404 background) with a dark gradient header
2. Transform the bare event list into the Refined List design — bold date block, category pill, short description, accent bar

---

## Component 1 — Hero Section

### Current state
The page uses a Helpy theme `vl-hero` widget whose background image (`hero2-main-bg.png`) is a 404. The result is a yellow-background spinner that occupies ~300px at the top of the page.

### Design
Dark gradient header, no image dependency.

```
┌─────────────────────────────────────────────────────────┐
│  [dark gradient: #1A1A1A → #2d2d2d + gold overlay tint] │
│                                                          │
│            Home › Events          ← breadcrumb          │
│             Our Events            ← H1, white, bold     │
│          ──────────────           ← gold rule, 44px     │
│   Community meetings, advocacy campaigns...  ← subtitle │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

**Specs:**
- Background: `linear-gradient(135deg, #1A1A1A 0%, #2d2d2d 100%)` + `rgba(245,163,32,0.12)` tint overlay
- Padding: 44px top/bottom, 32px sides
- Breadcrumb: `Home › Events`, 0.65rem, `rgba(255,255,255,0.45)`, uppercase spaced
- H1: "Our Events", 2rem, weight 800, white
- Gold rule: 44px wide, 3px tall, `#F5A320`, centred, margin 10px above/below subtitle
- Subtitle: "Community meetings, advocacy campaigns, training workshops and public events across Kabondo East Ward and Rachuonyo East Sub-County." — 0.82rem, `rgba(255,255,255,0.6)`, max-width 420px, centred

**Implementation method:** Replace the broken hero Elementor widget with a new Elementor container. Use container background (dark gradient), then add three child widgets: Text (breadcrumb), Heading (H1), Divider (gold), Text (subtitle). Apply page-level custom CSS for the overlay tint.

---

## Component 2 — Events List (Refined List)

### Current state
The `vl-event` widget renders `layout-3` — a plain row with a narrow date column, venue badge, title and "Read More" link. No description is shown (widget setting disabled). No visual hierarchy.

### Design

Each event renders as a card:

```
┌──[5px accent bar]──────────────────────────────────────┐
│  [date block]  │  [Category Pill]  [Venue]             │
│   Month        │  Event Title (bold, 0.95rem)           │
│   Day (large)  │  Short description, 2 lines, grey      │
│   Year (small) │  Read More →                           │
└────────────────────────────────────────────────────────┘
```

**Date block:** Light grey background (`#f5f5f5`), right-bordered. Month in uppercase 0.58rem, Day in 1.5rem weight-800, Year in 0.6rem grey.

**Accent bar (5px left strip) — colour by category:**
| Category | Colour | Events |
|---|---|---|
| Advocacy | `#E8342A` (Unity Red) | Address to CS for Energy, KENGEN Forum |
| Governance | `#F5A320` (Gold) | AGM, Constitution Adoption |
| Community | `#4CAF50` (Green) | Drug-Free Campaign |

**Category pill:** Coloured label chip above the title. Advocacy = red tint, Governance = gold tint, Community = green tint.

**Description:** 2-line excerpt from post content. Enabled by setting `vl_event_content: yes` in the Elementor widget and setting a `post_excerpt` on each event post.

**"Read More →":** Keep existing link. Style in Unity Red, bold, no underline.

**Section label:** Grey uppercase "2025 Events — All Completed" above the list, with a full-width dividing line.

### Implementation method

**Chosen approach: edit `vl-event.php` directly** (Option 2). The VL Core plugin is a theme-bundled plugin at v1.0.0 with no update history — the file is safe to edit. Changes are isolated to the layout-3 render block.

1. **Edit `vl-event.php` layout-3 template** (FTP download → edit → upload):
   - Split `helpy_event_date` meta output into three `<span>` elements: `<span class="ev-month">`, `<span class="ev-day">`, `<span class="ev-year">` — parsed from the "Sep 29, 2025" string via PHP `date_create_from_format` or `explode`
   - Add `get_the_terms()` call for `events-cat` taxonomy; output the first term slug as a `data-category` attribute on `.event-bg-flex` and as a `<span class="ev-cat-pill">` label above the title
   - Ensure `the_excerpt()` renders (already controlled by widget setting — just confirm the HTML wrapper is present)
   - No changes to any other layout (layout-1, layout-2, layout-4)

2. **Enable description:** Update Elementor data for page 1943 — set `vl_event_content: "yes"` in widget settings via `wp_update_elementor_data`

3. **Set excerpts:** PHP one-shot script to set `post_excerpt` on event posts 6181–6185

4. **Assign categories:** PHP one-shot script to create 3 `events-cat` terms (`Advocacy`, `Governance`, `Community`) and assign:
   - Advocacy → posts 6181 (KENGEN), 6185 (CS Energy)
   - Governance → posts 6182 (AGM), 6184 (Constitution)
   - Community → post 6183 (Drug-Free Campaign)

5. **Custom CSS:** Inject into page 1943 Elementor page settings (`custom_css`) — styles the new HTML structure: card layout, date block, accent bar colour per `[data-category]`, pill colours, "Read More →" link style

6. **Section label:** Add a Heading widget above the vl-event widget in the Elementor container

7. **Hero:** Replace the broken hero widget with a new Elementor container (dark gradient background, Heading widget, Divider widget, Text widget)

---

## What Is Not Changing

- The 5 event posts (IDs 6181–6185) — content and meta stay as-is
- The `vl-event` widget type and other layouts (layout-1, 2, 4) — only layout-3 render block is touched
- The CTA banner below the list ("Your Help Can Change Lives") — already in the Elementor template, leave it
- No plugin installs, no theme file edits

---

## Files / Resources Affected

| Resource | Change |
|---|---|
| `vl-core/include/elementor/vl-event.php` | layout-3 block: date split, category data-attr + pill, excerpt wrapper |
| WP page 1943 Elementor data | Hero widget replaced; `vl_event_content` enabled; section label widget added; `custom_css` injected |
| Event posts 6181–6185 | `post_excerpt` set via PHP script |
| `events-cat` taxonomy | 3 terms created: Advocacy, Governance, Community |
| Event post terms | Posts assigned to their category terms |

---

## Out of Scope

- The two 404 assets from the Helpy theme (`vl-arow-shap-1.1.png`) — separate issue, leave for later
- Mobile-specific layout testing — verify after deploy but no separate breakpoint work scoped here
- The individual event detail pages (`/events/[slug]/`) — separate improvement task
