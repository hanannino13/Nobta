# NOBTA: System Architecture

Status: **Proposed (M0)**. The decisions referenced as ADR-NNN are in [ARCHITECTURE_DECISIONS.md](ARCHITECTURE_DECISIONS.md).

## 1. High-level view

```
 ┌───────────────────────┐      ┌────────────────────────┐
 │ Mobile app (Expo)     │      │ Admin dashboard (Vite) │
 │ Customer/Owner/Employee│      │ Platform Admin         │
 └──────────┬────────────┘      └───────────┬────────────┘
            │ HTTPS  Bearer token            │ HTTPS  (Sanctum SPA cookie, ADR-019)
            ▼                                ▼
 ┌─────────────────────────────────────────────────────────┐
 │ Laravel API (modular monolith)                          │
 │  /api/v1/*  (tenant + customer)   /api/admin/v1/*        │
 │  ┌──────────┐┌─────────┐┌─────────┐┌──────────┐┌───────┐ │
 │  │Identity  ││Tenancy  ││Industry ││Catalog   ││Booking│ │
 │  └──────────┘└─────────┘└─────────┘└──────────┘└───────┘ │
 │  ┌──────────┐┌─────────────┐┌────────┐┌──────┐            │
 │  │Scheduling││Notifications││Billing ││Audit │            │
 │  └──────────┘└─────────────┘└────────┘└──────┘            │
 └──────┬──────────────────┬───────────────────┬────────────┘
        ▼                  ▼                   ▼
   MySQL 8.4         Queue workers +       External: push (Expo),
   (single DB,       scheduler             email, SMS, payment
   shared schema)                          provider, maps
```

A **modular monolith** is one deployable Laravel app with strict internal module boundaries (ADR-002). We can extract a module later if a measured need appears.

## 2. Monorepo

npm workspaces cover the TS projects. Composer handles the backend separately (ADR-001).

```
apps/mobile         → @nobta/mobile   (Expo SDK 58, RN 0.88.1, React 19.3.0; see ADR-013)
apps/admin          → @nobta/admin    (Vite + React)
packages/api-types  → @nobta/api-types (empty placeholder; generated from OpenAPI in M2)
packages/shared-config → @nobta/shared-config (tsconfig preset; ESLint/Prettier presets in M2)
backend/            → Laravel 13 (Composer)
```

The backend's OpenAPI spec will be the contract. `packages/api-types` will be generated from it, and CI will fail when generated types don't match the spec (ADR-008, ADR-020). None of this exists yet: the generator, the generated types and the CI drift check are M2 scope.

## 3. Tenancy and isolation

**Tenant = Business.** The model is a single database with a shared schema and a `business_id` column on every tenant-owned table (ADR-003).

```
Business (tenant)
 ├── Branches (location, timezone, working hours)
 ├── Memberships (user ↔ business, role, branch scope)
 ├── Services, Resources, Schedules
 ├── Bookings
 └── BusinessCustomers (tenant-local view of a platform customer)
```

How tenant context gets resolved for each request:
1. Tenant endpoints are **route-scoped**: `/api/v1/businesses/{business}/...`.
2. Middleware `ResolveTenant` loads `{business}`, then checks that the authenticated user has an **active membership** in it. If not, the response is **404** (we don't reveal the business exists).
3. The resolved business and membership go into a request-scoped `TenantContext` service.
4. Tenant-owned Eloquent models use a `BelongsToTenant` trait. It adds a global scope from `TenantContext` and auto-fills `business_id` on create. Writing a model whose `business_id` doesn't match the context throws.
5. Policies then authorize the specific action (role/permission + branch scope).

**Never trusted:** a `business_id`, `branch_id` or `user_id` in a request body or header. Route-bound IDs are only *selectors*, and they're checked against membership. Child resources are always resolved through the tenant (`$business->services()->findOrFail($id)`), never with a global `Service::find($id)`.

**Customer endpoints** (`/api/v1/me/...`, `/api/v1/discover/...`) aren't tenant-scoped. They're scoped to the authenticated customer (`customer_id = auth user`), and public discovery returns only published, active businesses.

**Queues, commands and scheduler** run with no implicit tenant. Jobs carry `business_id` explicitly and set `TenantContext` at the start of `handle()`, and scheduled tasks iterate tenants explicitly.

## 4. Identity, authentication and authorization

- **One `users` table** holds every human using the mobile app (customers, owners, employees). Roles aren't stored on the user. They come from memberships (ADR-005).
- **A separate `admins` table and `admin` guard** for platform admins. An admin token can't call tenant endpoints, and a user token can't call `/api/admin/*`.
- **Laravel Sanctum** (ADR-004):
  - Mobile uses **API tokens** (Bearer), stored in `expo-secure-store`, one token per device, revocable, with expiry, scoped via token abilities. Access tokens live 30 minutes with rotating refresh tokens (ADR-016); Sanctum's global `expiration` is a 30-minute backstop (ADR-021).
  - Admin web uses **Sanctum SPA cookie auth** on its own subdomain, CSRF protected, with no bearer tokens (proposed in ADR-019, which refines ADR-004's earlier "cookie or short-lived tokens" option; ADR status: Proposed). This assumes the admin and API share a parent domain, which depends on the hosting topology (ROADMAP U5).
- **Authorization layers:**
  1. Guard (who can call this API surface)
  2. Tenant membership (`ResolveTenant`)
  3. Policy, using a role → permission mapping defined in code (`Permission` enum; Owner, Manager and Staff roles at launch)
  4. Branch scope (a staff member limited to Branch A can't see Branch B's calendar)
  5. Ownership (a staff member may only edit their own time off, a customer only their own bookings)

## 5. Business and branch ownership

- A Business has exactly one **primary owner** (`businesses.owner_user_id`) plus members with the `owner` role (co-owners). Ownership transfer is an explicit, audited action.
- A Branch belongs to one Business and has its own **IANA timezone**, address, geo point, working hours and holidays.
- A Membership has `role` and an optional `branch_ids` scope. An empty scope means all branches (owners/managers only).
- An Employee user may belong to several businesses (e.g. a freelance trainer), and each membership is independent.

## 6. Industry configuration

Industry differences live in **configuration plus a small set of capability flags**, not in branches of booking code (ADR-006).

- `industries`: a seeded catalog (beauty, clinic, fitness, education, home_services, professional_services).
- Each industry has a versioned **template**: terminology (e.g. "stylist" vs "practitioner" vs "trainer"), the booking modes it allows, default policies, enabled features, and the customer intake fields it allows.
- At creation a business copies the template into `business_settings`. The owner can then override settings within the limits the template allows.
- Capabilities (examples): `booking.mode.appointment`, `booking.mode.group_session`, `booking.location.customer_address`, `booking.waitlist`, `booking.online_meeting`, `catalog.multi_service_booking`.
- **Clinic guardrail:** the clinic template turns off free-text intake fields beyond the "reason for visit" category and file uploads, and limits the notes length. The policy is stated in product docs and enforced server-side (ADR-011).

## 7. Booking engine boundaries

The single **Booking** module owns everything about creating and changing bookings (ADR-007). The other modules ask it questions or listen to its events. They never write bookings directly.

| Concept | Description |
|---|---|
| **Service** | What's sold: duration, buffers before/after, price, capacity (1 = appointment, >1 = group), required resource types |
| **Resource** | What's consumed: staff member, room, equipment. Staff are resources linked to memberships. |
| **Schedule** | Working hours, breaks, time off and holidays per branch and resource (Scheduling module) |
| **Availability** | Computed slots = schedule − existing bookings − holds − buffers, with lead time and horizon rules applied |
| **Booking** | Customer + branch + one or more booking items (service, resource, start, end), status, source, policy snapshot |
| **Hold** | Short TTL reservation while the customer confirms. It prevents a race between "view slot" and "confirm". |

- **Booking modes:** `appointment` (1:1) and `group_session` (capacity N). `location_type` is `at_branch`, `at_customer` or `online`. A combination of these covers all six industries.
- **State machine:** `pending → confirmed → checked_in → completed`, plus the terminal states `cancelled_by_customer`, `cancelled_by_business`, `no_show` and `expired` (unconfirmed hold or pending). Transitions are guarded and audited.
- **Concurrency:** booking creation runs in a DB transaction and locks the affected resource-day rows (`SELECT ... FOR UPDATE` on a `resource_day_locks` row or equivalent). Overlap is re-checked inside the lock. Session capacity uses an atomic counter checked in the same transaction. Creation is idempotent via `Idempotency-Key` (see API conventions).
- **Time:** stored in UTC (`DATETIME`), with each branch's timezone stored and used for presentation and the availability calculation. DST-safe tests are required.
- **Policy snapshot:** cancellation and reschedule rules are copied onto the booking at creation, so later policy changes don't apply retroactively.
- **Domain events:** `BookingCreated`, `BookingConfirmed`, `BookingRescheduled`, `BookingCancelled`, `BookingCompleted`, `BookingNoShow`. Notifications, Audit and future Billing subscribe to them.

## 8. API

- REST with JSON, URI-versioned: `/api/v1` for mobile and `/api/admin/v1` for admin (ADR-008).
- An OpenAPI 3.1 spec will be generated from the backend code, with types generated into `packages/api-types`. This is planned for M2, when the tool is chosen (ADR-020); it is not implemented yet.
- Full conventions are in [API_CONVENTIONS.md](../api/API_CONVENTIONS.md).

## 9. Background jobs and notifications

- Laravel queues use the **database driver** in development and the first production deployment, then Redis when throughput needs it (ADR-009). Queues are named by priority: `critical` (booking confirmations), `default`, `notifications`, `low`.
- **Scheduler** (`schedule:run` / `schedule:work`) handles reminders (e.g. 24h and 2h before), hold and pending expiry, no-show auto-marking, and data retention tasks.
- **Notifications** use Laravel Notifications with these channels:
  - `database` (in-app inbox)
  - Expo Push (push tokens stored per device and per user)
  - `mail`
  - SMS (provider to be decided)
  - Each notification is localized to the recipient's language and respects their preferences.
- Jobs are **idempotent** and carry explicit `business_id`. Failed jobs are retried with backoff and alerted on.

## 10. Audit logging

An append-only `audit_logs` table, written by an `AuditLogger` service that domain actions call explicitly. It doesn't rely on model observers alone (ADR-010).

Each record has: `id`, `business_id` (nullable for platform actions), `actor_type` (user/admin/system), `actor_id`, `action` (e.g. `booking.cancelled`), `subject_type`, `subject_id`, `changes` (a JSON diff with sensitive fields redacted), `ip`, `user_agent`, `request_id` and `created_at`.

- Tenant owners see their own business's audit trail. Platform admins see all of it, and admin actions are always logged.
- Audit rows are never updated or deleted by the app. A retention policy gets defined before launch.

## 11. Cross-cutting

- **Localization:** `Accept-Language` header and a user preference. Translatable content is stored as JSON `{ "en": "...", "ar": "..." }` (ADR-012).
- **Money:** integer minor units plus an ISO 4217 currency, with the currency's precision respected (KWD = 3 decimals).
- **Observability:** a request ID on every request, structured logs, error tracking (provider to be decided).
- **Security:** rate limiting per route group, strict CORS, HTTPS only, secrets only in env, and no PII in logs.
  - **CORS** is strict by default: no browser origin is allowed unless listed in `CORS_ALLOWED_ORIGINS`, and credentials are disabled. Real origins are configured once the hosting topology (U5) is decided (ADR-021 D8).
  - **Rate-limit numbers** are not decided yet (ADR-021 D6); limiting must be active before the first auth or OTP endpoint.

## 12. Testing strategy

| Layer | Tooling | Must cover |
|---|---|---|
| Backend unit | Pest | Availability calculation, state machine, policy rules, money |
| Backend feature | Pest + MySQL test DB | Each endpoint: auth, validation, **tenant isolation** (cross-business access returns 404), happy path |
| Concurrency | Pest + parallel requests | No double-booking under concurrent create |
| Contract | OpenAPI drift check in CI (M2; not implemented yet) | Spec ↔ generated types |
| Mobile | Jest + jest-expo + RN Testing Library | Screens, hooks, RTL rendering |
| Admin | Vitest + RTL | Components, data hooks |
| E2E (later) | Maestro (mobile), Playwright (admin) | Critical booking flows |
