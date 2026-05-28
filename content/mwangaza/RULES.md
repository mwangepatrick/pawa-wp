# Mwangaza Integrated Project — Content Rules & Guidelines

These are hard rules. Every page, post, or feature built on mwangazaintergrated.org must comply.

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
- Logo must appear in the header on every page
- Colour palette: see `brand/colors/palette.md`
- Typography: see `brand/typography/fonts.md`
- All donation/CTA buttons must use consistent brand colour (TBD — confirm with client)

### Social & Contact Rules
- Real social media links must replace all placeholder icons before launch
- Footer must display correct copyright: "© [year] Mwangaza Integrated Project. All Rights Reserved."
- Contact page must remove demo addresses (Texas, North Carolina placeholders)

---

## Items Needing Confirmation ⚠️

These must be resolved before content build begins:

| Item | Status | Notes |
|---|---|---|
| Real phone number | ❌ missing | Site shows +36 636425 (invalid — Hungarian code) |
| Facebook URL | ❌ missing | Icon on site but no link |
| Twitter/X URL | ❌ missing | Icon on site but no link |
| LinkedIn URL | ❌ missing | Icon on site but no link |
| Instagram URL | ❌ missing | Icon on site but no link |
| Logo file | ❌ missing | Need source file or WP media ID |
| Brand colours | ❌ missing | Confirm primary/accent palette |
| Key contact person | ❌ missing | Who runs the org day-to-day? |
| Sondu Miriu content | ⚠️ partial | Background known; need more programme details |

---

## Content Folder Structure

```
content/mwangaza/
├── RULES.md                    ← this file (hard rules)
├── profile.md                  ← org identity, contacts, social, history
├── brand/
│   ├── logos/                  ← logo file(s)
│   ├── colors/palette.md       ← brand colour palette
│   ├── typography/fonts.md     ← fonts in use
│   ├── assets/README.md        ← brand asset inventory
│   └── theme-handoff.md        ← theme/Kirki keys + how to apply
├── pages/                      ← one .md per page
├── posts/                      ← one .json per post
├── menus/                      ← menu structure
└── media/                      ← media manifest + source notes
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
