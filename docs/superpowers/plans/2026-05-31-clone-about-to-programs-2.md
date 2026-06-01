# Clone About to Programs 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Clone the live Mwangaza About page into a new published WordPress page at the `programs-2` slug.

**Architecture:** This is a content clone, not a redesign. The source page content will be copied from the published About page and reused as-is in a new page so the structure, Elementor markup, images, and copy remain identical. After creation, the new page will be verified in WordPress to ensure the duplicate exists, is published, and uses the requested slug.

**Tech Stack:** WordPress REST API via `pawa-wp-mcp`, Elementor page content stored in `post_content`.

---

### Task 1: Capture the source page content

**Files:**
- Reference: `/home/pixel/projects/personal/wp/content/mwangaza/pages/about.md`
- Reference: `/home/pixel/projects/personal/wp/content/mwangaza/profile.md`

- [ ] **Step 1: Inspect the live About page content**

Use the published About page (`id: 1956`) as the canonical source for the clone.

```text
Source page: https://mwangazaintergrated.org/about/
WP page ID: 1956
```

- [ ] **Step 2: Confirm the target slug**

Use the user-provided slug:

```text
Target slug: programs-2
```

### Task 2: Create the cloned page

**Files:**
- Create: new WordPress page on site `mwangaza`

- [ ] **Step 1: Create a new page in WordPress**

Create a published page using the exact About page HTML as the content source, with a title that matches the slug-derived page name.

```text
Site alias: mwangaza
New page title: Programs 2
New page slug: programs-2
Status: publish
Source content: copy from page ID 1956
```

- [ ] **Step 2: Preserve the Elementor markup**

Copy the full `post_content` from page `1956` without trimming sections or rewriting markup so the clone behaves like the original page.

### Task 3: Verify the clone

**Files:**
- No repo files should change in this task unless verification reveals a content issue.

- [ ] **Step 1: Fetch the newly created page**

Confirm the page exists in WordPress and check the permalink, title, and status.

```text
Expected slug: /programs-2/
Expected status: publish
Expected title: Programs 2
```

- [ ] **Step 2: Inspect the live render**

Open the new page in the browser and confirm it renders the same structure as the About page.

```text
Expected result: same section order, same images, same layout, new URL
```

- [ ] **Step 3: Record completion**

Report the new page URL and ID back to the user.

