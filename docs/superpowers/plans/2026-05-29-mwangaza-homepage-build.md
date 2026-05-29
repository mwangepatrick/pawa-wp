# Mwangaza Homepage — Story-First Build Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the broken mwangaza homepage with an 8-section Story-First design in Elementor, targeting donor conversion with a hope-and-community tone.

**Architecture:** Build the complete new homepage as a single Elementor JSON payload and deploy it via a PHP helper file (ftp_upload → curl → ftp_delete pattern). Each section is a top-level Elementor container. After deployment, trigger CSS regeneration via a no-op `wp_update_elementor_data` call.

**Tech Stack:** WordPress 6.x, Elementor Pro (Helpy theme), pawa-wp-mcp MCP server, PHP 8.x

---

## Pre-flight: Read rules and skill

- [ ] Read `/home/pixel/projects/personal/wp/pawa-wp-mcp/.claude/agents/zane.md` — iron rules apply throughout
- [ ] Invoke `wp-manager` skill before any tool call

---

## Task 1: Baseline Snapshot

**Purpose:** Capture current state, backup Elementor data, find assets.

**Files:**
- Create: `/tmp/mwangaza-hp-backup.json` — current `_elementor_data` for homepage

- [ ] **Step 1: Get homepage post and backup Elementor data**

```
wp_get_post(site="mwangaza", id=1716, type="page")
```

Save the full response to `/tmp/mwangaza-hp-backup.json`. If the page ID is wrong (verify title is "Home 5"), run `wp_get_posts(site="mwangaza", type="page", limit=20)` to find the correct homepage ID and update all subsequent tasks.

- [ ] **Step 2: Visual baseline screenshot**

```
wp_inspect(url="https://mwangazaintergrated.org/")
```

Save screenshot mentally as "before" state.

- [ ] **Step 3: Find hero image**

```
wp_get_media(site="mwangaza", limit=30)
```

Find a field photo of real community members in action — `afrinov-training.jpg` (WP ID ~6019) or the community gathering shots. Record the image URL and ID as `HERO_IMAGE_URL` and `HERO_IMAGE_ID` — you will substitute these into the JSON in Task 3.

- [ ] **Step 4: Find partner logos**

From the media list, identify:
- AfriNov logo URL → `AFRINOV_LOGO_URL` and `AFRINOV_LOGO_ID`
- Uraia logo URL → `URAIA_LOGO_URL` and `URAIA_LOGO_ID`

---

## Task 2: Detect Server Filesystem Path

**Purpose:** The PHP helper needs the absolute filesystem path to `wp-load.php`. Detect it now before writing the deployment file.

**⚠️ Iron Rule 4 applies:** Tell the user before uploading any PHP file. State: "About to upload a path-detection PHP file to the server root as `detect-path.php`. It will return the WordPress root path and be deleted immediately. Confirm?"

- [ ] **Step 1: Write detection file**

Create `/tmp/detect-path.php`:
```php
<?php
$candidates = [
    dirname(__DIR__) . '/public_html/wp-load.php',
    __DIR__ . '/wp-load.php',
    '/home/' . get_current_user() . '/public_html/wp-load.php',
];
foreach ($candidates as $p) {
    if (file_exists($p)) {
        echo json_encode(['wp_load' => $p, 'dir' => __DIR__]);
        exit;
    }
}
echo json_encode(['wp_load' => null, 'dir' => __DIR__, 'candidates' => $candidates]);
```

- [ ] **Step 2: Confirm with user, then upload**

After confirmation:
```
ftp_upload(site="mwangaza", local_path="/tmp/detect-path.php", remote_path="detect-path.php")
```

- [ ] **Step 3: Execute and record path**

```bash
curl -s "https://mwangazaintergrated.org/detect-path.php"
```

Record the returned `wp_load` value as `WP_LOAD_PATH`. Example: `/home1/mwangaz/public_html/wp-load.php`

- [ ] **Step 4: Delete immediately**

```
ftp_delete(site="mwangaza", path="detect-path.php")
```

---

## Task 3: Build Complete Homepage JSON

**Purpose:** Construct the full 8-section Elementor JSON. Save as a local file — deployment happens in Task 4.

**Files:**
- Create: `/tmp/mwangaza-homepage.json`

- [ ] **Step 1: Write the JSON**

Write the following to `/tmp/mwangaza-homepage.json`. Replace `HERO_IMAGE_URL`, `HERO_IMAGE_ID`, `AFRINOV_LOGO_URL`, `AFRINOV_LOGO_ID`, `URAIA_LOGO_URL`, `URAIA_LOGO_ID` with the values recorded in Task 1.

```json
[
  {
    "id": "s01out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_image": {
        "url": "HERO_IMAGE_URL",
        "id": "HERO_IMAGE_ID"
      },
      "background_position": "center center",
      "background_repeat": "no-repeat",
      "background_size": "cover",
      "background_overlay": "yes",
      "background_overlay_color": "rgba(0,0,0,0.45)",
      "height": "min-height",
      "min_height": {"unit": "vh", "size": 85, "sizes": []},
      "content_position": "middle",
      "flex_direction": "column",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "0", "right": "0", "bottom": "0", "left": "0", "isLinked": true}
    },
    "elements": [
      {
        "id": "s01inn1",
        "elType": "container",
        "settings": {
          "flex_direction": "column",
          "align_items": "flex-start",
          "padding": {"unit": "px", "top": "80", "right": "20", "bottom": "80", "left": "20", "isLinked": false},
          "width": {"unit": "%", "size": 60, "sizes": []}
        },
        "elements": [
          {
            "id": "s01hed1",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "Together We Rise, Together We Stand",
              "header_size": "h1",
              "title_color": "#FFFFFF",
              "typography_typography": "custom",
              "typography_font_family": "Quicksand",
              "typography_font_weight": "700",
              "typography_font_size": {"unit": "px", "size": 56, "sizes": []},
              "typography_line_height": {"unit": "em", "size": 1.15, "sizes": []},
              "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "20", "left": "0", "isLinked": false}
            }
          },
          {
            "id": "s01txt1",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "<p>Mwangaza Integrated Project fights for the rights of communities along the Sondu Miriu, Kabondo East — through evidence, advocacy, and unity.</p>",
              "text_color": "#FFFFFF",
              "typography_typography": "custom",
              "typography_font_family": "Nunito Sans",
              "typography_font_size": {"unit": "px", "size": 18, "sizes": []},
              "typography_line_height": {"unit": "em", "size": 1.6, "sizes": []},
              "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "32", "left": "0", "isLinked": false}
            }
          },
          {
            "id": "s01btn0",
            "elType": "container",
            "settings": {
              "flex_direction": "row",
              "flex_wrap": "wrap",
              "gap": {"unit": "px", "size": 16}
            },
            "elements": [
              {
                "id": "s01btn1",
                "elType": "widget",
                "widgetType": "button",
                "settings": {
                  "text": "Get Involved",
                  "link": {"url": "/volunteers/", "is_external": "", "nofollow": ""},
                  "button_type": "",
                  "button_background_color": "#F5A320",
                  "button_text_color": "#1A1A1A",
                  "border_radius": {"unit": "px", "top": 6, "right": 6, "bottom": 6, "left": 6, "isLinked": true},
                  "typography_font_family": "Nunito Sans",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 16}
                }
              },
              {
                "id": "s01btn2",
                "elType": "widget",
                "widgetType": "button",
                "settings": {
                  "text": "Our Story",
                  "link": {"url": "/about/", "is_external": "", "nofollow": ""},
                  "button_type": "ghost",
                  "button_background_color": "rgba(0,0,0,0)",
                  "button_text_color": "#FFFFFF",
                  "border_border": "solid",
                  "border_width": {"unit": "px", "top": 2, "right": 2, "bottom": 2, "left": 2, "isLinked": true},
                  "border_color": "#FFFFFF",
                  "border_radius": {"unit": "px", "top": 6, "right": 6, "bottom": 6, "left": 6, "isLinked": true},
                  "typography_font_family": "Nunito Sans",
                  "typography_font_weight": "600",
                  "typography_font_size": {"unit": "px", "size": 16}
                }
              }
            ]
          }
        ]
      }
    ]
  },
  {
    "id": "s02out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#FEF3E2",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "80", "right": "0", "bottom": "80", "left": "0", "isLinked": false}
    },
    "elements": [
      {
        "id": "s02row1",
        "elType": "container",
        "settings": {
          "flex_direction": "row",
          "flex_wrap": "wrap",
          "gap": {"unit": "px", "size": 40}
        },
        "elements": [
          {
            "id": "s02lft1",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 55}
            },
            "elements": [
              {
                "id": "s02txt1",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>We develop efficient, healthy communities through food security, capacity building, and rights advocacy in Kabondo East, Kenya.</p>",
                  "text_color": "#1A1A1A",
                  "typography_typography": "custom",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "600",
                  "typography_font_size": {"unit": "px", "size": 20}
                }
              }
            ]
          },
          {
            "id": "s02rgt1",
            "elType": "container",
            "settings": {
              "flex_direction": "row",
              "flex_wrap": "wrap",
              "gap": {"unit": "px", "size": 24}
            },
            "elements": [
              {
                "id": "s02plr1",
                "elType": "widget",
                "widgetType": "icon-box",
                "settings": {
                  "title_text": "Evidence-Based",
                  "description_text": "AfriNov methodology",
                  "icon": {"library": "fa-solid", "value": "fas fa-seedling"},
                  "icon_color": "#1A1A1A",
                  "icon_background_color": "#72D64A",
                  "icon_shape": "circle",
                  "icon_size": {"unit": "px", "size": 20}
                }
              },
              {
                "id": "s02plr2",
                "elType": "widget",
                "widgetType": "icon-box",
                "settings": {
                  "title_text": "Nonviolent Advocacy",
                  "description_text": "Community-led",
                  "icon": {"library": "fa-solid", "value": "fas fa-fist-raised"},
                  "icon_color": "#1A1A1A",
                  "icon_background_color": "#72D64A",
                  "icon_shape": "circle",
                  "icon_size": {"unit": "px", "size": 20}
                }
              },
              {
                "id": "s02plr3",
                "elType": "widget",
                "widgetType": "icon-box",
                "settings": {
                  "title_text": "Partnership-Driven",
                  "description_text": "Local to national",
                  "icon": {"library": "fa-solid", "value": "fas fa-handshake"},
                  "icon_color": "#1A1A1A",
                  "icon_background_color": "#72D64A",
                  "icon_shape": "circle",
                  "icon_size": {"unit": "px", "size": 20}
                }
              }
            ]
          }
        ]
      }
    ]
  },
  {
    "id": "s03out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#FFFFFF",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "80", "right": "0", "bottom": "80", "left": "0", "isLinked": false}
    },
    "elements": [
      {
        "id": "s03hed1",
        "elType": "widget",
        "widgetType": "heading",
        "settings": {
          "title": "Six Challenges. One Community. One Voice.",
          "header_size": "h2",
          "align": "center",
          "title_color": "#E8342A",
          "typography_font_family": "Quicksand",
          "typography_font_weight": "700",
          "typography_font_size": {"unit": "px", "size": 36}
        }
      },
      {
        "id": "s03sub1",
        "elType": "widget",
        "widgetType": "text-editor",
        "settings": {
          "editor": "<p>AfriNov's situation analysis of Kabondo East identified six critical, interconnected issues Mwangaza is actively working to resolve.</p>",
          "align": "center",
          "text_color": "#514F4C",
          "typography_font_family": "Nunito Sans",
          "typography_font_size": {"unit": "px", "size": 18},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "48", "left": "0", "isLinked": false}
        }
      },
      {
        "id": "s03grd1",
        "elType": "container",
        "settings": {
          "flex_direction": "row",
          "flex_wrap": "wrap",
          "gap": {"unit": "px", "size": 24}
        },
        "elements": [
          {
            "id": "s03cd1",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 30},
              "border_left_width": {"unit": "px", "size": 4},
              "border_left_color": "#F5A320",
              "border_border": "solid",
              "background_background": "classic",
              "background_color": "#FAFAFA",
              "padding": {"unit": "px", "top": "24", "right": "24", "bottom": "24", "left": "20", "isLinked": false},
              "border_radius": {"unit": "px", "top": 4, "right": 4, "bottom": 4, "left": 0, "isLinked": false}
            },
            "elements": [
              {
                "id": "s03ic1",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-bolt"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#72D64A",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03t1",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "No Electricity",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03d1",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Families fish and farm in the dark — no cold storage, no studying after sunset.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s03cd2",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 30},
              "border_left_width": {"unit": "px", "size": 4},
              "border_left_color": "#F5A320",
              "border_border": "solid",
              "background_background": "classic",
              "background_color": "#FAFAFA",
              "padding": {"unit": "px", "top": "24", "right": "24", "bottom": "24", "left": "20", "isLinked": false},
              "border_radius": {"unit": "px", "top": 4, "right": 4, "bottom": 4, "left": 0, "isLinked": false}
            },
            "elements": [
              {
                "id": "s03ic2",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-tint"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#72D64A",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03t2",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Water Scarcity",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03d2",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Limited access to clean, safe water for drinking and irrigation.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s03cd3",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 30},
              "border_left_width": {"unit": "px", "size": 4},
              "border_left_color": "#F5A320",
              "border_border": "solid",
              "background_background": "classic",
              "background_color": "#FAFAFA",
              "padding": {"unit": "px", "top": "24", "right": "24", "bottom": "24", "left": "20", "isLinked": false},
              "border_radius": {"unit": "px", "top": 4, "right": 4, "bottom": 4, "left": 0, "isLinked": false}
            },
            "elements": [
              {
                "id": "s03ic3",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-road"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#72D64A",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03t3",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Poor Roads",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03d3",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Communities remain cut off from markets, hospitals, and services.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s03cd4",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 30},
              "border_left_width": {"unit": "px", "size": 4},
              "border_left_color": "#F5A320",
              "border_border": "solid",
              "background_background": "classic",
              "background_color": "#FAFAFA",
              "padding": {"unit": "px", "top": "24", "right": "24", "bottom": "24", "left": "20", "isLinked": false},
              "border_radius": {"unit": "px", "top": 4, "right": 4, "bottom": 4, "left": 0, "isLinked": false}
            },
            "elements": [
              {
                "id": "s03ic4",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-fish"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#72D64A",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03t4",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Hippo Grass",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03d4",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>River grass blocks fishing access and destroys livelihoods along the Sondu Miriu.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s03cd5",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 30},
              "border_left_width": {"unit": "px", "size": 4},
              "border_left_color": "#F5A320",
              "border_border": "solid",
              "background_background": "classic",
              "background_color": "#FAFAFA",
              "padding": {"unit": "px", "top": "24", "right": "24", "bottom": "24", "left": "20", "isLinked": false},
              "border_radius": {"unit": "px", "top": 4, "right": 4, "bottom": 4, "left": 0, "isLinked": false}
            },
            "elements": [
              {
                "id": "s03ic5",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-heartbeat"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#72D64A",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03t5",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Healthcare Gap",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03d5",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>The nearest dispensary is too far for most families.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s03cd6",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 30},
              "border_left_width": {"unit": "px", "size": 4},
              "border_left_color": "#F5A320",
              "border_border": "solid",
              "background_background": "classic",
              "background_color": "#FAFAFA",
              "padding": {"unit": "px", "top": "24", "right": "24", "bottom": "24", "left": "20", "isLinked": false},
              "border_radius": {"unit": "px", "top": 4, "right": 4, "bottom": 4, "left": 0, "isLinked": false}
            },
            "elements": [
              {
                "id": "s03ic6",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-landmark"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#72D64A",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03t6",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Land Rights",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 18}
                }
              },
              {
                "id": "s03d6",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Community members lack title deeds to land they have farmed for generations.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          }
        ]
      }
    ]
  },
  {
    "id": "s04out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#8544AD",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "64", "right": "0", "bottom": "64", "left": "0", "isLinked": false}
    },
    "elements": [
      {
        "id": "s04row1",
        "elType": "container",
        "settings": {
          "flex_direction": "row",
          "flex_wrap": "wrap",
          "align_items": "center",
          "justify_content": "center",
          "gap": {"unit": "px", "size": 0}
        },
        "elements": [
          {
            "id": "s04st1",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 25},
              "padding": {"unit": "px", "top": "16", "right": "16", "bottom": "16", "left": "16", "isLinked": true}
            },
            "elements": [
              {
                "id": "s04n1",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "25+",
                  "header_size": "h2",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 56}
                }
              },
              {
                "id": "s04l1",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>years of community advocacy</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.8)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s04st2",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 25},
              "padding": {"unit": "px", "top": "16", "right": "16", "bottom": "16", "left": "16", "isLinked": true}
            },
            "elements": [
              {
                "id": "s04n2",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "5+",
                  "header_size": "h2",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 56}
                }
              },
              {
                "id": "s04l2",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>years as a registered CBO</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.8)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s04st3",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 25},
              "padding": {"unit": "px", "top": "16", "right": "16", "bottom": "16", "left": "16", "isLinked": true}
            },
            "elements": [
              {
                "id": "s04n3",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "1",
                  "header_size": "h2",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 56}
                }
              },
              {
                "id": "s04l3",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>petition defended before Parliament</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.8)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s04st4",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 25},
              "padding": {"unit": "px", "top": "16", "right": "16", "bottom": "16", "left": "16", "isLinked": true}
            },
            "elements": [
              {
                "id": "s04n4",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "6",
                  "header_size": "h2",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 56}
                }
              },
              {
                "id": "s04l4",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>challenges actively being addressed</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.8)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          }
        ]
      }
    ]
  },
  {
    "id": "s05out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#FEF3E2",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "80", "right": "0", "bottom": "80", "left": "0", "isLinked": false}
    },
    "elements": [
      {
        "id": "s05hed1",
        "elType": "widget",
        "widgetType": "heading",
        "settings": {
          "title": "What We Do",
          "header_size": "h2",
          "align": "center",
          "title_color": "#E8342A",
          "typography_font_family": "Quicksand",
          "typography_font_weight": "700",
          "typography_font_size": {"unit": "px", "size": 36},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "12", "left": "0", "isLinked": false}
        }
      },
      {
        "id": "s05sub1",
        "elType": "widget",
        "widgetType": "text-editor",
        "settings": {
          "editor": "<p>Eight active programmes, one mission — building Kabondo East from the ground up.</p>",
          "align": "center",
          "text_color": "#514F4C",
          "typography_font_family": "Nunito Sans",
          "typography_font_size": {"unit": "px", "size": 18},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "48", "left": "0", "isLinked": false}
        }
      },
      {
        "id": "s05grd1",
        "elType": "container",
        "settings": {
          "flex_direction": "row",
          "flex_wrap": "wrap",
          "gap": {"unit": "px", "size": 24},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "40", "left": "0", "isLinked": false}
        },
        "elements": [
          {
            "id": "s05cd1",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 22},
              "background_background": "classic",
              "background_color": "#FFFFFF",
              "padding": {"unit": "px", "top": "28", "right": "24", "bottom": "28", "left": "24", "isLinked": false},
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "box_shadow_box_shadow_type": "yes"
            },
            "elements": [
              {
                "id": "s05ic1",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-seedling"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#F5A320",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 20},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "16", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s05t1",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Sustainable Agriculture",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 17}
                }
              },
              {
                "id": "s05d1",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Improving food security and livelihoods through better farming practices.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 14}
                }
              }
            ]
          },
          {
            "id": "s05cd2",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 22},
              "background_background": "classic",
              "background_color": "#FFFFFF",
              "padding": {"unit": "px", "top": "28", "right": "24", "bottom": "28", "left": "24", "isLinked": false},
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "box_shadow_box_shadow_type": "yes"
            },
            "elements": [
              {
                "id": "s05ic2",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-scale-balanced"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#F5A320",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 20},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "16", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s05t2",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Human Rights Advocacy",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 17}
                }
              },
              {
                "id": "s05d2",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Training communities to lobby and advocate for their rights at every level.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 14}
                }
              }
            ]
          },
          {
            "id": "s05cd3",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 22},
              "background_background": "classic",
              "background_color": "#FFFFFF",
              "padding": {"unit": "px", "top": "28", "right": "24", "bottom": "28", "left": "24", "isLinked": false},
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "box_shadow_box_shadow_type": "yes"
            },
            "elements": [
              {
                "id": "s05ic3",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-heartbeat"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#F5A320",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 20},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "16", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s05t3",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Health Education",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 17}
                }
              },
              {
                "id": "s05d3",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Awareness campaigns on health, sanitation, and social issues.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 14}
                }
              }
            ]
          },
          {
            "id": "s05cd4",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "width": {"unit": "%", "size": 22},
              "background_background": "classic",
              "background_color": "#FFFFFF",
              "padding": {"unit": "px", "top": "28", "right": "24", "bottom": "28", "left": "24", "isLinked": false},
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "box_shadow_box_shadow_type": "yes"
            },
            "elements": [
              {
                "id": "s05ic4",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-venus"},
                  "primary_color": "#1A1A1A",
                  "secondary_color": "#F5A320",
                  "view": "framed",
                  "shape": "circle",
                  "size": {"unit": "px", "size": 20},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "16", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s05t4",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Women Empowerment",
                  "header_size": "h4",
                  "title_color": "#1A1A1A",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 17}
                }
              },
              {
                "id": "s05d4",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Mainstreaming gender equality and economic opportunity for women.</p>",
                  "text_color": "#514F4C",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 14}
                }
              }
            ]
          }
        ]
      },
      {
        "id": "s05cta1",
        "elType": "widget",
        "widgetType": "button",
        "settings": {
          "text": "See All Programmes →",
          "link": {"url": "/service/", "is_external": "", "nofollow": ""},
          "align": "center",
          "button_type": "ghost",
          "button_background_color": "rgba(0,0,0,0)",
          "button_text_color": "#E8342A",
          "border_border": "solid",
          "border_width": {"unit": "px", "top": 2, "right": 2, "bottom": 2, "left": 2, "isLinked": true},
          "border_color": "#E8342A",
          "border_radius": {"unit": "px", "top": 6, "right": 6, "bottom": 6, "left": 6, "isLinked": true},
          "typography_font_family": "Nunito Sans",
          "typography_font_weight": "600",
          "typography_font_size": {"unit": "px", "size": 16}
        }
      }
    ]
  },
  {
    "id": "s06out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#FFFFFF",
      "content_width": "full",
      "flex_direction": "row",
      "flex_wrap": "wrap",
      "padding": {"unit": "px", "top": "0", "right": "0", "bottom": "0", "left": "0", "isLinked": true}
    },
    "elements": [
      {
        "id": "s06img1",
        "elType": "container",
        "settings": {
          "flex_direction": "column",
          "width": {"unit": "%", "size": 50},
          "background_background": "classic",
          "background_image": {
            "url": "HERO_IMAGE_URL",
            "id": "HERO_IMAGE_ID"
          },
          "background_position": "center center",
          "background_repeat": "no-repeat",
          "background_size": "cover",
          "min_height": {"unit": "px", "size": 480, "sizes": []}
        },
        "elements": []
      },
      {
        "id": "s06txt1",
        "elType": "container",
        "settings": {
          "flex_direction": "column",
          "justify_content": "center",
          "width": {"unit": "%", "size": 50},
          "padding": {"unit": "px", "top": "64", "right": "64", "bottom": "64", "left": "64", "isLinked": true},
          "border_left_width": {"unit": "px", "size": 4},
          "border_left_color": "#F5A320",
          "border_border": "solid"
        },
        "elements": [
          {
            "id": "s06lbl1",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "COMMUNITY VOICE",
              "header_size": "h6",
              "title_color": "#F5A320",
              "typography_font_family": "Nunito Sans",
              "typography_font_weight": "700",
              "typography_letter_spacing": {"unit": "px", "size": 2},
              "typography_font_size": {"unit": "px", "size": 13},
              "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "20", "left": "0", "isLinked": false}
            }
          },
          {
            "id": "s06qte1",
            "elType": "widget",
            "widgetType": "heading",
            "settings": {
              "title": "We fought for 20 years along this river. Now we have a voice in Parliament.",
              "header_size": "h3",
              "title_color": "#1A1A1A",
              "typography_font_family": "Quicksand",
              "typography_font_weight": "700",
              "typography_font_size": {"unit": "px", "size": 26},
              "typography_line_height": {"unit": "em", "size": 1.4},
              "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "16", "left": "0", "isLinked": false}
            }
          },
          {
            "id": "s06atr1",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "<p><em>— [Name], CBO member, Kabondo East</em></p>",
              "text_color": "#514F4C",
              "typography_font_family": "Nunito Sans",
              "typography_font_size": {"unit": "px", "size": 15},
              "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "20", "left": "0", "isLinked": false}
            }
          },
          {
            "id": "s06ctx1",
            "elType": "widget",
            "widgetType": "text-editor",
            "settings": {
              "editor": "<p>For over two decades, communities along the Sondu Miriu dam faced displacement and silence. Mwangaza changed that — equipping members with the tools to turn protest into principled advocacy, and local grievances into parliamentary action.</p>",
              "text_color": "#514F4C",
              "typography_font_family": "Nunito Sans",
              "typography_font_size": {"unit": "px", "size": 16},
              "typography_line_height": {"unit": "em", "size": 1.7},
              "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "28", "left": "0", "isLinked": false}
            }
          },
          {
            "id": "s06lnk1",
            "elType": "widget",
            "widgetType": "button",
            "settings": {
              "text": "Read Our Full Story →",
              "link": {"url": "/about/", "is_external": "", "nofollow": ""},
              "button_type": "ghost",
              "button_background_color": "rgba(0,0,0,0)",
              "button_text_color": "#E8342A",
              "border_border": "none",
              "typography_font_family": "Nunito Sans",
              "typography_font_weight": "600",
              "typography_font_size": {"unit": "px", "size": 16},
              "text_padding": {"unit": "px", "top": "0", "right": "0", "bottom": "0", "left": "0", "isLinked": true}
            }
          }
        ]
      }
    ]
  },
  {
    "id": "s07out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#FFFFFF",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "64", "right": "0", "bottom": "64", "left": "0", "isLinked": false}
    },
    "elements": [
      {
        "id": "s07lbl1",
        "elType": "widget",
        "widgetType": "heading",
        "settings": {
          "title": "WORKING ALONGSIDE",
          "header_size": "h6",
          "align": "center",
          "title_color": "#514F4C",
          "typography_font_family": "Nunito Sans",
          "typography_font_weight": "700",
          "typography_letter_spacing": {"unit": "px", "size": 2},
          "typography_font_size": {"unit": "px", "size": 13},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "32", "left": "0", "isLinked": false}
        }
      },
      {
        "id": "s07lgr1",
        "elType": "container",
        "settings": {
          "flex_direction": "row",
          "align_items": "center",
          "justify_content": "center",
          "flex_wrap": "wrap",
          "gap": {"unit": "px", "size": 48},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "24", "left": "0", "isLinked": false}
        },
        "elements": [
          {
            "id": "s07lg1",
            "elType": "widget",
            "widgetType": "image",
            "settings": {
              "image": {"url": "AFRINOV_LOGO_URL", "id": "AFRINOV_LOGO_ID"},
              "image_size": "medium",
              "width": {"unit": "px", "size": 120},
              "css_filters_css_filter": "grayscale",
              "css_filters_brightness": {"size": 0},
              "css_filters_css_filter_hover": "",
              "opacity": {"size": 0.6}
            }
          },
          {
            "id": "s07lg2",
            "elType": "widget",
            "widgetType": "image",
            "settings": {
              "image": {"url": "URAIA_LOGO_URL", "id": "URAIA_LOGO_ID"},
              "image_size": "medium",
              "width": {"unit": "px", "size": 120},
              "css_filters_css_filter": "grayscale",
              "css_filters_brightness": {"size": 0},
              "opacity": {"size": 0.6}
            }
          }
        ]
      },
      {
        "id": "s07sub1",
        "elType": "widget",
        "widgetType": "text-editor",
        "settings": {
          "editor": "<p>Mwangaza works with local, national, and international partners to amplify community voices.</p>",
          "align": "center",
          "text_color": "#514F4C",
          "typography_font_family": "Nunito Sans",
          "typography_font_size": {"unit": "px", "size": 15}
        }
      }
    ]
  },
  {
    "id": "s08out1",
    "elType": "container",
    "settings": {
      "background_background": "classic",
      "background_color": "#1A1A1A",
      "content_width": "boxed",
      "padding": {"unit": "px", "top": "80", "right": "0", "bottom": "80", "left": "0", "isLinked": false}
    },
    "elements": [
      {
        "id": "s08hed1",
        "elType": "widget",
        "widgetType": "heading",
        "settings": {
          "title": "Your Help Can Change Lives",
          "header_size": "h2",
          "align": "center",
          "title_color": "#FFFFFF",
          "typography_font_family": "Quicksand",
          "typography_font_weight": "700",
          "typography_font_size": {"unit": "px", "size": 36},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "12", "left": "0", "isLinked": false}
        }
      },
      {
        "id": "s08sub1",
        "elType": "widget",
        "widgetType": "text-editor",
        "settings": {
          "editor": "<p>There are three ways to stand with Mwangaza.</p>",
          "align": "center",
          "text_color": "rgba(255,255,255,0.7)",
          "typography_font_family": "Nunito Sans",
          "typography_font_size": {"unit": "px", "size": 18},
          "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "48", "left": "0", "isLinked": false}
        }
      },
      {
        "id": "s08row1",
        "elType": "container",
        "settings": {
          "flex_direction": "row",
          "flex_wrap": "wrap",
          "gap": {"unit": "px", "size": 24},
          "align_items": "stretch"
        },
        "elements": [
          {
            "id": "s08cd1",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 30},
              "background_background": "classic",
              "background_color": "rgba(255,255,255,0.06)",
              "border_border": "solid",
              "border_width": {"unit": "px", "top": 1, "right": 1, "bottom": 1, "left": 1, "isLinked": true},
              "border_color": "rgba(255,255,255,0.15)",
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "padding": {"unit": "px", "top": "40", "right": "32", "bottom": "40", "left": "32", "isLinked": false}
            },
            "elements": [
              {
                "id": "s08ic1",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-hands-helping"},
                  "primary_color": "#F5A320",
                  "view": "default",
                  "size": {"unit": "px", "size": 36},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "20", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08t1",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Volunteer",
                  "header_size": "h3",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 22},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "12", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08d1",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Bring your skills to Kabondo East or support us remotely.</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.7)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "28", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08b1",
                "elType": "widget",
                "widgetType": "button",
                "settings": {
                  "text": "Get Involved",
                  "link": {"url": "/volunteers/", "is_external": "", "nofollow": ""},
                  "align": "center",
                  "button_background_color": "#F5A320",
                  "button_text_color": "#1A1A1A",
                  "border_radius": {"unit": "px", "top": 6, "right": 6, "bottom": 6, "left": 6, "isLinked": true},
                  "typography_font_family": "Nunito Sans",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s08cd2",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 30},
              "background_background": "classic",
              "background_color": "rgba(255,255,255,0.06)",
              "border_border": "solid",
              "border_width": {"unit": "px", "top": 1, "right": 1, "bottom": 1, "left": 1, "isLinked": true},
              "border_color": "rgba(255,255,255,0.15)",
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "padding": {"unit": "px", "top": "40", "right": "32", "bottom": "40", "left": "32", "isLinked": false}
            },
            "elements": [
              {
                "id": "s08ic2",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-building"},
                  "primary_color": "#F5A320",
                  "view": "default",
                  "size": {"unit": "px", "size": 36},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "20", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08t2",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Partner",
                  "header_size": "h3",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 22},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "12", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08d2",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Organisations and institutions — let's amplify impact together.</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.7)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "28", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08b2",
                "elType": "widget",
                "widgetType": "button",
                "settings": {
                  "text": "Work With Us",
                  "link": {"url": "/contact/", "is_external": "", "nofollow": ""},
                  "align": "center",
                  "button_background_color": "#F5A320",
                  "button_text_color": "#1A1A1A",
                  "border_radius": {"unit": "px", "top": 6, "right": 6, "bottom": 6, "left": 6, "isLinked": true},
                  "typography_font_family": "Nunito Sans",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          },
          {
            "id": "s08cd3",
            "elType": "container",
            "settings": {
              "flex_direction": "column",
              "align_items": "center",
              "width": {"unit": "%", "size": 30},
              "background_background": "classic",
              "background_color": "rgba(255,255,255,0.06)",
              "border_border": "solid",
              "border_width": {"unit": "px", "top": 1, "right": 1, "bottom": 1, "left": 1, "isLinked": true},
              "border_color": "rgba(255,255,255,0.15)",
              "border_radius": {"unit": "px", "top": 8, "right": 8, "bottom": 8, "left": 8, "isLinked": true},
              "padding": {"unit": "px", "top": "40", "right": "32", "bottom": "40", "left": "32", "isLinked": false}
            },
            "elements": [
              {
                "id": "s08ic3",
                "elType": "widget",
                "widgetType": "icon",
                "settings": {
                  "icon": {"library": "fa-solid", "value": "fas fa-rss"},
                  "primary_color": "#F5A320",
                  "view": "default",
                  "size": {"unit": "px", "size": 36},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "20", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08t3",
                "elType": "widget",
                "widgetType": "heading",
                "settings": {
                  "title": "Stay Updated",
                  "header_size": "h3",
                  "align": "center",
                  "title_color": "#FFFFFF",
                  "typography_font_family": "Quicksand",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 22},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "12", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08d3",
                "elType": "widget",
                "widgetType": "text-editor",
                "settings": {
                  "editor": "<p>Follow our advocacy journey — field updates, events, and wins.</p>",
                  "align": "center",
                  "text_color": "rgba(255,255,255,0.7)",
                  "typography_font_family": "Nunito Sans",
                  "typography_font_size": {"unit": "px", "size": 15},
                  "_margin": {"unit": "px", "top": "0", "right": "0", "bottom": "28", "left": "0", "isLinked": false}
                }
              },
              {
                "id": "s08b3",
                "elType": "widget",
                "widgetType": "button",
                "settings": {
                  "text": "Read Our Blog",
                  "link": {"url": "/blog/", "is_external": "", "nofollow": ""},
                  "align": "center",
                  "button_background_color": "#F5A320",
                  "button_text_color": "#1A1A1A",
                  "border_radius": {"unit": "px", "top": 6, "right": 6, "bottom": 6, "left": 6, "isLinked": true},
                  "typography_font_family": "Nunito Sans",
                  "typography_font_weight": "700",
                  "typography_font_size": {"unit": "px", "size": 15}
                }
              }
            ]
          }
        ]
      }
    ]
  }
]
```

- [ ] **Step 2: Validate JSON**

```bash
python3 -c "import json; json.load(open('/tmp/mwangaza-homepage.json')); print('JSON valid')"
```

Expected output: `JSON valid`

---

## Task 4: Deploy Homepage via PHP Helper

**⚠️ Iron Rule 4:** Before executing this task, tell the user:
> "About to upload `mwangaza-deploy-homepage.php` to the site root. This PHP file will replace the homepage Elementor data and be deleted immediately. It will overwrite the current homepage layout. The old data is backed up in `/tmp/mwangaza-hp-backup.json`. Confirm?"

Wait for confirmation before proceeding.

**Files:**
- Create: `/tmp/mwangaza-deploy-homepage.php`

- [ ] **Step 1: Write the PHP deployment file**

Replace `WP_LOAD_PATH` with the value detected in Task 2, and `PAGE_ID` with the homepage post ID (1716 or as confirmed in Task 1):

```php
<?php
require('WP_LOAD_PATH');

$post_id = PAGE_ID;
$json_path = dirname(__FILE__) . '/mwangaza-homepage-data.json';

if (!file_exists($json_path)) {
    echo json_encode(['error' => 'JSON data file not found: ' . $json_path]);
    exit(1);
}

$json = file_get_contents($json_path);

// Validate JSON before writing
$decoded = json_decode($json);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit(1);
}

$updated = update_post_meta($post_id, '_elementor_data', $json);
update_post_meta($post_id, '_elementor_edit_mode', 'builder');

// Regenerate Elementor CSS
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo json_encode([
    'success' => true,
    'post_id' => $post_id,
    'updated' => $updated,
    'sections' => count($decoded)
]);
```

- [ ] **Step 2: Upload both files to the server root**

```
ftp_upload(site="mwangaza", local_path="/tmp/mwangaza-deploy-homepage.php", remote_path="mwangaza-deploy-homepage.php")
ftp_upload(site="mwangaza", local_path="/tmp/mwangaza-homepage.json", remote_path="mwangaza-homepage-data.json")
```

- [ ] **Step 3: Execute deployment**

```bash
curl -s "https://mwangazaintergrated.org/mwangaza-deploy-homepage.php"
```

Expected output:
```json
{"success":true,"post_id":1716,"updated":true,"sections":8}
```

If `"error"` appears in the response, stop immediately, delete the files, and report the error.

- [ ] **Step 4: Delete both files immediately**

```
ftp_delete(site="mwangaza", path="mwangaza-deploy-homepage.php")
ftp_delete(site="mwangaza", path="mwangaza-homepage-data.json")
```

Verify deletion:
```
ftp_list(site="mwangaza", path=".")
```

Confirm neither `mwangaza-deploy-homepage.php` nor `mwangaza-homepage-data.json` appear in the listing.

- [ ] **Step 5: Commit the JSON as source of truth**

```bash
cp /tmp/mwangaza-homepage.json /home/pixel/projects/personal/wp/docs/superpowers/specs/mwangaza-homepage-elementor-data.json
git add docs/superpowers/specs/mwangaza-homepage-elementor-data.json
git commit -m "feat: add Mwangaza homepage Story-First Elementor JSON"
```

---

## Task 5: CSS Regeneration

**Purpose:** The PHP deployment calls `clear_cache()` but Elementor Pro templates (header/footer) need a separate trigger. Use the safe no-op pattern.

- [ ] **Step 1: Trigger CSS regeneration for homepage**

```
wp_update_elementor_data(
  site="mwangaza",
  post_id=1716,
  post_type="pages",
  replacements=[{"search": "Together We Rise, Together We Stand", "replace": "Together We Rise, Together We Stand"}]
)
```

Wait 3 seconds, then proceed.

- [ ] **Step 2: Commit**

```bash
git commit --allow-empty -m "chore: trigger Elementor CSS regeneration for mwangaza homepage"
```

---

## Task 6: Visual Verification — Hero and Mission (Sections 1–2)

- [ ] **Step 1: Screenshot the homepage**

```
wp_inspect(url="https://mwangazaintergrated.org/")
```

**Check Section 1 (Hero):**
- [ ] Full-bleed photo visible behind dark overlay
- [ ] White headline "Together We Rise, Together We Stand" visible
- [ ] White sub-line visible
- [ ] Gold "Get Involved" button visible
- [ ] White ghost "Our Story" button visible
- [ ] No spinner or broken layout

**Check Section 2 (Mission Strip):**
- [ ] Warm cream background visible
- [ ] Mission statement text visible (dark on cream)
- [ ] 3 pillar icon-boxes visible

If hero is blank or missing photo: the `HERO_IMAGE_URL` substitution may have failed. Re-check Task 1 Step 3 and re-run Task 3–4 with the correct URL.

---

## Task 7: Visual Verification — Challenges, Stats, Programmes (Sections 3–5)

- [ ] **Step 1: Scroll screenshot (check mid-page)**

```
wp_audit(url="https://mwangazaintergrated.org/")
```

**Check Section 3 (Challenges):**
- [ ] Red heading "Six Challenges. One Community. One Voice." visible
- [ ] 6 cards in a 3-column layout with gold left borders
- [ ] Each card has icon, title, and one-line description

**Check Section 4 (Stats):**
- [ ] Purple background band visible
- [ ] 4 large white numbers visible: 25+, 5+, 1, 6
- [ ] Labels below each number

**Check Section 5 (Programmes):**
- [ ] Cream background
- [ ] Red heading "What We Do"
- [ ] 4 white cards with gold icons
- [ ] Red outlined "See All Programmes →" button centred below

Note any console errors from `wp_audit` output and fix before proceeding.

---

## Task 8: Visual Verification — Story, Partners, Get Involved (Sections 6–8)

- [ ] **Step 1: Screenshot lower half of page**

From the `wp_audit` screenshot, check the lower sections:

**Check Section 6 (Field Story):**
- [ ] 2-column layout: image left, text right
- [ ] Gold label "COMMUNITY VOICE" visible
- [ ] Dark heading quote visible
- [ ] Attribution line in muted grey
- [ ] Red "Read Our Full Story →" link visible
- [ ] ⚠️ If the attribution shows `[Name]` — that is intentional placeholder; remind user to supply a real name and quote from a community member before launch

**Check Section 7 (Partners):**
- [ ] "WORKING ALONGSIDE" label visible
- [ ] AfriNov and Uraia logos visible (greyscale)
- [ ] Sub-line text visible

**Check Section 8 (Get Involved):**
- [ ] Dark charcoal background
- [ ] White heading and sub-line visible
- [ ] 3 cards with gold icons: Volunteer, Partner, Stay Updated
- [ ] Gold buttons on each card

---

## Task 9: Content Fixes and Pre-Launch Notes

- [ ] **Step 1: Update fonts.md with confirmed fonts**

```
Edit /home/pixel/projects/personal/wp/content/mwangaza/brand/typography/fonts.md
```

Replace contents with:
```markdown
# Mwangaza — Typography

| Role | Font | Weight | Notes |
|---|---|---|---|
| Headings (H1–H4) | Quicksand | 700 | Google Font — confirmed from Elementor kit |
| Body text | Nunito Sans | 400–600 | Google Font — confirmed from Elementor kit |
| Labels/overlines | Nunito Sans | 700 | Uppercase, letter-spaced |
```

- [ ] **Step 2: Flag required client content in RULES.md**

Add this to the `## Items Needing Confirmation` table in `content/mwangaza/RULES.md`:

```markdown
| Hero photo | ❌ needed | High-res landscape field photo for homepage hero |
| Community voice quote | ❌ needed | Real name + quote from CBO member for Section 6 |
| Verified stats | ❌ needed | Confirm 25+ years, 5+ years, petition count before launch |
| Partner logos | ⚠️ partial | AfriNov + Uraia available; confirm full list + transparent PNGs |
```

- [ ] **Step 3: Commit documentation updates**

```bash
git add content/mwangaza/brand/typography/fonts.md content/mwangaza/RULES.md
git commit -m "docs: update mwangaza fonts and flag homepage content requirements"
```

---

## Task 10: Final Audit

- [ ] **Step 1: Run full audit**

```
wp_audit(url="https://mwangazaintergrated.org/")
```

- [ ] No console errors related to missing assets
- [ ] No 4xx/5xx broken links in the homepage sections
- [ ] Load time under 6 seconds (improvement from current ~10s)
- [ ] All 6 section CTAs are clickable and go to published pages

- [ ] **Step 2: Test navigation links manually**

```
wp_inspect(url="https://mwangazaintergrated.org/volunteers/")
wp_inspect(url="https://mwangazaintergrated.org/about/")
wp_inspect(url="https://mwangazaintergrated.org/service/")
wp_inspect(url="https://mwangazaintergrated.org/blog/")
wp_inspect(url="https://mwangazaintergrated.org/contact/")
```

Each should load without spinner-only or blank renders.

- [ ] **Step 3: Final commit**

```bash
git add -A
git commit -m "feat: deploy Mwangaza homepage Story-First design

8-section homepage: hero, mission, challenges, stats,
programmes, field story, partners, get involved.
Fonts confirmed: Quicksand + Nunito Sans.
Client content still needed: hero photo, community quote,
verified stats, partner logos.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

---

## Rollback Procedure

If anything goes wrong after Task 4, restore the original homepage:

1. Open `/tmp/mwangaza-hp-backup.json`
2. Write a PHP helper that calls `update_post_meta(PAGE_ID, '_elementor_data', file_get_contents('backup.json'))`
3. Upload, execute, delete (same pattern as Task 4)
4. Run `wp_inspect` to confirm restoration
