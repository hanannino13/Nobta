# NOBTA: Product Overview

## Vision

NOBTA is a premium booking and business-management platform. Service businesses use it to take bookings, manage staff and branches, and serve customers in Arabic and English. One booking engine powers six industries, and each industry gets its own terminology and feature set.

## Surfaces and roles

| Role | Surface | Scope |
|---|---|---|
| **Customer** | Mobile app | Platform-wide identity. Discovers businesses, books, manages bookings and profile. Can book with any business. |
| **Business Owner** | Mobile app | Full control of the businesses they own: branches, staff, services, schedules, bookings, settings, reports. |
| **Employee** | Mobile app | Works inside one or more businesses or branches. Sees and manages their own schedule and bookings, within the permissions their role grants. |
| **Platform Admin** | Web admin dashboard | Operates the platform: onboarding, verification, industries, plans, support, moderation. **Separate identity and API from tenant users.** |

The same person can be a Customer and also an Employee or Owner. The mobile app handles this with a context switcher, not separate accounts.

## Industries

| # | Industry | Typical booking mode | Notes |
|---|---|---|---|
| 1 | Beauty & Salons | 1:1 appointment with a staff member | Multi-service bookings, staff preference |
| 2 | Clinics | 1:1 appointment with a practitioner | **Appointments only. No medical records, diagnoses, prescriptions or clinical notes.** |
| 3 | Fitness | Group class (capacity) and 1:1 personal training | Capacity, waitlist, packages and memberships later |
| 4 | Education | Group session/course and 1:1 tutoring | Recurring sessions, capacity |
| 5 | Home Services | 1:1 visit at the customer's address | Service area, travel buffer, address capture |
| 6 | Professional Services | 1:1 consultation (in person or online) | Online meeting link, longer durations |

Industries are **configuration**, not code forks (ADR-006). Every industry uses the same booking engine.

## Core capabilities (MVP target)

**Customer**
- Register or log in, profile, language preference (AR/EN)
- Discover businesses by industry, location and search
- View a business, its branches, services, staff and availability
- Book, reschedule, cancel (subject to business policy)
- Booking history, upcoming bookings, notifications/reminders

**Business Owner**
- Create a business, choose an industry, set up branches (address, timezone, working hours)
- Manage services (duration, price, buffers, capacity), staff and staff-service assignments
- Calendar of bookings per branch and staff. Manual bookings for walk-ins and phone calls.
- Booking policies: lead time, cancellation window, approval required or auto-confirm
- Basic reports: bookings, revenue estimate, no-shows

**Employee**
- Own schedule and bookings, check-in/complete/no-show actions
- Time-off requests (later)

**Platform Admin**
- Business onboarding and verification, suspension
- Industry templates and feature flags
- Platform users and support tools (read-only views, audited impersonation later)
- Audit log viewer

## Explicitly out of scope

- **Medical records** of any kind (EMR/EHR, diagnoses, prescriptions, clinical notes, uploaded medical documents). Clinic bookings carry only the appointment data.
- **Storing payment card data.** Payments go through a PCI-compliant provider's hosted checkout or tokenization.
- Microservices, a multi-region deployment, and a white-label app per business (possibly later).

## Localization

- Arabic (RTL) and English (LTR) at launch, across the mobile app, admin and server-generated content (notifications, emails).
- Business content (service names, descriptions) can be entered in both languages.
- Currency stored in minor units with the currency's own precision (e.g. KWD has 3 decimals).

## Success criteria for MVP (draft)

- A business can go from sign-up to a bookable public profile in under 15 minutes.
- No double-booking of a staff member or a capacity-limited session under concurrent requests (load-tested).
- Zero cross-tenant data leaks (enforced by automated isolation tests in CI).
- Full AR/EN parity on all customer-facing screens.

## Open product questions

See "Unresolved decisions" in [ROADMAP.md](../architecture/ROADMAP.md).
