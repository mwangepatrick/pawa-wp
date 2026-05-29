# Mwangaza Integrated Project — Content Rules & Guidelines

These are hard rules. Every page, post, or feature built on mwangazaintergrated.org must comply.

---

## ⚠️ Dangerous Operations — Absolute Prohibitions

### Elementor CSS Cache
**NEVER call `wp_clear_elementor_cache` to fix styling issues.**

Why: Clearing the cache deletes all files in `wp-content/uploads/elementor/css/`. Regular pages regenerate on the next front-end visit, but **Elementor Pro theme templates (header `post-1402`, footer `post-2571`, kit `post-8`) require an admin session to regenerate** — they will NOT come back from a front-end visit alone. This leaves the site broken until someone manually runs Elementor → Tools → Regenerate CSS in the admin.

Safe alternatives:
- To force a style update on a specific page: use `wp_update_elementor_data` (no-op replacement triggers a re-save and CSS regeneration)
- To update global kit CSS/colours: use `wp_update_elementor_kit`
- To actually clear and regenerate safely: only do it from the WP admin → Elementor → Tools → Regenerate Files & Data

If cache MUST be cleared via tools: first read and save every file in `public_html/wp-content/uploads/elementor/css/` locally, then clear, then immediately re-upload the critical files (`post-8.css`, `post-1402.css`, `post-2571.css`).

---

## Hard Requirements (non-negotiable)

### Pages — must exist and be published
| Page | Slug | Notes |
|---|---|---|
| Home | `/` | Hero + programmes overview + stats + CTA |
| About | `/about/` | Organisation identity, history, mission, vision |
| Services / Programmes | `/service/` | All active programmes listed |
| Blog | `/blog/` | News and updates |
| Events | `/our-events/` | Upcoming and past events |
| Gallery | `/our-gallery/` | Photo gallery of field work and events |
| Causes | `/causes/` | Cause/campaign pages |
| Donation | `/donation/` | Donation mechanism |
| Volunteers | `/volunteers/` | Volunteer recruitment |
| Contact | `/contact/` | Real phone, email, address, contact form |
| FAQ | `/faqs/` | Frequently asked questions |

### Content Rules
- All pages must be `status: publish` before launch
- No placeholder content (demo emails, fake phone numbers) on any live page
- Contact page must display the real email (Mwangazaintegratedproject@gmail.com) and real phone (TBD)
- Every page must have a featured image set
- Gallery must contain at least 6 images before going live
- Statistics (12+ years, 93+ projects, etc.) must be verified and accurate

### Brand Rules
- Logo must appear in the header on every page — use WP ID 6012 (`Mwangaza-Intergrated-Logo.png`)
- Colour palette: see `brand/colors/palette.md`
- Typography: see `brand/typography/fonts.md`
- All primary CTA buttons (Donate, Get Involved): Gold `#F5A320` background, dark `#1A1A1A` text
- Headings (H1/H2): Unity Red `#E8342A`
- Footer background: Dark Charcoal `#1A1A1A`
- NEVER use white text on Gold — use dark text only (contrast too low)

### Social & Contact Rules
- Real social media links must replace all placeholder icons before launch
- Footer must display correct copyright: "© [year] Mwangaza Integrated Project. All Rights Reserved."
- Contact page must remove demo addresses (Texas, North Carolina placeholders)

---

## Items Needing Confirmation ⚠️

| Item | Status | Notes |
|---|---|---|
| Real phone number | ✅ resolved | Chairperson: 0721922632, Secretary: 0724174017 |
| Key contacts | ✅ resolved | Full leadership table in profile.md |
| Sondu Miriu content | ✅ resolved | Full petition history and causes in causes.md + services.md |
| Programme details | ✅ resolved | All 8 programmes filled in services.md |
| Facebook URL | ✅ resolved | https://facebook.com/groups/144311313691995/ |
| Twitter/X URL | ❌ missing | Not provided — remove icon or leave pending |
| LinkedIn URL | ✅ resolved | https://www.linkedin.com/groups/22115011 |
| Instagram URL | ❌ missing | Not provided — remove icon or leave pending |
| Logo file | ✅ resolved | `C:\Users\ADMIN\Downloads\mwangaza_logo.jpg` — upload to WP, record ID in brand/assets/README.md |
| Brand colours | ✅ resolved | Extracted from logo — see brand/colors/palette.md |
| Donation payment method | ❌ missing | M-Pesa, bank details or plugin needed |
| Stats verification | ❌ pending | "12+ years" / "93+ projects" / "3,000+ partners" all suspect — see profile.md |
| Hero photo | ❌ needed | High-res landscape field photo for homepage hero (currently using afrinov-training.jpg as placeholder) |
| Community voice quote | ❌ needed | Real name + quote from CBO member for homepage Section 6 (currently shows [Name] placeholder) |
| Verified stats | ❌ needed | Confirm 25+ years, 5+ years, petition count before launch — do NOT keep demo numbers |
| Partner logos | ⚠️ partial | AfriNov + Uraia loaded; confirm full partner list + supply transparent PNG versions |

---

## Content Folder Structure

```
content/mwangaza/
├── RULES.md                    ← this file (hard rules)
├── profile.md                  ← org identity, contacts, social, history, leadership
├── brand/
│   ├── logos/                  ← logo file(s)
│   ├── colors/palette.md       ← brand colour palette
│   ├── typography/fonts.md     ← fonts in use
│   ├── assets/README.md        ← brand asset inventory
│   └── theme-handoff.md        ← theme/Kirki keys + how to apply
├── pages/
│   ├── home.md                 ✅ drafted
│   ├── about.md                ✅ drafted (full leadership + story)
│   ├── services.md             ✅ drafted (all 8 programmes with details)
│   ├── contact.md              ✅ drafted (real phones confirmed)
│   ├── causes.md               ✅ drafted (9 causes from petition)
│   ├── events.md               ✅ drafted (5 seed events)
│   ├── gallery.md              ✅ drafted (15 source images mapped)
│   ├── donation.md             ✅ drafted (⚠️ payment method TBD)
│   ├── volunteers.md           ✅ drafted
│   ├── blog.md                 ✅ drafted
│   └── faq.md                  ✅ drafted (12 Q&As)
├── posts/
│   ├── drug-free-society-campaign.md    ✅ ready to publish
│   └── petition-our-journey-to-parliament.md  ✅ ready to publish
├── menus/
│   └── menus.md                ✅ drafted
└── media/
    └── README.md               ✅ updated (15 source images listed)
```

---

## Checklist Before Launch

- [ ] All required pages published
- [ ] No placeholder content anywhere
- [ ] Real phone number set
- [ ] Real social media links set (Facebook, Twitter, LinkedIn, Instagram)
- [ ] Logo uploaded and set in header
- [ ] Brand colours applied
- [ ] Typography set
- [ ] Gallery has 6+ real images
- [ ] Contact details are real
- [ ] Copyright text correct in footer
- [ ] Statistics verified (years, projects, partners, donors)
- [ ] Under Construction plugin OFF
