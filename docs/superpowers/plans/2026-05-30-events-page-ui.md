# Events Page UI Improvement — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Visually upgrade `/our-events/` with a dark gradient hero, refined event card list (split date block, category pills, accent bars, descriptions), using a direct edit to the Helpy theme's `vl-event.php` widget.

**Architecture:** Edit `vl-event.php` layout-3 block to output richer HTML (split date spans, category data-attribute + pill); set post excerpts and `events-cat` taxonomy terms via one-shot PHP script; inject custom CSS via Elementor page settings; replace broken hero and add section label via Elementor data update.

**Tech Stack:** PHP (WordPress), Elementor JSON, custom CSS, FTP (pawa-wp-mcp), MCP tools (`wp_update_elementor_data`, `wp_update_elementor_page_settings`, `ftp_read`, `ftp_upload`, `wp_inspect`)

---

## File Map

| File | Action | What changes |
|---|---|---|
| `/public_html/wp-content/plugins/vl-core/include/elementor/vl-event.php` | Modify | layout-3 block only: split date, category data-attr + pill, excerpt wrapper |
| `/public_html/mwangaza-events-setup.php` | Create → run → auto-delete | Sets post_excerpt on 6181–6185, creates events-cat terms, assigns posts to terms |
| WP page 1943 `_elementor_data` | Modify via PHP script | Hero container prepended; Heading widget inserted before vl-event; `vl_event_content` enabled |
| WP page 1943 `_elementor_page_settings` | Modify via `wp_update_elementor_page_settings` | `custom_css` injected |

---

## Task 1 — Download and edit `vl-event.php`

**Files:**
- Download: `/public_html/wp-content/plugins/vl-core/include/elementor/vl-event.php` → `/home/pixel/projects/personal/wp/vl-event.php`
- Backup: `/home/pixel/projects/personal/wp/vl-event.php.bak`

- [ ] **Step 1.1 — Read the file from FTP**

Use `ftp_read` to fetch:
```
site: mwangaza
path: /public_html/wp-content/plugins/vl-core/include/elementor/vl-event.php
```
Save the full content locally at `/home/pixel/projects/personal/wp/vl-event.php` and a backup at `/home/pixel/projects/personal/wp/vl-event.php.bak`.

- [ ] **Step 1.2 — Replace the layout-3 block**

Locate the section starting with `<?php elseif($settings['vl_design_style'] == 'layout-3'): ?>` and ending just before `<?php elseif($settings['vl_design_style'] == 'layout-4'): ?>`.

Replace the entire layout-3 block with:

```php
<?php elseif($settings['vl_design_style'] == 'layout-3'): ?>

     <section class="vl-singlevent-iner sp1">
  <div class="container">
    <div class="row">

    <?php while ($vl_event_posts->have_posts()) :
          $vl_event_posts->the_post();
          $helpy_event_date = function_exists('tpmeta_field') ? tpmeta_field('helpy_event_date') : '';
          $helpy_event_time = function_exists('tpmeta_field') ? tpmeta_field('helpy_event_time') : '';

          // Split "Sep 29, 2025" into month / day / year
          $ev_month = $ev_day = $ev_year = '';
          if ( $helpy_event_date ) {
              $d = date_create_from_format( 'M j, Y', $helpy_event_date )
                ?: date_create_from_format( 'F j, Y', $helpy_event_date );
              if ( $d ) {
                  $ev_month = date_format( $d, 'M' );
                  $ev_day   = date_format( $d, 'j' );
                  $ev_year  = date_format( $d, 'Y' );
              }
          }

          // Category term (events-cat taxonomy)
          $ev_terms    = get_the_terms( get_the_ID(), 'events-cat' );
          $ev_cat_slug = ( $ev_terms && ! is_wp_error( $ev_terms ) ) ? $ev_terms[0]->slug : '';
          $ev_cat_name = ( $ev_terms && ! is_wp_error( $ev_terms ) ) ? $ev_terms[0]->name : '';
        ?>

      <!-- single event item -->
      <div class="col-lg-12 mb-50">
        <div class="event-bg-flex"<?php if ( $ev_cat_slug ) echo ' data-category="' . esc_attr( $ev_cat_slug ) . '"'; ?>>
          <div class="event-date">
            <?php if ( $ev_day ) : ?>
              <span class="ev-month"><?php echo esc_html( $ev_month ); ?></span>
              <span class="ev-day"><?php echo esc_html( $ev_day ); ?></span>
              <span class="ev-year"><?php echo esc_html( $ev_year ); ?></span>
            <?php else : ?>
              <p class="year"><?php echo esc_html( $helpy_event_date ); ?></p>
            <?php endif; ?>
          </div>
          <div class="event-content">
            <div class="event-meta">
              <?php if ( $ev_cat_name ) : ?>
                <span class="ev-cat-pill ev-cat-<?php echo esc_attr( $ev_cat_slug ); ?>"><?php echo esc_html( $ev_cat_name ); ?></span>
              <?php endif; ?>
              <p class="para"><?php echo esc_html( $helpy_event_time ); ?></p>
            </div>
            <?php if ( get_the_title() ) : ?>
            <a href="<?php the_permalink(); ?>" class="title"><?php echo wp_trim_words( get_the_title(), $settings['vl_blog_title_word'], '' ); ?></a>
            <?php endif; ?>
            <?php if ( ! empty( $settings['vl_event_content'] ) && $settings['vl_event_content'] === 'yes' ) :
                  $vl_event_content_limit = ! empty( $settings['vl_event_content_limit'] ) ? $settings['vl_event_content_limit'] : 20; ?>
              <p class="para ev-excerpt"><?php echo wp_trim_words( get_the_excerpt(), $vl_event_content_limit, '…' ); ?></p>
            <?php endif; ?>
            <?php if ( ! empty( $settings['event_btn_text'] ) ) : ?>
              <a href="<?php the_permalink(); ?>" class="details">
                <?php echo esc_html( $settings['event_btn_text'] ); ?>
                <?php if ( isset( $settings['show_btn_icon'] ) && $settings['show_btn_icon'] === 'yes' ) : ?>
                  <span><i class="fa-solid fa-arrow-right"></i></span>
                <?php endif; ?>
              </a>
            <?php endif; ?>
          </div>
          <div class="event-thumb">
            <?php the_post_thumbnail( 'full' ); ?>
          </div>
        </div>
      </div>

      <?php endwhile; wp_reset_query(); ?>

  </div>
</section>
```

- [ ] **Step 1.3 — Upload the edited file**

Use `ftp_upload`:
```
site: mwangaza
local_path: /home/pixel/projects/personal/wp/vl-event.php
remote_path: /public_html/wp-content/plugins/vl-core/include/elementor/vl-event.php
```

- [ ] **Step 1.4 — Verify upload with a quick page fetch**

Use `wp_inspect` on `https://mwangazaintergrated.org/our-events/` — confirm the page still loads (no PHP fatal errors). The event cards will look unstyled at this point but the page must not be broken.

---

## Task 2 — Set excerpts, create categories, assign terms

**Files:**
- Create: `/home/pixel/projects/personal/wp/mwangaza-events-setup.php`

- [ ] **Step 2.1 — Write the one-shot PHP script**

Write to `/home/pixel/projects/personal/wp/mwangaza-events-setup.php`:

```php
<?php
if ( ! isset( $_GET['run'] ) || $_GET['run'] !== 'evsetup2025' ) { die( 'Unauthorized.' ); }
require_once( dirname( __FILE__ ) . '/wp-load.php' );

$log = [];

// ── Excerpts ─────────────────────────────────────────────────────────────────
$excerpts = [
    6181 => 'Meeting between Mwangaza and KENGEN to review the stalled implementation of Parliament\'s recommendations from Petition No. 002 of 2022, and agree on a concrete roadmap for action.',
    6182 => 'Annual General Meeting to reform the constitution, upgrade from SHG to full CBO governance model, and elect interim board members. Interim Board of Management established at the meeting.',
    6183 => 'Community education session targeting youth and couples on the dangers of alcohol, bhang, and tobacco use. Brought together 25+ participants for a half-day awareness campaign at Abuoye.',
    6184 => 'Formal adoption of the new CBO constitution, confirmation of office bearers, and establishment of bank account by-laws. All signatories signed; Equity Bank (Oyugis) account confirmed.',
    6185 => 'Chairperson Woowill Odhiambo Lala addressed Hon. James Opiyo Wandayi (CS Energy and Petroleum), requesting electricity connection and full follow-through on Petition No. 002 of 2022 recommendations.',
];

foreach ( $excerpts as $id => $excerpt ) {
    $result = wp_update_post( [ 'ID' => $id, 'post_excerpt' => $excerpt ] );
    $log[]  = ( is_wp_error( $result ) ? 'FAILED' : 'OK' ) . " excerpt [$id]";
}

// ── Categories ────────────────────────────────────────────────────────────────
$cats = [
    'advocacy'   => 'Advocacy',
    'governance' => 'Governance',
    'community'  => 'Community',
];
$term_ids = [];
foreach ( $cats as $slug => $name ) {
    $existing = get_term_by( 'slug', $slug, 'events-cat' );
    if ( $existing ) {
        $term_ids[ $slug ] = $existing->term_id;
        $log[] = "Term exists: $slug ({$existing->term_id})";
    } else {
        $t = wp_insert_term( $name, 'events-cat', [ 'slug' => $slug ] );
        if ( is_wp_error( $t ) ) {
            $log[] = "FAILED term: $slug — " . $t->get_error_message();
        } else {
            $term_ids[ $slug ] = $t['term_id'];
            $log[] = "Created term: $slug ({$t['term_id']})";
        }
    }
}

// ── Assignments ───────────────────────────────────────────────────────────────
$assignments = [
    'advocacy'   => [ 6181, 6185 ],   // KENGEN, CS Energy
    'governance' => [ 6182, 6184 ],   // AGM, Constitution
    'community'  => [ 6183 ],         // Drug-Free
];
foreach ( $assignments as $slug => $post_ids ) {
    if ( empty( $term_ids[ $slug ] ) ) continue;
    foreach ( $post_ids as $pid ) {
        $r    = wp_set_object_terms( $pid, [ (int) $term_ids[ $slug ] ], 'events-cat' );
        $log[] = ( is_wp_error( $r ) ? 'FAILED' : 'OK' ) . " assign $slug → [$pid]";
    }
}

echo '<pre>' . implode( "\n", array_map( 'esc_html', $log ) ) . '</pre>';
echo '<p><a href="https://mwangazaintergrated.org/our-events/">View events →</a></p>';
unlink( __FILE__ );
echo '<p><em>Script removed.</em></p>';
```

- [ ] **Step 2.2 — Upload and run the script**

Upload:
```
site: mwangaza
local_path: /home/pixel/projects/personal/wp/mwangaza-events-setup.php
remote_path: /public_html/mwangaza-events-setup.php
```

Fetch via `WebFetch`: `https://mwangazaintergrated.org/mwangaza-events-setup.php?run=evsetup2025`

Expected output — all lines must be `OK`, no `FAILED`:
```
OK excerpt [6181]
OK excerpt [6182]
OK excerpt [6183]
OK excerpt [6184]
OK excerpt [6185]
Created term: advocacy (...)   ← or "Term exists" if already created
Created term: governance (...)
Created term: community (...)
OK assign advocacy → [6181]
OK assign advocacy → [6185]
OK assign governance → [6182]
OK assign governance → [6184]
OK assign community → [6183]
Script removed.
```

---

## Task 3 — Enable excerpt display in Elementor widget

**Files:**
- Modify: WP page 1943 `_elementor_data` via `wp_update_elementor_data`

- [ ] **Step 3.1 — Enable `vl_event_content`**

The current Elementor JSON has no `vl_event_content` key (defaults to empty/disabled). Add it set to `"yes"` by inserting it alongside the existing `vl_event_content_limit` key.

Use `wp_update_elementor_data` with two replacements — use `vl_blog_title_word` as a stable anchor (never been changed) to insert the new settings, then bump the content limit:

```
site: mwangaza
post_id: 1943
post_type: pages
replacements:
  - search: "\"vl_blog_title_word\":\"10\""
    replace: "\"vl_event_content\":\"yes\",\"vl_event_content_limit\":\"20\",\"vl_blog_title_word\":\"10\""
```

If this returns "no match" (meaning `vl_event_content` was already added), skip to Step 3.2.

- [ ] **Step 3.2 — Verify**

Use `wp_inspect` on `https://mwangazaintergrated.org/our-events/` — confirm excerpt text appears under each event title. (Styling not yet applied — this is just a data check.)

---

## Task 4 — Inject custom CSS

**Files:**
- Modify: WP page 1943 `_elementor_page_settings` via `wp_update_elementor_page_settings`

- [ ] **Step 4.1 — Load the tool schema**

Run `ToolSearch` for `mcp__pawa-wp-mcp__wp_update_elementor_page_settings` to load its schema before calling it.

- [ ] **Step 4.2 — Inject CSS**

Call `wp_update_elementor_page_settings`:
```
site: mwangaza
post_id: 1943
settings:
  custom_css: <CSS below>
```

CSS to inject:
```css
/* ── Events Page — Refined List (2026-05-30) ─────────────── */

/* Container width */
.vl-singlevent-iner.sp1 .container { max-width: 760px; padding-top: 32px; }

/* Card */
.event-bg-flex {
  display: flex !important; flex-direction: row !important; align-items: stretch !important;
  background: #fff !important; border: 1px solid #e2e2e2 !important;
  border-radius: 10px !important; overflow: hidden !important;
  box-shadow: 0 1px 4px rgba(0,0,0,.05) !important;
  transition: box-shadow .2s, transform .2s !important; position: relative !important;
}
.event-bg-flex:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1) !important; transform: translateY(-2px) !important; }

/* Accent bar */
.event-bg-flex::before {
  content: '' !important; display: block !important;
  width: 5px !important; flex-shrink: 0 !important; background: #ccc !important;
}
.event-bg-flex[data-category="advocacy"]::before  { background: #E8342A !important; }
.event-bg-flex[data-category="governance"]::before { background: #F5A320 !important; }
.event-bg-flex[data-category="community"]::before  { background: #4CAF50 !important; }

/* Date block */
.event-bg-flex .event-date {
  display: flex !important; flex-direction: column !important;
  align-items: center !important; justify-content: center !important;
  min-width: 68px !important; width: 68px !important; flex-shrink: 0 !important;
  background: #f5f5f5 !important; border-right: 1px solid #e2e2e2 !important;
  padding: 16px 8px !important; text-align: center !important;
}
.event-bg-flex .event-date .year   { display: none !important; }
.event-bg-flex .event-date .ev-month {
  display: block !important; font-size: .58rem !important; font-weight: 700 !important;
  text-transform: uppercase !important; letter-spacing: .1em !important; color: #777 !important; margin-bottom: 2px !important;
}
.event-bg-flex .event-date .ev-day {
  display: block !important; font-size: 1.5rem !important;
  font-weight: 800 !important; color: #1A1A1A !important; line-height: 1 !important;
}
.event-bg-flex .event-date .ev-year {
  display: block !important; font-size: .6rem !important; color: #aaa !important; margin-top: 3px !important;
}

/* Content area */
.event-bg-flex .event-content { flex: 1 !important; padding: 14px 16px !important; min-width: 0 !important; }

/* Meta row */
.event-bg-flex .event-meta {
  display: flex !important; align-items: center !important;
  gap: 8px !important; flex-wrap: wrap !important; margin-bottom: 5px !important;
}
.event-bg-flex .event-meta .para { font-size: .68rem !important; color: #666 !important; margin: 0 !important; }
.event-bg-flex .event-meta .para::before { content: "📍 "; }

/* Category pill */
.ev-cat-pill {
  display: inline-block !important; font-size: .6rem !important; font-weight: 700 !important;
  text-transform: uppercase !important; letter-spacing: .07em !important;
  border-radius: 4px !important; padding: 2px 7px !important;
}
.ev-cat-advocacy   { background: #fce8e7 !important; color: #c0392b !important; }
.ev-cat-governance { background: #fff8e6 !important; color: #b7770c !important; }
.ev-cat-community  { background: #e8f5e9 !important; color: #2e7d32 !important; }

/* Title */
.event-bg-flex .event-content a.title {
  display: block !important; font-size: .95rem !important; font-weight: 700 !important;
  color: #1A1A1A !important; line-height: 1.3 !important;
  margin-bottom: 6px !important; text-decoration: none !important;
}
.event-bg-flex .event-content a.title:hover { color: #E8342A !important; }

/* Excerpt */
.event-bg-flex .ev-excerpt {
  font-size: .75rem !important; color: #666 !important; line-height: 1.55 !important;
  margin-bottom: 8px !important; display: -webkit-box !important;
  -webkit-line-clamp: 2 !important; -webkit-box-orient: vertical !important; overflow: hidden !important;
}

/* Read More */
.event-bg-flex .details { font-size: .72rem !important; font-weight: 700 !important; color: #E8342A !important; text-decoration: none !important; }
.event-bg-flex .details:hover { color: #1A1A1A !important; }

/* Hide thumb (no images on these events) */
.event-bg-flex .event-thumb { display: none !important; }

/* Card spacing */
.vl-singlevent-iner.sp1 .col-lg-12.mb-50 { margin-bottom: 14px !important; }
```

---

## Task 5 — Replace broken hero + add section label

**Files:**
- Create: `/home/pixel/projects/personal/wp/mwangaza-hero-update.php`

The current page 1943 `_elementor_data` has one top-level container (`id: 3bc7d24`) holding the vl-event widget (`id: 7ed1046`). This task:
1. Prepends a hero container to the data array
2. Inserts a Heading widget inside `3bc7d24` before the vl-event widget

- [ ] **Step 5.1 — Write the Elementor update script**

Write to `/home/pixel/projects/personal/wp/mwangaza-hero-update.php`:

```php
<?php
if ( ! isset( $_GET['run'] ) || $_GET['run'] !== 'herofix2025' ) { die( 'Unauthorized.' ); }
require_once( dirname( __FILE__ ) . '/wp-load.php' );

$page_id = 1943;
$raw     = get_post_meta( $page_id, '_elementor_data', true );
$data    = json_decode( $raw, true );

if ( ! $data || json_last_error() !== JSON_ERROR_NONE ) {
    die( 'Could not parse Elementor data: ' . json_last_error_msg() );
}

// ── 1. Hero container ─────────────────────────────────────────────────────────
$hero = [
    'id'       => 'mw_hero_01',
    'elType'   => 'container',
    'settings' => [
        'flex_direction'             => 'column',
        'content_width'              => 'full',
        'text_align'                 => 'center',
        'padding'                    => [ 'unit' => 'px', 'top' => '44', 'right' => '32', 'bottom' => '44', 'left' => '32', 'isLinked' => false ],
        'background_background'      => 'gradient',
        'background_color'           => '#1A1A1A',
        'background_color_b'         => '#2d2d2d',
        'background_gradient_angle'  => [ 'unit' => 'deg', 'size' => 135 ],
        'background_overlay_background' => 'classic',
        'background_overlay_color'   => 'rgba(245,163,32,0.12)',
    ],
    'elements' => [
        // Breadcrumb
        [
            'id'         => 'mw_hero_bc',
            'elType'     => 'widget',
            'widgetType' => 'text-editor',
            'settings'   => [
                'editor' => '<p style="font-size:.65rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.1em;margin:0 0 8px">Home &rsaquo; Events</p>',
            ],
            'elements'   => [],
        ],
        // H1 Heading
        [
            'id'         => 'mw_hero_h1',
            'elType'     => 'widget',
            'widgetType' => 'heading',
            'settings'   => [
                'title'      => 'Our Events',
                'header_size'=> 'h1',
                'typography_typography'  => 'custom',
                'typography_font_weight' => '800',
                'typography_font_size'   => [ 'unit' => 'rem', 'size' => 2 ],
                'title_color' => '#ffffff',
            ],
            'elements'   => [],
        ],
        // Gold divider
        [
            'id'         => 'mw_hero_div',
            'elType'     => 'widget',
            'widgetType' => 'divider',
            'settings'   => [
                'color'  => [ 'color' => '#F5A320' ],
                'weight' => [ 'unit' => 'px', 'size' => 3 ],
                'width'  => [ 'unit' => 'px', 'size' => 44 ],
                'align'  => 'center',
                'gap'    => [ 'unit' => 'px', 'size' => 10 ],
            ],
            'elements'   => [],
        ],
        // Subtitle
        [
            'id'         => 'mw_hero_sub',
            'elType'     => 'widget',
            'widgetType' => 'text-editor',
            'settings'   => [
                'editor' => '<p style="font-size:.82rem;color:rgba(255,255,255,.6);max-width:420px;margin:0 auto;line-height:1.6">Community meetings, advocacy campaigns, training workshops and public events across Kabondo East Ward and Rachuonyo East Sub-County.</p>',
            ],
            'elements'   => [],
        ],
    ],
    'isInner' => false,
];

// ── 2. Section label heading widget ──────────────────────────────────────────
$section_label = [
    'id'         => 'mw_sec_lbl',
    'elType'     => 'widget',
    'widgetType' => 'heading',
    'settings'   => [
        'title'       => '2025 Events — All Completed',
        'header_size' => 'p',
        'typography_typography'   => 'custom',
        'typography_font_size'    => [ 'unit' => 'rem', 'size' => 0.65 ],
        'typography_font_weight'  => '700',
        'typography_text_transform' => 'uppercase',
        'typography_letter_spacing' => [ 'unit' => 'px', 'size' => 1.5 ],
        'title_color' => '#888888',
        'margin'      => [ 'unit' => 'px', 'top' => '32', 'right' => '0', 'bottom' => '16', 'left' => '0', 'isLinked' => false ],
    ],
    'elements' => [],
];

// ── 3. Mutate the data ────────────────────────────────────────────────────────

// Prepend hero to top-level array (guard: don't add twice)
$already_has_hero = false;
foreach ( $data as $el ) {
    if ( isset( $el['id'] ) && $el['id'] === 'mw_hero_01' ) { $already_has_hero = true; break; }
}
if ( ! $already_has_hero ) {
    array_unshift( $data, $hero );
}

// Find container 3bc7d24 and prepend section label before vl-event widget
foreach ( $data as &$container ) {
    if ( ! isset( $container['id'] ) || $container['id'] !== '3bc7d24' ) continue;
    $already_has_label = false;
    foreach ( $container['elements'] as $el ) {
        if ( isset( $el['id'] ) && $el['id'] === 'mw_sec_lbl' ) { $already_has_label = true; break; }
    }
    if ( ! $already_has_label ) {
        array_unshift( $container['elements'], $section_label );
    }
    break;
}
unset( $container );

// ── 4. Save ───────────────────────────────────────────────────────────────────
$encoded = wp_slash( wp_json_encode( $data ) );
update_post_meta( $page_id, '_elementor_data', $encoded );

// Force Elementor CSS regeneration for this page
delete_post_meta( $page_id, '_elementor_css' );

echo '<p>Done. Hero and section label added.</p>';
echo '<p><a href="https://mwangazaintergrated.org/our-events/">View page →</a></p>';
unlink( __FILE__ );
echo '<p><em>Script removed.</em></p>';
```

- [ ] **Step 5.2 — Upload and run**

Upload:
```
site: mwangaza
local_path: /home/pixel/projects/personal/wp/mwangaza-hero-update.php
remote_path: /public_html/mwangaza-hero-update.php
```

Fetch via `WebFetch`: `https://mwangazaintergrated.org/mwangaza-hero-update.php?run=herofix2025`

Expected: `Done. Hero and section label added. Script removed.`

---

## Task 6 — Visual verification

- [ ] **Step 6.1 — Full audit**

Run `wp_audit` on `https://mwangazaintergrated.org/our-events/`

Check the screenshot against these pass criteria:
- [ ] Hero: dark background, "Our Events" heading visible, gold rule, subtitle text — **no yellow spinner**
- [ ] Section label: "2025 Events — All Completed" in small grey uppercase
- [ ] 5 event cards visible, each with split date block (Month / Day / Year), category pill, venue, title, excerpt, "Read More →"
- [ ] Accent bars: red for advocacy events, gold for governance, green for community
- [ ] No PHP errors in console errors list

- [ ] **Step 6.2 — Check individual event cards**

Run `wp_inspect` on `https://mwangazaintergrated.org/our-events/` and zoom in on the event list. Confirm:
- Date block shows three stacked elements (month small, day large, year tiny)
- Category pills appear with correct tint colours
- Excerpt text is visible (2 lines, grey)
- "Read More →" is red

- [ ] **Step 6.3 — Commit local file changes**

```bash
git add vl-event.php docs/superpowers/plans/2026-05-30-events-page-ui.md
git commit -m "feat: improve events page UI — refined list + dark hero"
```

---

## Rollback

If vl-event.php causes a PHP fatal error after upload:
```
ftp_upload: vl-event.php.bak → /public_html/wp-content/plugins/vl-core/include/elementor/vl-event.php
```
(Backup was saved in Step 1.1.)

If Elementor data is corrupted after Task 5, restore from the JetBackup plugin in WP admin or re-run the original hero script from Session history.
