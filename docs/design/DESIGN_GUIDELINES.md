# NOBTA: Design Guidelines

Status: **foundations only (M0)**. The visual identity (logo, brand palette, typefaces) isn't defined yet. See the unresolved decisions in the roadmap. This document fixes the *rules* that the identity will plug into.

## 1. Principles

1. **Premium and calm.** Generous spacing, restrained colour, clear hierarchy. The product should feel trustworthy for clinics and elegant for salons.
2. **Bilingual by design.** Every screen is designed in Arabic (RTL) and English (LTR). Neither is a translation afterthought.
3. **Fast to book.** A returning customer can rebook in three taps or fewer, and a new customer can complete a booking in under a minute.
4. **One system, many industries.** Industry differences show through terminology, iconography and enabled features, never through separate UI kits.
5. **Accessible.** WCAG 2.2 AA contrast, 44×44pt minimum touch targets, screen-reader labels in both languages, Dynamic Type / font scaling support.

## 2. Design tokens

Tokens are defined once and shared conceptually by mobile and admin. The concrete token package gets decided in M2 (moved from M1 on 2026-11-11), and values are placeholders until the brand is set.

| Group | Tokens |
|---|---|
| Colour (semantic) | `bg`, `surface`, `surface-raised`, `text`, `text-muted`, `border`, `primary`, `on-primary`, `success`, `warning`, `danger`, `info` |
| Booking status | `status-pending`, `status-confirmed`, `status-checked-in`, `status-completed`, `status-cancelled`, `status-no-show`. These are **always paired with an icon or label, never colour alone** |
| Spacing | 4-pt scale: 4, 8, 12, 16, 24, 32, 48 |
| Radius | `sm` 6, `md` 10, `lg` 16, `full` |
| Typography | `display`, `title`, `heading`, `body`, `body-sm`, `caption`. Each needs an Arabic-capable family with line height ≥ 1.5 for Arabic body text |
| Elevation | `0`–`3`, subtle shadows |

- Dark mode: semantic tokens have light and dark values from the start, even if dark mode ships later.
- Components reference only semantic tokens, never raw hex values.

## 3. RTL / LTR rules

- Use **logical** properties only: `start`/`end`, `marginStart`, `paddingEnd`, CSS `margin-inline-start`, `inset-inline-end`. Don't use `left`/`right` in layout code; lint rules get added in M2 (moved from M1 on 2026-11-11).
- Mirror directional icons (back arrow, chevrons, progress) in RTL. Don't mirror non-directional icons (clock, play/media, logos, checkmarks).
- Phone numbers, emails, prices and booking reference codes are LTR runs inside RTL text (wrap them in direction isolation).
- Time pickers and calendars follow the locale's week start (to be confirmed per market: Saturday or Sunday) and the locale's direction.
- Typography: Arabic text must not use letter-spacing, and it needs a font with proper Arabic shaping. Test long Arabic strings, which run roughly 20–30% longer or shorter than English.
- In React Native, switching direction requires an app reload. The language switch screen explains this before reloading.

## 4. Role experiences (mobile)

| Experience | Primary navigation (draft) |
|---|---|
| Customer | Discover · Bookings · Notifications · Profile |
| Business (Owner/Manager) | Today · Calendar · Customers · Manage (services, staff, branches, settings) |
| Business (Staff) | Today · My calendar · Notifications · Profile |

- A context switcher in the profile/header moves between "Customer" and each business membership.
- Screens and actions the current role can't use are hidden, not just disabled. The server enforces permissions regardless.

## 5. Industry adaptation

- Terminology comes from the industry template (e.g. *Stylist / Practitioner / Trainer / Tutor / Technician / Consultant*). Copy uses tokens such as `{{resource_label}}`, never hard-coded industry nouns.
- Each industry has an icon set and an optional accent, used inside the shared component library.
- **Clinic screens** show no medical fields. "Reason for visit" is the only optional context, and it isn't labelled as a diagnosis or symptoms field.

## 6. Admin dashboard

- Desktop-first, responsive down to tablet.
- Data tables with server-side filtering and sorting, and audit-log views showing actor, action and timestamp in the viewer's timezone.
- Destructive or sensitive actions (suspend business, impersonate) need a confirmation step and a reason field, which is written to the audit log.

## 7. Content and tone

- Short, friendly, respectful copy. Arabic copy is written natively (Modern Standard Arabic, with Gulf conventions where appropriate) and isn't machine-translated in production.
- Error messages say what happened and what to do next, in the user's language.
- Dates show the weekday plus a date relative to today where helpful ("Tomorrow, 10:30").

## 8. Deliverables before UI build (M1–M2)

- Brand identity and typefaces (Arabic + Latin pairing)
- Token values (light and dark)
- Core component inventory: Button, Input, Select, DateStrip, TimeSlotGrid, BookingCard, StatusBadge, Avatar, EmptyState, Sheet, Toast
- Key flows wireframed in AR and EN: onboarding, discover → book, owner "Today" view
