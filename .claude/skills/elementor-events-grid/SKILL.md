---
name: elementor-events-grid
description: Use when building a WordPress events or posts listing page with Elementor on Helpy/VL Core theme sites — covers split hero layout, CSS grid card design with category pills and date badges, CSS injection workaround for Elementor width settings, and vl-event widget customisation.
---

# Elementor Events Grid

## Overview

A proven pattern for events/posts listing pages on Helpy theme + VL Core plugin sites. Consists of three parts: a split hero, a branded card grid, and a CSS injection workaround. All three are required together — the hero and grid are independently editable in Elementor; the CSS injection is the technical enabler.

## When to Use

- Building or rebuilding an events page on a Helpy/VL Core WordPress site
- Client wants a grid layout for posts/events (not a list)
- Elementor container `width` settings are being ignored (they often are in this theme)
- Need category-coloured visual differentiation across cards

## Part 1 — Split Hero

Replace a full-width overlay hero with a two-panel split: dark text left, photo right.

**Elementor structure:**
```
Container mw_hero_01 [row, full_width, nowrap, min-height 440px]
├── Container mw_hero_left [50%, dark #1A1A1A, padding 72/64]
│   ├── Heading widget — gold label ("OUR EVENTS", 0.7rem, uppercase)
│   ├── Heading widget — H1 white title (2.6rem, weight 800)
│   ├── Divider widget — gold #F5A320, 3px, 44px wide, left-aligned
│   └── Text Editor — subtitle (0.9rem, rgba white .7)
└── Container mw_hero_right [50%, background-image cover, min-height 440px]
    └── (empty — photo is the background)
```

**Why CSS injection is needed:** Elementor's container `width` setting is ignored when the parent uses `content_width: boxed`. Setting `content_width: full_width` on the parent fixes this but the child `width` percentages still need forcing via CSS.

**Hero CSS (add via HTML widget or page settings):**
```css
[class*="elementor-element-mw_hero_01"].e-con { display:flex!important; flex-direction:row!important; flex-wrap:nowrap!important; min-height:440px!important; }
[class*="elementor-element-mw_hero_left"] { flex:0 0 50%!important; max-width:50%!important; }
[class*="elementor-element-mw_hero_right"] { flex:0 0 50%!important; max-width:50%!important; min-height:440px!important; }
@media(max-width:768px) {
  [class*="elementor-element-mw_hero_01"].e-con { flex-direction:column!important; }
  [class*="elementor-element-mw_hero_left"], [class*="elementor-element-mw_hero_right"] { flex:0 0 100%!important; max-width:100%!important; }
  [class*="elementor-element-mw_hero_right"] { min-height:260px!important; }
}
```

**Elementor editability:** Text, heading size, background colour on left panel, and background image on right panel are all editable via Elementor without touching code.

---

## Part 2 — CSS Grid Cards

Each event post becomes a card in a 3-column responsive grid.

**Card anatomy:**
- Top accent bar (5px, colour-coded by taxonomy category)
- Header row: date badge (day/month/year stacked) + category pill + time/venue
- Title (bold, links to post)
- Excerpt (3 lines, clamped)
- "Read More →" link

**Category colour system:**

| Category slug | Accent bar | Pill background | Pill text |
|---|---|---|---|
| `advocacy` | `#E8342A` (red) | `#fce8e7` | `#c0392b` |
| `governance` | `#F5A320` (gold) | `#fff8e6` | `#b7770c` |
| `community` | `#4CAF50` (green) | `#e8f5e9` | `#2e7d32` |

Add categories via: WP Admin → Events → Categories (taxonomy: `events-cat`). Assign per event via Quick Edit.

**Full grid CSS:**
```css
.mw-events-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; padding:8px 0 48px; }
@media(max-width:992px) { .mw-events-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:576px)  { .mw-events-grid { grid-template-columns:1fr; } }

.mw-event-card { background:#fff; border:1px solid #eee; border-radius:12px; padding:0; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 1px 4px rgba(0,0,0,.05); transition:box-shadow .2s,transform .2s; }
.mw-event-card:hover { box-shadow:0 6px 20px rgba(0,0,0,.1); transform:translateY(-3px); }
.mw-event-card::before { content:""; display:block; height:5px; background:#ccc; }
.mw-event-card[data-category="advocacy"]::before   { background:#E8342A; }
.mw-event-card[data-category="governance"]::before { background:#F5A320; }
.mw-event-card[data-category="community"]::before  { background:#4CAF50; }

.mw-event-card-header { display:flex; align-items:flex-start; gap:12px; padding:16px 20px 0; }
.mw-event-date-badge  { display:flex; flex-direction:column; align-items:center; background:#f5f5f5; border-radius:8px; min-width:50px; padding:8px 10px; text-align:center; flex-shrink:0; }
.mw-ev-day   { font-size:1.35rem; font-weight:800; color:#1A1A1A; line-height:1; display:block; }
.mw-ev-month { font-size:.55rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#888; display:block; }
.mw-ev-year  { font-size:.55rem; color:#bbb; display:block; }

.mw-event-meta { display:flex; flex-direction:column; gap:5px; padding-top:4px; }
.mw-ev-cat { display:inline-block; font-size:.58rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; border-radius:4px; padding:2px 8px; }
.mw-ev-cat-advocacy   { background:#fce8e7; color:#c0392b; }
.mw-ev-cat-governance { background:#fff8e6; color:#b7770c; }
.mw-ev-cat-community  { background:#e8f5e9; color:#2e7d32; }
.mw-ev-time { font-size:.65rem; color:#888; }

.mw-ev-title   { display:block; font-size:.88rem; font-weight:700; color:#1A1A1A; line-height:1.4; text-decoration:none; padding:12px 20px 8px; flex:1; }
.mw-ev-title:hover { color:#E8342A; }
.mw-ev-excerpt { font-size:.75rem; color:#666; line-height:1.6; padding:0 20px 12px; margin:0; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.mw-ev-link    { display:inline-flex; align-items:center; gap:6px; font-size:.72rem; font-weight:700; color:#E8342A; text-decoration:none; padding:0 20px 20px; margin-top:auto; }
.mw-ev-link:hover { color:#1A1A1A; }
.mw-events-grid-section .container { max-width:1100px; padding-top:32px; }
```

---

## Part 3 — vl-event Widget (layout-2) PHP Template

The VL Core `vl-event` widget drives the query (post type, count, ordering, category filter) from Elementor. Layout-2 is the grid variant — its default PHP output is an owl carousel which must be replaced.

**Location:** `/public_html/wp-content/plugins/vl-core/include/elementor/vl-event.php`

Replace the entire `layout-2` block (`elseif layout-2` to `elseif layout-3`) with:

```php
<?php elseif($settings['vl_design_style'] == 'layout-2'): ?>
<section class="mw-events-grid-section">
  <div class="container">
    <div class="mw-events-grid">
      <?php while ( $vl_event_posts->have_posts() ) :
        $vl_event_posts->the_post();
        $helpy_event_date = function_exists('tpmeta_field') ? tpmeta_field('helpy_event_date') : '';
        $helpy_event_time = function_exists('tpmeta_field') ? tpmeta_field('helpy_event_time') : '';
        $ev_month = $ev_day = $ev_year = '';
        if ( $helpy_event_date ) {
            $d = date_create_from_format( 'M j, Y', $helpy_event_date )
              ?: date_create_from_format( 'F j, Y', $helpy_event_date );
            if ( $d ) { $ev_month = date_format($d,'M'); $ev_day = date_format($d,'j'); $ev_year = date_format($d,'Y'); }
        }
        $ev_terms    = get_the_terms( get_the_ID(), 'events-cat' );
        $ev_cat_slug = ($ev_terms && !is_wp_error($ev_terms)) ? $ev_terms[0]->slug : '';
        $ev_cat_name = ($ev_terms && !is_wp_error($ev_terms)) ? $ev_terms[0]->name : '';
      ?>
      <div class="mw-event-card"<?php if ($ev_cat_slug) echo ' data-category="'.esc_attr($ev_cat_slug).'"'; ?>>
        <div class="mw-event-card-header">
          <div class="mw-event-date-badge">
            <span class="mw-ev-day"><?php echo esc_html($ev_day ?: $helpy_event_date); ?></span>
            <span class="mw-ev-month"><?php echo esc_html($ev_month); ?></span>
            <span class="mw-ev-year"><?php echo esc_html($ev_year); ?></span>
          </div>
          <div class="mw-event-meta">
            <?php if ($ev_cat_name): ?><span class="mw-ev-cat mw-ev-cat-<?php echo esc_attr($ev_cat_slug); ?>"><?php echo esc_html($ev_cat_name); ?></span><?php endif; ?>
            <?php if ($helpy_event_time): ?><span class="mw-ev-time"><?php echo esc_html($helpy_event_time); ?></span><?php endif; ?>
          </div>
        </div>
        <?php if (get_the_title()): ?>
          <a href="<?php the_permalink(); ?>" class="mw-ev-title"><?php echo wp_trim_words(get_the_title(), $settings['vl_blog_title_word'], ''); ?></a>
        <?php endif; ?>
        <?php if (!empty($settings['vl_event_content']) && $settings['vl_event_content'] === 'yes'):
              $limit = !empty($settings['vl_event_content_limit']) ? $settings['vl_event_content_limit'] : 20; ?>
          <p class="mw-ev-excerpt"><?php echo wp_trim_words(get_the_excerpt(), $limit, '&#8230;'); ?></p>
        <?php endif; ?>
        <?php if (!empty($settings['event_btn_text'])): ?>
          <a href="<?php the_permalink(); ?>" class="mw-ev-link">
            <?php echo esc_html($settings['event_btn_text']); ?>
            <?php if (isset($settings['show_btn_icon']) && $settings['show_btn_icon'] === 'yes'): ?><i class="fa-solid fa-arrow-right"></i><?php endif; ?>
          </a>
        <?php endif; ?>
      </div>
      <?php endwhile; wp_reset_query(); ?>
    </div>
  </div>
</section>
```

**Elementor controls this drives:** Layout selection, posts per page, category include/exclude, order, excerpt toggle, excerpt length, button text — all editable in Elementor without touching PHP again.

---

## Part 4 — CSS Injection Workaround

**Problem:** Elementor page settings `custom_css` does not regenerate reliably on this theme. Changes sit in the database but the compiled `.css` file isn't rebuilt.

**Solution:** Inject styles via a hidden HTML widget inside the Elementor content container. The `<style>` block renders inline in the page HTML and applies immediately.

**How to inject via PHP script:**
```php
$style_widget = [
    'id'         => 'ev_style_inject',
    'elType'     => 'widget',
    'widgetType' => 'html',
    'settings'   => [ 'html' => '<style>/* CSS here */</style>' ],
    'elements'   => [],
];
// Prepend to the content container's elements array
array_unshift( $content_container['elements'], $style_widget );
```

**Elementor editability:** The HTML widget is visible in Elementor and can be edited directly — but warn clients not to touch it. Label it in the HTML comment: `<!-- Layout CSS — do not edit -->`.

---

## Event Data Entry (WP Admin)

Each event post needs:
- **Title** — the event name
- **Date** — set via Pure Metafields: `helpy_event_date` in format `Sep 29, 2025`
- **Time** — `helpy_event_time` e.g. `3:00 PM` or `Community Hall, Abuoye · Half-day`
- **Excerpt** — 1–2 sentence description (shown in card)
- **Category** — assign via Events → Categories (`events-cat` taxonomy)
- **Thumbnail** — optional (hidden in grid layout by default)

---

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| CSS not applying after page settings update | Delete `/wp-content/uploads/elementor/css/post-{id}.css` and use HTML widget injection |
| `width: 50%` on child containers not respected | Add `content_width: full_width` on parent + inject flex CSS via HTML widget |
| layout-2 shows owl carousel | Patch `/vl-core/include/elementor/vl-event.php` — replace layout-2 block with CSS grid template |
| Category accent bar not showing | Check `data-category` attribute matches the taxonomy slug exactly |
| Events not appearing | Set `post_type: vl-events` in widget query; check events are published |
