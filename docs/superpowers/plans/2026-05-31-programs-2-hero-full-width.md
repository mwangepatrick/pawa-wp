# Programs 2 Hero Full-Width Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the `Programs 2` hero into a full-width text-led section by removing the large image column while keeping the page editable in Elementor.

**Architecture:** Update only the `prghero` Elementor container tree on page `6220`. Keep the existing heading, text, and buttons widgets, but change the hero container from a split layout to a single-column full-width layout. Remove the image column from the Elementor JSON rather than replacing the page body, so the page remains fully editable in Elementor.

**Tech Stack:** WordPress Elementor containers, `pawa-wp-mcp` Elementor data update tools.

---

### Task 1: Inspect the current hero structure

**Files:**
- Modify: none
- Test: none

- [ ] **Step 1: Inspect the hero subtree**

Use `wp_get_elementor_data` for page `6220` and confirm the `prghero` container contains:
- `prgherorow`
- `prgherotxt`
- `prgheroimgcol`

- [ ] **Step 2: Identify the exact JSON block to remove**

Confirm the image column block is the `prgheroimgcol` container that wraps `prgheroimgbox` and `prgheroimg`.

### Task 2: Update the hero layout

**Files:**
- Modify: WordPress post meta for page `6220` via Elementor data update
- Test: live page render at `/programs-2/`

- [ ] **Step 1: Change the hero container to full width**

Replace:
```json
"content_width": "boxed"
```
with:
```json
"content_width": "full_width"
```

- [ ] **Step 2: Make the text column occupy the full row**

Replace:
```json
"width": {
  "unit": "%",
  "size": 58
}
```
with:
```json
"width": {
  "unit": "%",
  "size": 100
}
```

- [ ] **Step 3: Remove the image column**

Delete the entire `prgheroimgcol` container block, including its nested `prgheroimgbox` and `prgheroimg` widgets.

- [ ] **Step 4: Verify Elementor still loads the page**

Open `/programs-2/` and confirm the page renders with:
- the hero text spanning the section width
- no large right-side image panel
- editable Elementor content still present

