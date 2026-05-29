# Mwangaza Homepage — Design Spec

**Date:** 2026-05-29  
**Site:** mwangazaintergrated.org  
**Platform:** WordPress + Elementor (Helpy theme)  
**Status:** Approved by user — ready for implementation planning

---

## Goal

Replace the current broken homepage (blank hero, demo stats, placeholder content) with a Story-First design that converts first-time visitors — primarily donors and funders — while serving the broader community audience.

**Primary audience:** Donors and funders  
**Secondary audience:** Community members, partners, volunteers  
**Tone:** Hope & community — grassroots movement, strength in unity, positive change  
**Principle:** Homepage is a front door, not the whole house. Each section teases and directs to the relevant page. No section should contain full page content.

---

## Brand Reference

| Token | Value |
|---|---|
| Gold (primary CTA) | `#F5A320` |
| Unity Red (headings) | `#E8342A` |
| Deep Purple (accent band) | `#8544AD` |
| Lime Green (icons) | `#72D64A` |
| Dark Charcoal (dark sections) | `#1A1A1A` |
| Warm Cream (light sections) | `#FEF3E2` |
| Body text | `#514F4C` |
| Heading font | Quicksand 700 |
| Body font | Nunito Sans 400–600 |

Primary CTA buttons: Gold `#F5A320` background, `#1A1A1A` text.  
H1/H2 headings: Unity Red `#E8342A` (except on dark/photo backgrounds — use white).

---

## Page Structure

### Section 1 — Hero

**Background:** Full-bleed community field photo (candid, real people in action — sourced from available field assets e.g. afrinov-training.jpg or community gathering shots)  
**Overlay:** Semi-transparent dark overlay, 40–50% opacity, ensures text readability  
**Layout:** Left-aligned text column (~55% width desktop), full-bleed mobile

**Content:**
- Headline (Quicksand 700, white, large): *"Together We Rise, Together We Stand"* *(or equivalent — confirm with client)*
- Sub-line (Nunito Sans, white, 18px): *"Mwangaza Integrated Project fights for the rights of communities along the Sondu Miriu, Kabondo East — through evidence, advocacy, and unity."*
- Primary CTA: **"Support Our Work"** — Gold `#F5A320`, dark text, rounded corners → `/donation/`
- Secondary CTA: **"Our Story"** — white ghost button (white border, white text) → `/about/`

**Elementor notes:** Use full-height container with background image + overlay. Text column as inner container. Two buttons side by side (stacked on mobile).

---

### Section 2 — Mission Strip

**Background:** Warm Cream `#FEF3E2`  
**Padding:** 80px top/bottom  
**Layout:** 2-column row (60/40 split) on desktop, stacked on mobile

**Content:**
- Left: Mission statement (Quicksand, `#1A1A1A`): *"We develop efficient, healthy communities through food security, capacity building, and rights advocacy in Kabondo East, Kenya."*
- Right: 3 pillars in a row:
  - 🌱 **Evidence-Based** — AfriNov methodology
  - ✊ **Nonviolent Advocacy** — community-led
  - 🤝 **Partnership-Driven** — local to national

**Elementor notes:** Icon List widget or 3-column inner container. No CTA in this section — breathing space only.

---

### Section 3 — The Challenges

**Background:** White  
**Section heading** (Quicksand 700, `#E8342A`): *"Six Challenges. One Community. One Voice."*  
**Sub-line** (Nunito Sans, `#514F4C`): *"AfriNov's situation analysis of Kabondo East identified six critical, interconnected issues Mwangaza is actively working to resolve."*

**Layout:** 3×2 card grid (2 columns tablet, 1 column mobile)

**Each card:**
- Icon in Lime Green `#72D64A` circle
- Bold title (Quicksand 700, `#1A1A1A`)
- One-line human-impact description (Nunito Sans, `#514F4C`)
- Left border: 4px solid Gold `#F5A320`
- Hover: lift effect (Elementor motion effect)

**The 6 challenges:**
| Title | One-liner |
|---|---|
| No Electricity | Families fish and farm in the dark — no cold storage, no studying after sunset |
| Water Scarcity | Limited access to clean, safe water for drinking and irrigation |
| Poor Road Infrastructure | Communities remain cut off from markets, hospitals, and services |
| Hippo Grass | River grass blocks fishing access and destroys livelihoods |
| Healthcare Gap | The nearest dispensary is too far for most families |
| Land Rights | Community members lack title deeds to land they have farmed for generations |

**Elementor notes:** 3-column grid container, each child container as a card. Use motion effects for hover lift.

---

### Section 4 — Impact Stats

**Background:** Deep Purple `#8544AD`  
**Layout:** 4 stats in a single row (2×2 on tablet, stacked on mobile)

**Each stat:**
- Number (Quicksand 700, white, 56px)
- Label (Nunito Sans, white, 16px, muted)

**Stats (⚠️ confirm exact numbers with client before build):**
| Number | Label |
|---|---|
| 25+ years | of community advocacy (from 2000) |
| 5+ years | as a registered CBO (from 2021) |
| 1 Petition | defended before Parliament (May 2022) |
| 6 challenges | actively being addressed |

**Content rule:** Do NOT use the demo stats currently on the live site (3,000+ partners, 69+ donors, 93+ projects). These are unverified and almost certainly wrong. Use only confirmed figures.

**Elementor notes:** Counter widget or heading widget per stat. 4-column grid container with purple background.

---

### Section 5 — Programmes

**Background:** Warm Cream `#FEF3E2`  
**Section heading** (Quicksand 700, `#E8342A`): *"What We Do"*  
**Sub-line** (Nunito Sans, `#514F4C`): *"Eight active programmes, one mission — building Kabondo East from the ground up."*

**Layout:** 4-column card grid (2 columns tablet, 1 column mobile) — show **4 cards only** on homepage, not all 8

**Each card:**
- Icon in Gold `#F5A320` circle
- Programme name (Quicksand 700, `#1A1A1A`)
- One-line description (Nunito Sans, `#514F4C`, 16px)
- No individual CTAs on cards

**4 featured programmes (rotate or confirm selection with client):**
- Sustainable Agriculture
- Human Rights Advocacy
- Health Education
- Women Empowerment

**Footer CTA** (centred, below grid): **"See All Programmes →"** — outlined button, `#E8342A` border and text → `/service/`

**Elementor notes:** 4-column grid. Single button widget centred below. Do not list all 8 programmes here.

---

### Section 6 — Field Story

**Background:** White  
**Layout:** 2-column 50/50 split (stacked on mobile — photo first, story below)

**Left column:** Full-height field photo — single community member or small candid group shot. Real, not posed.

**Right column (story block):**
- Label (Nunito Sans, Gold `#F5A320`, uppercase, 13px, letter-spaced): *"COMMUNITY VOICE"*
- Pull quote (Quicksand 700, `#1A1A1A`, 28px): e.g. *"We fought for 20 years along this river. Now we have a voice in Parliament."*
- Attribution (Nunito Sans, `#514F4C`, 15px): *"— [Name], CBO member, Kabondo East"*
- 2–3 sentences of context (Nunito Sans, `#514F4C`)
- CTA: **"Read Our Full Story →"** — text link in `#E8342A` → `/about/`
- Left border on quote block: 4px solid Gold `#F5A320`

**⚠️ Content rule:** This section requires a real quote, real name, and real photo from an actual community member. Client must supply. Do NOT use placeholder names or stock photos. If not yet available, leave section out of initial build and add later.

---

### Section 7 — Partners

**Background:** White  
**Layout:** Centred, minimal

**Content:**
- Label (Nunito Sans, `#514F4C`, uppercase, 13px, letter-spaced): *"WORKING ALONGSIDE"*
- Logo row: 3–4 logos, greyscale default, full colour on hover, equal spacing, centred
- One-line below (Nunito Sans, `#514F4C`, 15px): *"Mwangaza works with local, national, and international partners to amplify community voices."*

**⚠️ Asset note:** Logos must be transparent-background PNGs. Currently only AfriNov and Uraia are available. Client to confirm full partner list and supply assets.

**Elementor notes:** Image widget per logo in a flex row. No carousel needed with 3–4 logos.

---

### Section 8 — Get Involved

**Background:** Dark Charcoal `#1A1A1A`  
**Layout:** Centred heading + sub-line, then 3-column card row (stacked on mobile)

**Heading** (Quicksand 700, white, 36px): *"Your Help Can Change Lives"*  
**Sub-line** (Nunito Sans, white muted, 18px): *"There are three ways to stand with Mwangaza."*

**3 cards:**
| Title | One-liner | CTA | Destination |
|---|---|---|---|
| Donate | Fund the advocacy, training, and field work that drives real change | "Donate Now" | `/donation/` |
| Volunteer | Bring your skills to Kabondo East or support us remotely | "Get Involved" | `/volunteers/` |
| Partner | Organisations and institutions — let's amplify impact together | "Work With Us" | `/contact/` |

**Each card:** White outline icon, Quicksand 700 white title, Nunito Sans `#FEF3E2` one-liner, Gold `#F5A320` CTA button with dark text.

**Elementor notes:** 3-column grid with dark background. Each card is an inner container.

---

## Section Rhythm Summary

| # | Section | Background | Purpose |
|---|---|---|---|
| 1 | Hero | Full-bleed photo | Emotion + primary CTA |
| 2 | Mission strip | Warm Cream `#FEF3E2` | Context + credibility |
| 3 | Challenges | White | Problem framing |
| 4 | Impact stats | Deep Purple `#8544AD` | Proof + credibility |
| 5 | Programmes | Warm Cream `#FEF3E2` | Scope of work |
| 6 | Field story | White | Human connection |
| 7 | Partners | White | Trust signals |
| 8 | Get Involved | Dark Charcoal `#1A1A1A` | Conversion |

Alternating backgrounds (cream / white / purple / cream / white / white / dark) create visual rhythm without requiring images in every section.

---

## Pre-Build Checklist (content client must supply)

- [ ] Hero photo — real field shot, high resolution, landscape orientation
- [ ] Field story quote, name, and photo — confirmed real community member
- [ ] Verified impact stats — replace demo numbers
- [ ] Partner logos — transparent PNG, confirm full list
- [ ] Headline copy — confirm or replace suggested copy with client-approved wording

---

## Pages This Homepage Links To

| CTA | Destination | Current status |
|---|---|---|
| Support Our Work | `/donation/` | ⚠️ Draft — must publish before hero CTA works |
| Our Story | `/about/` | ✅ Published |
| See All Programmes | `/service/` | ✅ Published |
| Read Our Full Story | `/about/` | ✅ Published |
| Donate Now | `/donation/` | ⚠️ Draft |
| Get Involved | `/volunteers/` | ✅ Published |
| Work With Us | `/contact/` | ✅ Published |

**Critical:** `/donation/` must be published before this homepage goes live — two CTAs link to it.
