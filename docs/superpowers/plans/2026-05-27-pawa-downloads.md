# Pawa Downloads Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Pawa Downloads WordPress plugin — a rebranded, WP.org-ready version of `mwangaza-downloads` — and publish it to a public GitHub repo under `mwangepatrick`.

**Architecture:** Multi-file procedural plugin with a `includes/` folder split by responsibility (CPT, meta box, shortcode, styles). The main file defines constants and requires all includes unconditionally. No build step — pure PHP.

**Tech Stack:** PHP 7.4+, WordPress 5.8+, `gh` CLI (mwangepatrick account via Louis agent), `php -l` for syntax checks, FTP via pawa-wp-mcp for live verification on mwangaza.

---

## File Map

| File | Responsibility |
|------|---------------|
| `plugins/pawa-downloads/pawa-downloads.php` | Plugin header, constants, activation check, text domain, require includes |
| `plugins/pawa-downloads/includes/cpt.php` | Register `pawa_download` CPT |
| `plugins/pawa-downloads/includes/meta-box.php` | File URL meta box + media picker + save |
| `plugins/pawa-downloads/includes/shortcode.php` | `[pawa_downloads]` shortcode with `limit`/`order` attrs |
| `plugins/pawa-downloads/includes/styles.php` | Enqueue frontend CSS via `wp_add_inline_style` |
| `plugins/pawa-downloads/uninstall.php` | Delete all CPT posts on plugin deletion |
| `plugins/pawa-downloads/readme.txt` | WP.org listing file |
| `plugins/pawa-downloads/LICENSE` | GPL-2.0-or-later text |
| `plugins/pawa-downloads/.gitignore` | Repo ignores |
| `plugins/pawa-downloads/languages/pawa-downloads.pot` | Translation template |

All paths are relative to `/home/pixel/projects/personal/wp/`.

---

## Task 1: GitHub repo + local folder scaffold

**Files:**
- Create: `plugins/pawa-downloads/` (folder structure)
- Create: `plugins/pawa-downloads/.gitignore`

- [ ] **Step 1: Create the GitHub repo under mwangepatrick using Louis agent**

  Dispatch the Louis agent with this exact instruction:
  > "Create a new public GitHub repo named `pawa-downloads` under the mwangepatrick account. Use `GITHUB_TOKEN=$(gh auth token --user mwangepatrick) gh repo create mwangepatrick/pawa-downloads --public --description 'Manage and display downloadable files (PDF, Word, Excel) on WordPress using a simple shortcode.'` — no README, no .gitignore (we'll add our own). Confirm the repo URL."

  Expected: `https://github.com/mwangepatrick/pawa-downloads` created successfully.

- [ ] **Step 2: Create the plugin folder structure**

  ```bash
  mkdir -p /home/pixel/projects/personal/wp/plugins/pawa-downloads/includes
  mkdir -p /home/pixel/projects/personal/wp/plugins/pawa-downloads/languages
  ```

- [ ] **Step 3: Write `.gitignore`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/.gitignore`:

  ```
  # macOS
  .DS_Store

  # Windows
  Thumbs.db

  # Logs
  *.log

  # Environment
  .env

  # Node
  node_modules/

  # Zip archives
  *.zip
  ```

- [ ] **Step 4: Initialise git repo and link to GitHub**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git init
  git branch -M main
  GITHUB_TOKEN=$(gh auth token --user mwangepatrick) git remote add origin https://mwangepatrick:$(gh auth token --user mwangepatrick)@github.com/mwangepatrick/pawa-downloads.git
  ```

- [ ] **Step 5: Commit scaffold**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add .gitignore
  git commit -m "chore: initial repo scaffold"
  git push -u origin main
  ```

  Expected: push succeeds, repo visible at `https://github.com/mwangepatrick/pawa-downloads`.

---

## Task 2: Main plugin file

**Files:**
- Create: `plugins/pawa-downloads/pawa-downloads.php`

- [ ] **Step 1: Write `pawa-downloads.php`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/pawa-downloads.php`:

  ```php
  <?php
  /**
   * Plugin Name:       Pawa Downloads
   * Plugin URI:        https://github.com/mwangepatrick/pawa-downloads
   * Description:       Manage and display downloadable files (PDF, Word, Excel) using a simple shortcode.
   * Version:           1.0.0
   * Author:            Rahisi Solutions
   * Author URI:        https://rahisisolutions.com
   * License:           GPL-2.0-or-later
   * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
   * Text Domain:       pawa-downloads
   * Domain Path:       /languages
   * Requires at least: 5.8
   * Requires PHP:      7.4
   */

  if ( ! defined( 'ABSPATH' ) ) exit;

  // ---------------------------------------------------------------------------
  // Constants
  // ---------------------------------------------------------------------------
  define( 'PAWA_DL_VERSION', '1.0.0' );
  define( 'PAWA_DL_DIR', plugin_dir_path( __FILE__ ) );
  define( 'PAWA_DL_URL', plugin_dir_url( __FILE__ ) );

  // ---------------------------------------------------------------------------
  // PHP version check — abort activation on PHP < 7.4
  // ---------------------------------------------------------------------------
  register_activation_hook( __FILE__, 'pawa_dl_activation_check' );
  function pawa_dl_activation_check() {
      if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
          deactivate_plugins( plugin_basename( __FILE__ ) );
          wp_die(
              sprintf(
                  /* translators: %s: current PHP version string */
                  esc_html__( 'Pawa Downloads requires PHP 7.4 or higher. Your server is running PHP %s. Please upgrade PHP and try again.', 'pawa-downloads' ),
                  PHP_VERSION
              ),
              esc_html__( 'Plugin Activation Error', 'pawa-downloads' ),
              [ 'back_link' => true ]
          );
      }
  }

  // ---------------------------------------------------------------------------
  // Load text domain
  // ---------------------------------------------------------------------------
  add_action( 'plugins_loaded', function () {
      load_plugin_textdomain(
          'pawa-downloads',
          false,
          dirname( plugin_basename( __FILE__ ) ) . '/languages/'
      );
  } );

  // ---------------------------------------------------------------------------
  // Load includes
  // ---------------------------------------------------------------------------
  require_once PAWA_DL_DIR . 'includes/cpt.php';
  require_once PAWA_DL_DIR . 'includes/meta-box.php';
  require_once PAWA_DL_DIR . 'includes/shortcode.php';
  require_once PAWA_DL_DIR . 'includes/styles.php';
  ```

- [ ] **Step 2: Verify syntax**

  ```bash
  php -l /home/pixel/projects/personal/wp/plugins/pawa-downloads/pawa-downloads.php
  ```

  Expected output: `No syntax errors detected in .../pawa-downloads.php`

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add pawa-downloads.php
  git commit -m "feat: add main plugin file with header, constants, and activation check"
  git push
  ```

---

## Task 3: CPT registration

**Files:**
- Create: `plugins/pawa-downloads/includes/cpt.php`

- [ ] **Step 1: Write `includes/cpt.php`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/cpt.php`:

  ```php
  <?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  add_action( 'init', 'pawa_dl_register_cpt' );
  function pawa_dl_register_cpt() {
      $labels = [
          'name'               => __( 'Downloads', 'pawa-downloads' ),
          'singular_name'      => __( 'Download', 'pawa-downloads' ),
          'add_new'            => __( 'Add New', 'pawa-downloads' ),
          'add_new_item'       => __( 'Add New Download', 'pawa-downloads' ),
          'edit_item'          => __( 'Edit Download', 'pawa-downloads' ),
          'new_item'           => __( 'New Download', 'pawa-downloads' ),
          'view_item'          => __( 'View Download', 'pawa-downloads' ),
          'search_items'       => __( 'Search Downloads', 'pawa-downloads' ),
          'not_found'          => __( 'No downloads found', 'pawa-downloads' ),
          'not_found_in_trash' => __( 'No downloads found in Trash', 'pawa-downloads' ),
          'menu_name'          => __( 'Downloads', 'pawa-downloads' ),
      ];

      register_post_type( 'pawa_download', [
          'labels'        => $labels,
          'public'        => false,
          'show_ui'       => true,
          'show_in_menu'  => true,
          'show_in_rest'  => false, // intentional: uses classic editor
          'supports'      => [ 'title', 'editor' ],
          'menu_icon'     => 'dashicons-download',
          'menu_position' => 20,
          'rewrite'       => false,
      ] );
  }
  ```

- [ ] **Step 2: Verify syntax**

  ```bash
  php -l /home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/cpt.php
  ```

  Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add includes/cpt.php
  git commit -m "feat: register pawa_download custom post type"
  git push
  ```

---

## Task 4: Meta box

**Files:**
- Create: `plugins/pawa-downloads/includes/meta-box.php`

- [ ] **Step 1: Write `includes/meta-box.php`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/meta-box.php`:

  ```php
  <?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  // ---------------------------------------------------------------------------
  // Enqueue WP media library — scoped to pawa_download edit screens only
  // ---------------------------------------------------------------------------
  add_action( 'admin_enqueue_scripts', 'pawa_dl_enqueue_media' );
  function pawa_dl_enqueue_media( $hook ) {
      global $post;
      if (
          ( 'post-new.php' === $hook || 'post.php' === $hook ) &&
          isset( $post->post_type ) &&
          'pawa_download' === $post->post_type
      ) {
          wp_enqueue_media();
      }
  }

  // ---------------------------------------------------------------------------
  // Register meta box
  // ---------------------------------------------------------------------------
  add_action( 'add_meta_boxes', 'pawa_dl_add_file_metabox' );
  function pawa_dl_add_file_metabox() {
      add_meta_box(
          'pawa_dl_download_file',
          __( 'Download File', 'pawa-downloads' ),
          'pawa_dl_render_file_metabox',
          'pawa_download',
          'normal',
          'high'
      );
  }

  // ---------------------------------------------------------------------------
  // Render meta box HTML
  // ---------------------------------------------------------------------------
  function pawa_dl_render_file_metabox( $post ) {
      $file_url = get_post_meta( $post->ID, '_pawa_dl_file_url', true );
      wp_nonce_field( 'pawa_dl_save_download', 'pawa_dl_nonce' );
      ?>
      <p>
          <label for="pawa_dl_file_url">
              <strong><?php esc_html_e( 'File URL', 'pawa-downloads' ); ?></strong>
          </label><br>
          <input
              type="text"
              id="pawa_dl_file_url"
              name="pawa_dl_file_url"
              value="<?php echo esc_attr( $file_url ); ?>"
              style="width:75%;margin-right:8px;"
              placeholder="https://"
          />
          <button type="button" class="button" id="pawa_dl_upload_btn">
              <?php esc_html_e( 'Choose File', 'pawa-downloads' ); ?>
          </button>
      </p>
      <p style="color:#666;font-size:12px;">
          <?php esc_html_e( 'Accepted: PDF, DOC, DOCX, XLS, XLSX. Upload via Choose File or paste a direct URL.', 'pawa-downloads' ); ?>
      </p>
      <script>
      jQuery( document ).ready( function ( $ ) {
          $( '#pawa_dl_upload_btn' ).on( 'click', function ( e ) {
              e.preventDefault();
              var frame = wp.media( {
                  title:  '<?php echo esc_js( __( 'Select File to Download', 'pawa-downloads' ) ); ?>',
                  button: { text: '<?php echo esc_js( __( 'Use this file', 'pawa-downloads' ) ); ?>' },
                  multiple: false
              } );
              frame.on( 'select', function () {
                  var attachment = frame.state().get( 'selection' ).first().toJSON();
                  $( '#pawa_dl_file_url' ).val( attachment.url );
              } );
              frame.open();
          } );
      } );
      </script>
      <?php
  }

  // ---------------------------------------------------------------------------
  // Save meta
  // ---------------------------------------------------------------------------
  add_action( 'save_post_pawa_download', 'pawa_dl_save_download_meta' );
  function pawa_dl_save_download_meta( $post_id ) {
      if ( ! isset( $_POST['pawa_dl_nonce'] ) ) return;
      if ( ! wp_verify_nonce( $_POST['pawa_dl_nonce'], 'pawa_dl_save_download' ) ) return;
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
      if ( ! current_user_can( 'edit_post', $post_id ) ) return;

      if ( isset( $_POST['pawa_dl_file_url'] ) ) {
          update_post_meta(
              $post_id,
              '_pawa_dl_file_url',
              esc_url_raw( $_POST['pawa_dl_file_url'] )
          );
      }
  }
  ```

- [ ] **Step 2: Verify syntax**

  ```bash
  php -l /home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/meta-box.php
  ```

  Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add includes/meta-box.php
  git commit -m "feat: add Download File meta box with media picker"
  git push
  ```

---

## Task 5: Shortcode

**Files:**
- Create: `plugins/pawa-downloads/includes/shortcode.php`

- [ ] **Step 1: Write `includes/shortcode.php`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/shortcode.php`:

  ```php
  <?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  add_shortcode( 'pawa_downloads', 'pawa_dl_shortcode' );
  function pawa_dl_shortcode( $atts ) {
      $atts = shortcode_atts(
          [
              'limit' => 12,
              'order' => 'DESC',
          ],
          $atts,
          'pawa_downloads'
      );

      $limit = intval( $atts['limit'] );
      $order = strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC';

      $downloads = get_posts( [
          'post_type'      => 'pawa_download',
          'post_status'    => 'publish',
          'posts_per_page' => ( $limit <= 0 ) ? -1 : $limit,
          'orderby'        => 'date',
          'order'          => $order,
      ] );

      if ( empty( $downloads ) ) {
          return '<p class="pawa-dl-empty">'
              . esc_html__( 'No downloads available yet. Check back soon.', 'pawa-downloads' )
              . '</p>';
      }

      $icons = [
          'pdf'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="#e53e3e"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8.5 15.5c0 .3-.2.5-.5.5H7v1H6v-4h2c.8 0 1.5.7 1.5 1.5v1zm4 .5H11v1h-1v-4h2.5c.8 0 1.5.7 1.5 1.5v1c0 .8-.7 1.5-1.5 1.5zM18 13h-3v4h1v-1.5h1.5v-1H16V14h2v-1zm-10 1h1v1H8v-1zm4 0h1v1h-1v-1z"/></svg>',
          'doc'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="#2b579a"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM7 13h10v1H7v-1zm0 3h10v1H7v-1zm0-6h5v1H7v-1z"/></svg>',
          'docx' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="#2b579a"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM7 13h10v1H7v-1zm0 3h10v1H7v-1zm0-6h5v1H7v-1z"/></svg>',
          'xls'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="#217346"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17l2-3-2-3h1.5l1.25 2 1.25-2H13.5l-2 3 2 3H12l-1.25-2L9.5 17H8z"/></svg>',
          'xlsx' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="#217346"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17l2-3-2-3h1.5l1.25 2 1.25-2H13.5l-2 3 2 3H12l-1.25-2L9.5 17H8z"/></svg>',
      ];
      $default_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" fill="#718096"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM7 13h10v1H7v-1zm0 3h10v1H7v-1zm0-6h5v1H7v-1z"/></svg>';

      ob_start();
      echo '<div class="pawa-dl-grid">';

      foreach ( $downloads as $item ) {
          $file_url = get_post_meta( $item->ID, '_pawa_dl_file_url', true );
          $ext      = strtolower( pathinfo( parse_url( (string) $file_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
          $icon     = isset( $icons[ $ext ] ) ? $icons[ $ext ] : $default_icon;
          $desc     = wpautop( wp_kses_post( $item->post_content ) );
          ?>
          <div class="pawa-dl-card">
              <div class="pawa-dl-icon"><?php echo $icon; ?></div>
              <div class="pawa-dl-body">
                  <h3 class="pawa-dl-title"><?php echo esc_html( $item->post_title ); ?></h3>
                  <?php if ( $desc ) : ?>
                      <div class="pawa-dl-desc"><?php echo $desc; ?></div>
                  <?php endif; ?>
                  <?php if ( $file_url ) : ?>
                      <a href="<?php echo esc_url( $file_url ); ?>"
                         class="pawa-dl-btn"
                         download
                         target="_blank"
                         rel="noopener noreferrer">
                          <?php esc_html_e( '↓ Download', 'pawa-downloads' ); ?>
                      </a>
                  <?php else : ?>
                      <span class="pawa-dl-soon">
                          <?php esc_html_e( 'Coming soon', 'pawa-downloads' ); ?>
                      </span>
                  <?php endif; ?>
              </div>
          </div>
          <?php
      }

      echo '</div>';
      return ob_get_clean();
  }
  ```

- [ ] **Step 2: Verify syntax**

  ```bash
  php -l /home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/shortcode.php
  ```

  Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add includes/shortcode.php
  git commit -m "feat: add [pawa_downloads] shortcode with limit and order attributes"
  git push
  ```

---

## Task 6: Frontend styles

**Files:**
- Create: `plugins/pawa-downloads/includes/styles.php`

- [ ] **Step 1: Write `includes/styles.php`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/styles.php`:

  ```php
  <?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  add_action( 'wp_enqueue_scripts', 'pawa_dl_enqueue_styles' );
  function pawa_dl_enqueue_styles() {
      // Register with no src — used purely as a carrier for wp_add_inline_style.
      // Loaded on every front-end page so it works with Elementor and other
      // page builders that store shortcodes outside post_content.
      wp_register_style( 'pawa-downloads', false, [], PAWA_DL_VERSION );
      wp_enqueue_style( 'pawa-downloads' );
      wp_add_inline_style( 'pawa-downloads', pawa_dl_get_css() );
  }

  function pawa_dl_get_css() {
      return '
  .pawa-dl-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
      padding: 40px 0;
  }
  .pawa-dl-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 24px;
      display: flex;
      gap: 16px;
      align-items: flex-start;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
      transition: box-shadow .2s, transform .2s;
  }
  .pawa-dl-card:hover {
      box-shadow: 0 6px 20px rgba(0,0,0,.12);
      transform: translateY(-2px);
  }
  .pawa-dl-icon {
      flex-shrink: 0;
      width: 40px;
      padding-top: 2px;
  }
  .pawa-dl-icon svg { display: block; }
  .pawa-dl-body { flex: 1; min-width: 0; }
  .pawa-dl-title {
      font-size: 1.05rem !important;
      font-weight: 600 !important;
      margin: 0 0 8px !important;
      color: #1a202c !important;
      line-height: 1.4 !important;
  }
  .pawa-dl-desc {
      font-size: .9rem;
      color: #6b7280;
      margin: 0 0 16px;
      line-height: 1.6;
  }
  .pawa-dl-desc p { margin: 0; }
  .pawa-dl-btn {
      display: inline-block;
      background: #2a7c5f;
      color: #fff !important;
      padding: 8px 20px;
      border-radius: 6px;
      font-size: .875rem;
      font-weight: 600;
      text-decoration: none !important;
      transition: background .2s;
      letter-spacing: .02em;
  }
  .pawa-dl-btn:hover { background: #1f5e47; color: #fff !important; }
  .pawa-dl-soon { font-size: .85rem; color: #9ca3af; font-style: italic; }
  .pawa-dl-empty { color: #6b7280; font-style: italic; padding: 40px 0; }
  @media (max-width: 600px) {
      .pawa-dl-grid { grid-template-columns: 1fr; }
  }';
  }
  ```

- [ ] **Step 2: Verify syntax**

  ```bash
  php -l /home/pixel/projects/personal/wp/plugins/pawa-downloads/includes/styles.php
  ```

  Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add includes/styles.php
  git commit -m "feat: enqueue frontend styles via wp_add_inline_style"
  git push
  ```

---

## Task 7: Uninstall script

**Files:**
- Create: `plugins/pawa-downloads/uninstall.php`

- [ ] **Step 1: Write `uninstall.php`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/uninstall.php`:

  ```php
  <?php
  // Guard: only run when WordPress triggers plugin deletion.
  // Prevents direct URL execution.
  if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

  $pawa_dl_posts = get_posts( [
      'post_type'      => 'pawa_download',
      'post_status'    => 'any',
      'posts_per_page' => -1,
      'fields'         => 'ids',
  ] );

  foreach ( $pawa_dl_posts as $pawa_dl_id ) {
      // true = force-delete (bypasses trash).
      // WordPress cascade-deletes all post meta automatically.
      wp_delete_post( $pawa_dl_id, true );
  }
  ```

- [ ] **Step 2: Verify syntax**

  ```bash
  php -l /home/pixel/projects/personal/wp/plugins/pawa-downloads/uninstall.php
  ```

  Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add uninstall.php
  git commit -m "feat: add uninstall.php to clean up CPT posts on plugin deletion"
  git push
  ```

---

## Task 8: readme.txt and LICENSE

**Files:**
- Create: `plugins/pawa-downloads/readme.txt`
- Create: `plugins/pawa-downloads/LICENSE`

- [ ] **Step 1: Write `readme.txt`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/readme.txt`:

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

  Manage and display downloadable files (PDF, Word, Excel) on your WordPress site using a simple shortcode.

  == Description ==

  Pawa Downloads lets you manage downloadable files through a dedicated **Downloads** section in your WordPress admin. Add PDFs, Word documents, Excel spreadsheets, and more — then display them anywhere on your site with a single shortcode.

  **Features:**

  * Custom post type for organising downloads
  * Media library integration — pick files directly from your WordPress media library
  * Responsive card grid layout with colour-coded file-type icons (PDF, Word, Excel)
  * Simple shortcode: `[pawa_downloads]`
  * Optional `limit` and `order` attributes
  * Works with Elementor and all major page builders
  * Translation-ready

  **Shortcode usage:**

  Display all downloads (up to 12, newest first):
  `[pawa_downloads]`

  Show 6 downloads, oldest first:
  `[pawa_downloads limit="6" order="ASC"]`

  Show all downloads:
  `[pawa_downloads limit="-1"]`

  == Installation ==

  1. Upload the `pawa-downloads` folder to the `/wp-content/plugins/` directory, or install via **Plugins > Add New** in WordPress.
  2. Activate the plugin through the **Plugins** screen.
  3. Go to **Downloads > Add New** to create your first download entry.
  4. Add the `[pawa_downloads]` shortcode to any page or post.

  == Frequently Asked Questions ==

  = How do I change the button colour? =

  The download button uses `#2a7c5f` (green) by default. Override it in your theme's Additional CSS (Appearance > Customise > Additional CSS):

  ```css
  .pawa-dl-btn {
      background: #your-colour;
  }
  .pawa-dl-btn:hover {
      background: #your-darker-colour;
  }
  ```

  = What file types are supported? =

  Any file type can be linked. PDF, DOC, DOCX, XLS, and XLSX files get colour-coded icons automatically (red, blue, green). All other file types display a generic grey document icon.

  = Does this work with Elementor? =

  Yes. The plugin's stylesheet is loaded on every front-end page, so it works correctly with Elementor, Divi, Beaver Builder, and other page builders that store shortcodes outside of `post_content`.

  = Can I limit how many downloads are shown? =

  Yes. Use the `limit` attribute: `[pawa_downloads limit="6"]`. Set `limit="-1"` to show all downloads with no cap.

  = Can I sort downloads oldest-first? =

  Yes. Use `[pawa_downloads order="ASC"]`.

  == Screenshots ==

  1. The Downloads admin list view.
  2. Adding a download with the media library picker.
  3. The front-end card grid with file-type icons and download buttons.

  == Changelog ==

  = 1.0.0 =
  * Initial release.

  == Upgrade Notice ==

  = 1.0.0 =
  Initial release.
  ```

- [ ] **Step 2: Write `LICENSE`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/LICENSE`:

  ```
  GNU GENERAL PUBLIC LICENSE
  Version 2, June 1991

  Copyright (C) 2026 Rahisi Solutions
  https://rahisisolutions.com

  Everyone is permitted to copy and distribute verbatim copies
  of this license document, but changing it is not allowed.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software Foundation,
  Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

  The full license text is available at:
  https://www.gnu.org/licenses/gpl-2.0.html
  ```

- [ ] **Step 3: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add readme.txt LICENSE
  git commit -m "docs: add WP.org readme.txt and GPL-2.0 LICENSE"
  git push
  ```

---

## Task 9: POT translation template

**Files:**
- Create: `plugins/pawa-downloads/languages/pawa-downloads.pot`

> WP-CLI is not installed locally. Write the POT file manually — it covers all translatable strings in the plugin. Regenerate with `wp i18n make-pot . languages/pawa-downloads.pot` once WP-CLI is available, or run it on the remote server (which has WP-CLI at `~/.wp-cli`).

- [ ] **Step 1: Write `languages/pawa-downloads.pot`**

  Create `/home/pixel/projects/personal/wp/plugins/pawa-downloads/languages/pawa-downloads.pot`:

  ```
  # Copyright (C) 2026 Rahisi Solutions
  # This file is distributed under the GPL-2.0-or-later license.
  msgid ""
  msgstr ""
  "Project-Id-Version: Pawa Downloads 1.0.0\n"
  "Report-Msgid-Bugs-To: https://github.com/mwangepatrick/pawa-downloads/issues\n"
  "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
  "Language-Team: LANGUAGE <LL@li.org>\n"
  "MIME-Version: 1.0\n"
  "Content-Type: text/plain; charset=UTF-8\n"
  "Content-Transfer-Encoding: 8bit\n"
  "POT-Creation-Date: 2026-05-27T00:00:00+00:00\n"
  "PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
  "X-Domain: pawa-downloads\n"

  #. Plugin Name
  msgid "Pawa Downloads"
  msgstr ""

  #. Plugin URI
  msgid "https://github.com/mwangepatrick/pawa-downloads"
  msgstr ""

  #. Description
  msgid "Manage and display downloadable files (PDF, Word, Excel) using a simple shortcode."
  msgstr ""

  #. Author
  msgid "Rahisi Solutions"
  msgstr ""

  #: includes/cpt.php
  msgid "Downloads"
  msgstr ""

  #: includes/cpt.php
  msgid "Download"
  msgstr ""

  #: includes/cpt.php
  msgid "Add New"
  msgstr ""

  #: includes/cpt.php
  msgid "Add New Download"
  msgstr ""

  #: includes/cpt.php
  msgid "Edit Download"
  msgstr ""

  #: includes/cpt.php
  msgid "New Download"
  msgstr ""

  #: includes/cpt.php
  msgid "View Download"
  msgstr ""

  #: includes/cpt.php
  msgid "Search Downloads"
  msgstr ""

  #: includes/cpt.php
  msgid "No downloads found"
  msgstr ""

  #: includes/cpt.php
  msgid "No downloads found in Trash"
  msgstr ""

  #: includes/meta-box.php
  msgid "Download File"
  msgstr ""

  #: includes/meta-box.php
  msgid "File URL"
  msgstr ""

  #: includes/meta-box.php
  msgid "Choose File"
  msgstr ""

  #: includes/meta-box.php
  msgid "Accepted: PDF, DOC, DOCX, XLS, XLSX. Upload via Choose File or paste a direct URL."
  msgstr ""

  #: includes/meta-box.php
  msgid "Select File to Download"
  msgstr ""

  #: includes/meta-box.php
  msgid "Use this file"
  msgstr ""

  #: includes/shortcode.php
  msgid "No downloads available yet. Check back soon."
  msgstr ""

  #: includes/shortcode.php
  msgid "↓ Download"
  msgstr ""

  #: includes/shortcode.php
  msgid "Coming soon"
  msgstr ""

  #: pawa-downloads.php
  msgid "Pawa Downloads requires PHP 7.4 or higher. Your server is running PHP %s. Please upgrade PHP and try again."
  msgstr ""

  #: pawa-downloads.php
  msgid "Plugin Activation Error"
  msgstr ""
  ```

- [ ] **Step 2: Commit**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git add languages/pawa-downloads.pot
  git commit -m "i18n: add POT translation template"
  git push
  ```

---

## Task 10: Full syntax check + verify all files present

- [ ] **Step 1: Syntax-check all PHP files in one pass**

  ```bash
  find /home/pixel/projects/personal/wp/plugins/pawa-downloads -name "*.php" \
    -exec php -l {} \; 2>&1
  ```

  Expected: every file prints `No syntax errors detected`. Any `Parse error` line must be fixed before proceeding.

- [ ] **Step 2: Confirm file tree matches spec**

  ```bash
  find /home/pixel/projects/personal/wp/plugins/pawa-downloads -not -path '*/.git/*' | sort
  ```

  Expected output:
  ```
  .../pawa-downloads
  .../pawa-downloads/.gitignore
  .../pawa-downloads/LICENSE
  .../pawa-downloads/includes
  .../pawa-downloads/includes/cpt.php
  .../pawa-downloads/includes/meta-box.php
  .../pawa-downloads/includes/shortcode.php
  .../pawa-downloads/includes/styles.php
  .../pawa-downloads/languages
  .../pawa-downloads/languages/pawa-downloads.pot
  .../pawa-downloads/pawa-downloads.php
  .../pawa-downloads/readme.txt
  .../pawa-downloads/uninstall.php
  ```

  If any file is missing, create it before proceeding to Task 11.

---

## Task 11: Upload to mwangaza and verify live

> Uses the pawa-wp-mcp FTP tools. The mwangaza FTP root is `/` with WordPress at `/public_html/`.

- [ ] **Step 1: Create the plugin folder on the server**

  Use `mcp__pawa-wp-mcp__ftp_upload` to upload each file. Start by uploading the main plugin file:

  Upload `pawa-downloads.php` → `/public_html/wp-content/plugins/pawa-downloads/pawa-downloads.php`

- [ ] **Step 2: Upload all remaining files**

  Upload in this order (create parent directories implicitly via path):

  | Local path | Remote path |
  |---|---|
  | `includes/cpt.php` | `/public_html/wp-content/plugins/pawa-downloads/includes/cpt.php` |
  | `includes/meta-box.php` | `/public_html/wp-content/plugins/pawa-downloads/includes/meta-box.php` |
  | `includes/shortcode.php` | `/public_html/wp-content/plugins/pawa-downloads/includes/shortcode.php` |
  | `includes/styles.php` | `/public_html/wp-content/plugins/pawa-downloads/includes/styles.php` |
  | `uninstall.php` | `/public_html/wp-content/plugins/pawa-downloads/uninstall.php` |
  | `readme.txt` | `/public_html/wp-content/plugins/pawa-downloads/readme.txt` |
  | `LICENSE` | `/public_html/wp-content/plugins/pawa-downloads/LICENSE` |
  | `languages/pawa-downloads.pot` | `/public_html/wp-content/plugins/pawa-downloads/languages/pawa-downloads.pot` |

- [ ] **Step 3: Activate the plugin via WP REST API**

  ```
  wp_toggle_plugin  site=mwangaza  plugin=pawa-downloads/pawa-downloads.php  status=active
  ```

  Expected: `Plugin "Pawa Downloads" (pawa-downloads/pawa-downloads.php) is now active.`

- [ ] **Step 4: Verify CPT appears in admin**

  Use `wp_inspect` or `wp_get_site_info` to confirm the site is responding. Then log into the mwangaza WP admin and confirm:
  - **Downloads** menu item appears in the sidebar with the download icon
  - **Downloads > Add New** opens the classic editor with a **Download File** meta box
  - Saving a new download entry with a file URL persists correctly (edit the post and confirm the URL is still there)

- [ ] **Step 5: Verify shortcode renders on a page**

  - Add `[pawa_downloads]` to a test page (or the existing Downloads page on mwangaza)
  - Visit the page on the front end
  - Confirm: card grid renders, styles load, download buttons are green `#2a7c5f`
  - Test `[pawa_downloads limit="1"]` — confirm only 1 card shows
  - Test on an Elementor page — confirm styles load (no unstyled fallback)

- [ ] **Step 6: Note any issues**

  If the plugin activates with errors or the CPT / shortcode misbehave, fix the relevant file locally, re-run `php -l`, re-upload the changed file, and retest before continuing.

---

## Task 12: Final commit, tag, and link to parent wp repo

- [ ] **Step 1: Ensure all local changes are committed**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git status
  ```

  Expected: `nothing to commit, working tree clean`. If there are uncommitted changes from Task 11 fixes, commit them:

  ```bash
  git add -A
  git commit -m "fix: address issues found during live verification on mwangaza"
  git push
  ```

- [ ] **Step 2: Tag v1.0.0**

  ```bash
  cd /home/pixel/projects/personal/wp/plugins/pawa-downloads
  git tag -a v1.0.0 -m "v1.0.0 — initial release"
  git push origin v1.0.0
  ```

  Expected: tag visible at `https://github.com/mwangepatrick/pawa-downloads/releases/tag/v1.0.0`.

- [ ] **Step 3: Add plugin as a git submodule in the parent wp repo**

  ```bash
  cd /home/pixel/projects/personal/wp
  git rm plugins/.gitkeep
  git submodule add https://github.com/mwangepatrick/pawa-downloads.git plugins/pawa-downloads
  git commit -m "chore: add pawa-downloads as git submodule

  Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
  ```

- [ ] **Step 4: Verify submodule is wired correctly**

  ```bash
  git -C /home/pixel/projects/personal/wp submodule status
  ```

  Expected: a line like `abc1234 plugins/pawa-downloads (v1.0.0)` — no leading `-` (which would mean uninitialised).

---

## Success Checklist (from spec)

Before declaring done, confirm each item:

- [ ] Plugin activates on WordPress 5.8+ without errors
- [ ] Activation fails gracefully with admin notice on PHP < 7.4
- [ ] `pawa_download` CPT appears in the admin sidebar
- [ ] Admin can create a download, attach a file via media picker, and publish
- [ ] `[pawa_downloads]` renders the card grid on the front end, including on Elementor pages
- [ ] `[pawa_downloads limit="6" order="ASC"]` attributes work correctly
- [ ] Plugin deletes cleanly — no orphaned posts — via the Delete button in Plugins screen
- [ ] No PHP warnings/notices with `WP_DEBUG = true`
- [ ] All user-facing strings are wrapped for translation
- [ ] `readme.txt` passes the [WP.org Plugin Check](https://wordpress.org/plugins/plugin-check/)
- [ ] Styles dequeue cleanly via `wp_dequeue_style( 'pawa-downloads' )`
- [ ] Repo is public at `https://github.com/mwangepatrick/pawa-downloads`
- [ ] `v1.0.0` tag exists on GitHub
