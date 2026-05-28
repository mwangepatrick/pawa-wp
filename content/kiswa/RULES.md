# KISWA Kenya — Content Rules & Guidelines

These are hard rules. Every page, post, or feature built on kiswakenya.org must comply.

---

## Hard Requirements (non-negotiable)

### Pages — must exist and be published
| Page | Slug | Notes |
|---|---|---|
| Gallery | `/our-gallery/` | Photo gallery of events, activities, community work |
| Downloads | `/downloads/` | Reports, forms, resources available to the public |
| About | `/about/` | Organisation identity, mission, history |
| Contact | `/contact/` | Phone, email, address, contact form |
| Programmes | `/service/` | All active programmes listed |
| Blog | `/blog/` | News and updates |
| Events | `/our-events/` | Upcoming and past events |
| Causes | `/causes/` | Cause/campaign pages |
| Donation | `/donation/` | Donation mechanism |
| Volunteers | `/volunteers/` | Volunteer recruitment |
| FAQ | `/faqs/` | Frequently asked questions |

### Content rules
- All pages must be published (`status: publish`) before launch
- Gallery must contain at least 6 images before going live
- Downloads page must list at least 1 document/resource
- Events page must list at least 1 upcoming or past event before going live
- Contact page must display the real phone (+254 745 696692) and email (kiswagroup2@gmail.com)
- Every page must have a featured image set

### Brand rules
- Primary logo (`kiswa.jpg`, WP ID: 6004) must appear in the header on every page
- White/secondary logo variant required for dark header backgrounds (not yet available)
- Colour palette: see `brand/colors/palette.md`
- Typography: see `brand/typography/fonts.md`
- All CTAs (Donate, Get Involved) must use Magenta `#EC4899`

### Social & contact rules
- Facebook link (`https://www.facebook.com/kiswakenya/`) must appear in header and footer
- Twitter/X link (`https://x.com/kiswagroup2`) must appear in header and footer
- Footer must display copyright: "Copyright © KISWA Kenya. All Rights Reserved."

---

## Content Folder Structure

```
content/kiswa/
├── RULES.md                    ← this file (hard rules)
├── profile.md                  ← org identity, contacts, social, alliances
├── brand/
│   ├── logos/                  ← kiswa.jpg (WP ID: 6004)
│   ├── colors/palette.md       ← brand colour palette
│   ├── typography/fonts.md     ← Poppins + Inter + Lora
│   ├── assets/README.md        ← brand asset inventory
│   └── theme-handoff.md        ← Kirki/theme mod keys + how to apply
├── pages/                      ← one .md per page (brief + WP ID)
├── posts/                      ← one .json per post (content)
├── menus/                      ← menu structure files
└── media/                      ← media manifest + source notes
```

---

## Checklist Before Launch

- [ ] All 11 required pages published
- [ ] Gallery has 6+ images
- [ ] Downloads page has 1+ document
- [ ] Events page has 1+ event listed
- [ ] Contact details are real (not placeholder)
- [ ] Logo visible in header (dark version)
- [ ] White logo uploaded for secondary use
- [ ] Social links set in theme (Facebook + Twitter)
- [ ] Brand colours applied via theme mods (see `brand/theme-handoff.md`)
- [ ] Typography set (Poppins + Inter)
- [ ] Footer copyright updated
- [ ] Under Construction plugin remains OFF
