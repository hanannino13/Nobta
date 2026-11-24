# Architecture Decision Records

Format: Context → Decision → Consequences. Status values: **Proposed**, **Accepted**, **Superseded by ADR-NNN**.
New ADRs are appended. Existing ADRs are never rewritten; if a decision changes, a new ADR supersedes the old one.

All ADRs below are **Proposed** (M0) unless their own **Status** line says otherwise (ADR-021 is Accepted). They become Accepted when the product owner reviews them, or when they're validated by working code in M1–M2.

---

## ADR-001: Monorepo with npm workspaces and a separate Composer backend

**Context.** There are three apps (mobile, admin, backend) and the TS clients share API types. pnpm and Yarn aren't installed, and npm 11.8.0 is. Expo SDK 52+ supports npm workspaces out of the box.

**Decision.** One Git repository. npm workspaces cover `apps/*` and `packages/*`. `backend/` is a standalone Composer project inside the repo and isn't an npm workspace. No Nx or Turborepo for now.

**Consequences.**
- One clone and one place for docs and atomic cross-stack changes.
- No extra package manager or build orchestrator to install or learn.
- If CI build times grow, Turborepo can be layered on later without restructuring.
- npm hoisting can occasionally cause duplicate React copies in Expo. This gets mitigated with Expo's monorepo guidance and verified in M1.

---

## ADR-002: Modular monolith; no microservices

**Context.** Early-stage product with a small team. Booking consistency needs transactional integrity across availability and bookings.

**Decision.** A single Laravel application split into domain modules under `app/Domain/*` (Identity, Tenancy, Industry, Catalog, Scheduling, Booking, Notifications, Billing, Audit). Modules talk to each other through service classes and domain events, never by reaching into another module's tables or models.

**Consequences.** One deployment, real DB transactions, simple local setup. The boundary discipline is enforced by convention and code review, and arch tests (Pest `arch()`) get added in M1.

---

## ADR-003: Single database, shared schema, `business_id` tenant column

> Refined by ADR-017 (defense in depth; global scopes are not a security boundary).

**Context.** Options considered: (a) database per tenant, (b) schema per tenant (not native in MySQL), (c) shared schema with a tenant column. Customers book across many businesses, and platform admins need cross-tenant reporting.

**Decision.** Option (c). Every tenant-owned table has a non-null, indexed `business_id`, and every composite index used for tenant queries starts with `business_id`. Isolation is enforced in layers:
1. Route-scoped tenant resolution plus a membership check (`ResolveTenant` middleware).
2. A request-scoped `TenantContext`, with a `BelongsToTenant` global scope and a write guard.
3. Policies for each action.
4. Mandatory automated cross-tenant tests for every tenant endpoint.

No third-party tenancy package at first; it's a small amount of code and we keep full control.

**Consequences.**
- Simple ops, one migration path, efficient cross-tenant customer and admin queries.
- Risk: a forgotten scope means a data leak. This is mitigated by the layered enforcement, the CI tests, and code review checklists.
- If a large customer later needs physical isolation, it can move to its own database, but that's a future ADR.

---

## ADR-004: Laravel Sanctum for authentication (mobile tokens and admin SPA)

> Token lifecycle superseded by ADR-016 (short-lived access tokens + rotating refresh tokens with reuse detection). Admin authentication refined by ADR-019.

**Context.** We need first-party auth for a native mobile app and a web admin. OAuth2 (Passport) is only needed for third-party API clients, which aren't in scope.

**Assessment of Sanctum's suitability.**
- *Mobile:* Sanctum API tokens are hashed at rest, revocable per device, and support abilities and expiry. That's suitable. Sanctum has **no built-in refresh tokens**, so we use moderately long-lived tokens (e.g. 30–60 days with sliding renewal via a `/auth/token/refresh` endpoint that rotates the token) plus revocation on logout or password change.
- *Admin web:* Sanctum's SPA mode (stateful session cookie and CSRF) is the most secure option when the admin and API share a top-level domain. Otherwise it falls back to short-lived tokens kept in memory.

**Decision.** Use Sanctum. Two guards: `sanctum` with `users` for mobile, and `admin` with an `admins` provider for the admin dashboard. Admin routes also require admin-specific middleware, and MFA is required for admins before launch.

**Consequences.** One small, first-party package that fits Laravel conventions. Refresh and rotation logic is ours to build and test. The admin cookie vs token choice depends on the deployment domains (an unresolved decision).

---

## ADR-005: Separate identities for platform admins and tenant users; roles come from memberships

**Context.** A platform admin must never be confused with a business owner, and one person may be a customer in one place and an employee in another.

**Decision.**
- The `admins` table, guard, routes (`/api/admin/v1`) and app (`apps/admin`) are all separate. They don't share login sessions or tokens with `users`.
- The `users` table holds all mobile users. **Customer** is a platform-level capability every user has. **Owner, Manager and Staff** are roles on a `business_memberships` row, with an optional branch scope.
- Permissions are a code-defined `Permission` enum mapped to roles in a single class. No `spatie/laravel-permission` at launch. If businesses later need custom roles, that's a new ADR (spatie with teams or a custom table).
- Admin "impersonation" (if added) is read-only by default, always audited, and time-limited.

**Consequences.** Clear security boundary. No DB-driven RBAC complexity early. Adding custom roles later needs a migration path from the enum mapping.

---

## ADR-006: Industries are configuration, not code forks

**Context.** Six industries share about 90% of their booking behaviour. Duplicating logic per industry multiplies bugs and test effort.

**Decision.** An `industries` catalog plus versioned industry templates (terminology, allowed booking modes, default policies, capability flags, allowed intake fields). A business copies its template into `business_settings` at creation. Code checks **capabilities** (`$settings->allows('booking.waitlist')`), never the industry name (`if ($industry === 'fitness')` is prohibited outside the template definitions).

**Consequences.** New industries are mostly data. Features must be designed as generic capabilities. Template versioning lets us change defaults without touching existing businesses.

---

## ADR-007: One booking engine with explicit boundaries

> Concurrency and holds refined by ADR-018.

**Context.** Booking is the core domain and the main source of correctness risk (double-booking, timezone bugs).

**Decision.** The Booking module is the only writer of bookings and holds. It exposes `AvailabilityQuery`, `CreateBooking`, `RescheduleBooking`, `CancelBooking` and `TransitionBookingStatus` actions. Its modes are `appointment` and `group_session`, and its location types are `at_branch`, `at_customer` and `online`. Concurrency control uses transactions and row-level locks, idempotency keys, and short-TTL holds. All times are stored in UTC, and each branch has an IANA timezone. Policies are snapshotted per booking.

**Consequences.** One place to test and harden. Industry-specific UX is built on the same API. The engine needs heavy unit, concurrency and DST tests from the first milestone that touches it.

---

## ADR-008: REST with URI versioning; OpenAPI as the contract

> Tooling and drift check refined by ADR-020. Timing changed 2026-11-11: the OpenAPI generator is chosen in M2, not M1 (ADR-020; ROADMAP, "Moved from M1 to M2").

**Decision.** `/api/v1/...` for mobile and customer use, `/api/admin/v1/...` for admin. Within a version, changes are additive only. Breaking changes create `v2`, with a published deprecation window (`Deprecation` and `Sunset` headers). The OpenAPI 3.1 spec is generated from the backend (tool to be chosen in M1, e.g. Scramble), TypeScript types are generated into `packages/api-types`, and CI checks for drift.

**Consequences.** Clients get compile-time safety, and API changes are visible in review.

---

## ADR-009: Database queue driver first, Redis later

**Context.** Laragon ships Redis 5.0.14, an outdated unofficial Windows build. We don't know the expected volume yet.

**Decision.** Use the `database` queue, cache and session drivers locally and in the first production environment. Move to managed Redis when we see queue latency or throughput problems, or need atomic locks at scale. Jobs are written to be driver-agnostic and idempotent.

**Consequences.** No extra infrastructure on day one. The database queue adds DB load, which gets monitored.

---

## ADR-010: Explicit, append-only audit log

**Decision.** A custom `audit_logs` table written by an `AuditLogger` service, called explicitly from domain actions (not only Eloquent observers, which miss bulk queries and lack intent). Sensitive fields are redacted. The app never updates or deletes audit rows. No third-party auditing package at first.

**Consequences.** Audit entries carry business meaning (`booking.cancelled_by_business`) rather than raw row diffs. The cost is that developers must remember to call it, so feature tests assert that audit entries exist for sensitive actions.

---

## ADR-011: No medical records and no card data, as enforced constraints

**Decision.**
- *Clinics:* bookings store only the service, practitioner, time, customer contact and an optional short "reason for visit" chosen from a configured list (or a short free-text field, max length enforced). There are no file uploads, diagnosis, prescription or clinical-notes fields, and staff-facing notes are disabled for the clinic industry template. Terms of service say NOBTA isn't a medical record system.
- *Payments:* card numbers, CVV and expiry never touch NOBTA servers or the app's storage. We only use the provider's hosted checkout, SDK or tokenization, and only store provider references (`payment_intent_id`, `last4` and brand if the provider returns them).

**Consequences.** Far smaller regulatory surface (health data and PCI DSS scope reduced to SAQ-A level). Product requests that conflict with this need a new ADR plus a compliance review.

---

## ADR-012: Localization: AR (RTL) and EN (LTR) end to end

> Timing changed 2026-11-11: the mobile i18n library choice and the RTL (`left`/`right`) lint rules moved from M1 to M2 (ROADMAP, "Moved from M1 to M2"). The decision itself is unchanged.

**Decision.**
- Mobile: i18n library chosen in M1 (candidates: `i18next` + `react-i18next` with `expo-localization`). Direction via `I18nManager`, and layouts use `start`/`end` and never `left`/`right`.
- Admin: `dir` attribute on `<html>`, CSS logical properties.
- Backend: Laravel `lang/{en,ar}`. Translatable DB fields are stored as JSON `{"en","ar"}` with a cast. Notifications are rendered in the recipient's locale.
- Numbers, dates and currency are formatted per locale. We still need to decide between Western and Arabic-Indic digits (see the unresolved decisions).

**Consequences.** Switching direction in React Native requires an app reload, and the UX for that is designed in M6.

---

## ADR-013: M1 version baseline (corrects the M0 report)

**Context.** The M0 report paired Expo SDK 58 with React Native 0.85. That was wrong: RN 0.85 belongs to an earlier SDK. In M1 the pairing was re-verified against Expo's own published metadata (`npm view expo dist-tags`, `expo-template-default@sdk-58` dependencies) and then validated with Expo's tools.

**Decision.** These versions were selected and installed in M1. Expo-managed packages were resolved by `npx expo install`, with no manual overrides.

| Area | Package | Version |
|---|---|---|
| Mobile | expo | ~58.0.23 |
| | react-native | 0.88.1 |
| | react / react-dom | 19.3.0 (exact, per SDK 58 template) |
| | expo-router | ~58.0.30 |
| | expo-dev-client | ~58.0.18 |
| | typescript | ~6.0.3 |
| | jest / jest-expo | ~30.5.2 / ~58.0.14 |
| | eslint / eslint-config-expo | 9.39.5 / ~58.0.4 |
| Admin | vite | ^8.3.6 |
| | typescript | ~7.0.2 (Vite template default) |
| | react / react-dom | 19.3.0 (pinned to match mobile, see below) |
| | vitest | ^5.1.0 (peer: vite ^6.4 \|\| ^7 \|\| ^8) |
| | oxlint | ^1.90.0 (Vite template default) |
| Backend | laravel/framework | 13.40.0 |
| | laravel/sanctum | 4.3.3 |
| | pestphp/pest | 4.7.8 |
| | larastan/larastan | 3.14.0 (level 8) |
| | laravel/pint | 1.32.1 |
| Runtime | Node | 24.13.1 (Expo requires ^22.13 \|\| ^24.3) |
| | PHP | 8.3.30 |
| | MySQL | 8.4.3 |

Validation run in M1: `npx expo install --check` (up to date), `npx expo install --fix` (no changes), `npx expo-doctor` (20/20 checks passed).

**Notes.**
- **React pin in admin:** the Vite template asked for `react ^19.3.1`, while Expo SDK 58 pins `19.3.0`. Two React copies in one npm workspace can break React Native at runtime, so the admin app is pinned to Expo's validated `19.3.0`. `npm ls react` shows a single deduplicated copy. Revisit when Expo bumps React.
- **Pest 4 rather than 5:** Pest 5 requires PHP ≥ 8.4. We stay on Pest 4.7.x until the PHP runtime is upgraded (tracked in the roadmap).
- **Two TypeScript majors:** mobile uses TS 6 (Expo template) and admin uses TS 7 (Vite template). Each is nested per workspace. Converge when Expo adopts TS 7.

**Consequences.** Upgrades follow Expo's SDK cadence: bump `expo`, then `npx expo install --fix`, then `npx expo-doctor`. Never hand-edit RN or React versions in the mobile app.

---

## ADR-014: Egypt as the proposed first launch market (multi-market ready)

**Status.** Proposed, subject to product-owner confirmation **before** payment and SMS provider integration.

**Context.** Several design details depend on the first market: currency precision, timezone and DST, phone number formats, provider availability and data-protection law.

**Decision.** Plan for Egypt first, without hard-coding it anywhere:
- **Currency:** EGP (2 minor-unit decimals). Money stays `amount_minor` + ISO 4217 `currency` (see DATABASE_STRATEGY.md), and precision comes from a currency table, never from code constants.
- **Timezone:** `Africa/Cairo`. Egypt observes DST again (since 2023), so DST transition tests are mandatory for availability and reminders. Branch timezones stay per branch.
- **Phone:** E.164 storage (`+20…`), validated with a phone-number library chosen in M2. Country is a property of the user and the business, never assumed.
- **Locale:** Arabic first (Egyptian audience), English second. Copy should suit an Egyptian Arabic audience while staying in Modern Standard Arabic for UI text (content review needed).
- **Markets as data:** a `countries` / `markets` configuration (currency, default timezone, phone rules, enabled providers, tax settings) so new markets get added through configuration.
- **Providers behind interfaces:** `OtpSender`, `SmsSender`, `PaymentGateway` and `PushSender` contracts, bound per market. Egyptian candidates (e.g. Paymob, Fawry, Kashier for payments) are **not** selected or integrated yet.
- **Data protection:** Egypt's Personal Data Protection Law (Law No. 151 of 2020) applies. A compliance review is required before launch, including data residency and consent requirements.

**Consequences.** No market-specific `if` statements. Adding a market means configuration plus provider bindings, and possibly a translation pass.

---

## ADR-015: Phone OTP as primary customer authentication, email optional

**Status.** Proposed. No paid OTP or SMS provider gets integrated yet.

**Decision.**
- **Primary flow:** the customer enters a phone number. The server issues a 6-digit OTP. The customer verifies it and receives tokens (ADR-016). New phone numbers create a `users` row on first successful verification.
- **Email** is optional, used for receipts and account recovery, and has its own verification. Email and password login can be enabled per market later. Owners and employees use the same phone OTP flow.
- **OTP storage and checks:** only an HMAC of the code is stored (never the plain code), with a 5-minute expiry. Each code allows at most 5 attempts before it's invalidated. Codes are single use and compared in constant time.
- **Abuse controls:**
  - Rate limits per phone, per IP and per device.
  - A resend cooldown with exponential backoff.
  - A daily cap per phone.
  - A country allowlist per market, to limit SMS-pumping fraud.
  - Generic responses that don't reveal whether an account exists.
- **Delivery abstraction:** an `OtpSender` contract.
  - `LogOtpSender` writes the code to the local log and **refuses to boot outside `local`/`testing`**.
  - `FakeOtpSender` serves automated tests.
  - A real SMS or WhatsApp provider is chosen after ADR-014 is confirmed.
- **Phone number changes** require OTP verification of the new number and revoke all sessions (ADR-016).
- **Audit:** every OTP request, failure, lockout and success is audited without the code itself.

**Consequences.** SMS cost and deliverability become operational concerns. The provider abstraction keeps M2 testable with no paid service.

---

## ADR-016: Mobile token lifecycle: short-lived access tokens, rotating refresh tokens, reuse detection

> The Sanctum `expiration` backstop value (30 minutes) is fixed by ADR-021.

**Supersedes** the long-lived token approach in ADR-004 (Sanctum itself is kept).

**Decision.**
- **Access token:** a Sanctum personal access token with `expires_at` = 30 minutes. It carries abilities, is bound to a `device_id` (stored on the token row), and is sent as `Authorization: Bearer`.
- **Refresh token:** a custom `refresh_tokens` table with these columns:
  - `id`
  - `user_id`
  - `device_id`
  - `family_id`
  - `token_hash` (SHA-256 of a 256-bit random value; the plain value is never stored)
  - `expires_at` (sliding 30 days, absolute cap 90 days from family creation)
  - `used_at`
  - `revoked_at`
  - `replaced_by_id`
  - `created_ip`
  - `user_agent`
- **Rotation:** `POST /api/v1/auth/token/refresh` runs inside a transaction with the refresh token row locked `FOR UPDATE`. It marks the presented token `used_at`, issues a new access and refresh pair in the same family, and revokes the previous access token.
- **Reuse detection:** if a refresh token that's already used or revoked is presented again, the server treats it as theft. It revokes the **whole family** and every access token for that device, writes a security audit event, and returns 401. The legitimate client then has to log in again.
- **Revocation triggers:**
  - Logout revokes that device's family.
  - "Log out all devices", a phone number change, or an admin suspension revokes every family.
  - A user can list and revoke devices.
- **Client storage:** both tokens go in `expo-secure-store` only (Keychain / Android Keystore). There's a single in-flight refresh at a time; concurrent 401s wait on one refresh promise to avoid false reuse detection.
- **Expiry cleanup:** a scheduled job prunes expired tokens. Sanctum's `expiration` config is set too, as a backstop.
- **Tests:** rotation, reuse detection (family revoked), concurrent refresh race, expiry, logout and revoke-all.

**Consequences.** More code than plain Sanctum tokens, but stolen tokens have a short window and refresh-token theft is detectable.

---

## ADR-017: Tenant isolation in depth (global scopes are not the security boundary)

**Refines** ADR-003. Eloquent global scopes are kept only as a **safety net**. They can be bypassed (`withoutGlobalScopes`, query builder, raw SQL, relations loaded without the scope), so security never depends on them.

**Layers, in order of enforcement:**
1. **Route-level tenant resolution.** `ResolveTenant` middleware loads `{business}` from the route and requires an active membership for the authenticated user. Otherwise it returns 404.
2. **Scoped route model binding.** Tenant routes use `->scopeBindings()`, so `{service}` resolves only through `$business->services()`.
3. **Policies check tenancy explicitly.** Every policy method on a tenant model asserts `$model->business_id === $tenant->id` **and** the role/permission and branch scope. It never relies on the query having been scoped.
4. **Database constraints.**
   - Tenant tables expose `UNIQUE (business_id, id)`.
   - Child tables reference parents with **composite foreign keys** `(business_id, parent_id) → parent(business_id, id)`. This makes a cross-tenant reference (e.g. a booking item pointing at another business's staff member) impossible at the database level.
   - `business_id` is `NOT NULL` with an FK to `businesses`.
5. **Write guard.** A `BelongsToTenant` model hook rejects saving a model whose `business_id` doesn't match the current `TenantContext`.
6. **Global scope** (safety net only).

**Automated tests:**
- **Cross-tenant test harness:** for every route under `/api/v1/businesses/{business}`, a dataset-driven Pest test signs in as a member of Business A and calls the route with Business B's IDs. It expects 404 and no side effects.
- **Route coverage test:** a meta-test enumerates the tenant routes from the router and **fails if any tenant route isn't registered in the harness**, so new endpoints can't skip isolation testing.
- **Database constraint tests:** inserting a cross-tenant composite FK fails at the database level.
- **Arch tests:** tenant models must use `BelongsToTenant`, controllers must not call `withoutGlobalScopes()`, and nothing outside `app/Admin` may use admin-wide query classes.

**Consequences.** Slightly more schema work (composite keys) and test scaffolding. In exchange, isolation holds even if one layer is wrong.

---

## ADR-018: Booking concurrency and temporary reservation holds

**Refines** ADR-007.

**Decision.**
- **Holds.**
  - When a customer selects a slot, `POST /api/v1/businesses/{business}/booking-holds` creates a `booking_holds` row with an `expires_at` of 10 minutes (configurable per business within platform limits).
  - Holds count as busy time in availability for other customers until they expire.
  - Each customer can have at most 3 active holds per business, to prevent hold squatting.
  - Confirming converts the hold into a booking in one transaction. Expired holds can't be confirmed (409 `booking.hold_expired`).
- **Locking protocol** (create hold, confirm, reschedule):
  1. Start a transaction.
  2. Lock the affected `resource_day_locks` rows `FOR UPDATE`, **sorted by (resource_id, local_date)** to prevent deadlocks when several resources are involved.
  3. Re-check for overlap against confirmed bookings and active holds.
  4. Write the booking or hold.
  5. Commit.
  - Deadlocks and lock timeouts are retried up to 3 times (`DB::transaction(..., attempts: 3)`).
- **Group capacity:** `UPDATE sessions SET booked_count = booked_count + :n WHERE id = :id AND booked_count + :n <= capacity`. Zero affected rows means 409 `booking.session_full`.
- **Idempotency:** `Idempotency-Key` is required on hold creation, confirmation and reschedule (API conventions).
- **Expiry:** a scheduled job deletes expired holds every minute. Availability queries also filter on `expires_at > now()`, so correctness doesn't depend on the job.
- **Tests:**
  - Parallel-request tests (N concurrent holds for one slot → exactly one succeeds).
  - Deadlock-ordering test with multiple resources.
  - Expiry boundary tests.
  - DST-boundary availability tests (`Africa/Cairo`).

**Consequences.** Short lock scopes (one resource-day) keep contention low, and the holds make the booking flow feel reliable on mobile networks.

---

## ADR-019: Platform admin identity and authentication

> CORS is strict by default with no allowed origins until hosting (U5) is decided; see ADR-021. The admin cookie flow itself is unchanged and still M2/M9 work. Sanctum's `/sanctum/csrf-cookie` route is temporarily disabled and the `sanctum` guard is restricted to the `users` provider (2026-11-12); see ADR-021, "Also applied (B2-M1, B2-L1)", for how the route is re-enabled when this cookie flow is built.

**Refines** ADR-004/005.

**Decision.**
- **Separate identities.** There's a separate `admins` table, `Admin` model and `admin` guard (session driver), served on its own subdomain (e.g. `admin.<domain>`). Admins log in to the dashboard with **Sanctum SPA cookie auth** (CSRF protected). They don't use bearer tokens.
- **No self-registration.** Admins are created by an artisan command or by a super-admin, and every creation is audited.
- **MFA.** TOTP MFA is mandatory, with recovery codes. Login without completed MFA gets no admin session.
- **Short sessions.** Session lifetime is short (e.g. 30 minutes idle, 8 hours absolute). Re-authentication is required for sensitive actions (suspending a business, impersonation).
- **Guard separation is tested.**
  - User routes accept only `User` tokenables. The `sanctum` guard's provider is explicitly the `users` provider, and tests assert that an `Admin` credential or token is rejected on `/api/v1/*`.
  - Admin routes (`/api/admin/v1/*`) require the `admin` guard plus `EnsureAdmin` middleware. Tests assert that a `User` token is rejected there.
- **Admin roles.** Admins have their own roles (e.g. `super_admin`, `support`, `finance`), separate from tenant roles.
- **Impersonation (if built).** Read-only by default, time-limited, reason required, and audited at start and end.

**Consequences.** Two auth stacks to maintain. In return, a compromised customer account can't escalate into platform administration.

---

## ADR-020: API contracts and generated TypeScript types

**Refines** ADR-008.

**Decision.**
- **Spec generation.** The backend generates an OpenAPI 3.1 document from code. The primary candidate is **Scramble** (`dedoc/scramble`), which infers schemas from FormRequests and API Resources. Laravel 13 compatibility is verified when it's installed in M2, together with the first real endpoints. If it isn't compatible, an alternative gets chosen in a new ADR.
- **Committed spec.** The generated spec is committed at `packages/api-types/openapi/v1.json` and `admin-v1.json`.
- **Generated types.** TypeScript types are generated with **`openapi-typescript`** into `packages/api-types/src/generated/` and exported from `@nobta/api-types`. Clients use them through a thin typed fetch wrapper; a client library is chosen in M2.
- **CI drift check.** CI regenerates the spec and types, then runs `git diff --exit-code`. Any change that wasn't committed fails the build.
- **Contract tests.** Feature tests assert response shapes against the spec for key endpoints.
- **Breaking-change detection.** An OpenAPI diff check (tool to be chosen in M2) fails PRs that introduce breaking changes to `v1`.

**Consequences.** Contracts are reviewable in PR diffs. Clients get compile-time errors when the API changes.

---

## ADR-021: Pre-M2 backend hardening decisions (D1–D11)

**Status.** Accepted (approved 2026-11-11). This is a separate hardening step before M1 final sign-off. It is **not** M2 and does **not** constitute M1 final sign-off.

**Context.** A read-only backend review found defaults that conflict with existing ADRs or would become risks once M2 adds authentication. The fixes were approved as P1–P7 together with these decisions.

**Decision.**
- **D1, Sanctum backstop.** `config('sanctum.expiration')` = 30 minutes (`SANCTUM_EXPIRATION`). Sanctum rejects a token once it is older than this **or** past its own `expires_at`, so ADR-016's 30-minute access tokens are unchanged and no token can outlive 30 minutes. Refresh tokens are a custom table (ADR-016) and are not affected.
- **D2, Collation.** The MySQL application connection uses `utf8mb4_0900_ai_ci`. Existing tables (all created as `utf8mb4_unicode_ci` in `nobta` and `nobta_testing`) are **not** rebuilt; new tables get the new collation.
- **D3, Time.** Instants in **domain tables** are UTC `DATETIME`. Laravel framework/internal tables (cache, jobs, failed_jobs, sessions, password_reset_tokens, personal_access_tokens, and the scaffold `users` columns) keep their framework types. The MySQL session time zone is forced to `+00:00` for the application connection.
- **D4, Migrations.** Schema changes are made in **new** migrations; existing migrations are not edited.
- **D5, Test database.** Feature tests run only on MySQL `nobta_testing` on a local host. `Tests\Support\TestDatabaseGuard` rejects SQLite in every form and any other database, using the effective connection settings including `DB_URL`, before any testing trait runs. `phpunit.xml` forces `DB_CONNECTION`, `DB_DATABASE` and `DB_URL` (both `<env force>` and `<server>`).
  - *Clarified 2026-11-12 (F5):* every effective connection configuration must have driver exactly `mysql`, database exactly `nobta_testing` **and** host exactly `127.0.0.1`, `localhost` or `::1`. The rules apply after `DB_URL` is parsed, so a remote `DB_URL` host is refused even if `DB_HOST` is local. Every `read`/`write` section and every entry of a host list is checked; missing, empty, remote or non-allowlisted hosts are refused.
  - *Limitations:* a loopback port forward or SSH tunnel can still reach a remote database; `DB_SOCKET` is not validated on its own; `::1` is accepted but PDO connectivity over it is unverified; the read-replica check reads Laravel's internal `readPdoConfig` via reflection (may need maintenance after upgrades); connections resolved before the `TestCase` listener is registered are not checked.
  - CI sets `APP_ENV=testing` and runs `php artisan migrate --force` against its ephemeral MySQL service before Pest (not yet run on GitHub).
- **D6, Rate-limit numbers.** **Deferred.** No numeric thresholds are defined or implemented yet (API_CONVENTIONS §9 names the limiters only).
- **D7, `/api/user`.** Kept temporarily. It is replaced by `/api/v1/me` in M2.
- **D8, CORS.** Strict: `allowed_origins` comes from `CORS_ALLOWED_ORIGINS` and is empty by default; credentials are disabled. Real origins are chosen with the hosting topology (U5).
- **D9, Documentation.** Only documents relevant to these decisions are aligned.
- **D10, MySQL.** Laragon MySQL may be started for approved tests; no destructive database operations.
- **D11, Scope.** This hardening is separate from M2.

**Also applied (P2, P6).** API requests (`/api/*`) always get JSON errors and are never redirected to a login page, so an unauthenticated API request returns 401 even without `Accept: application/json`. The private `local` disk is not served over HTTP (`serve` = false).

**Also applied (B2-M1, B2-L1), 2026-11-12.** Found in human review Batch 2.
- **Stateless web root.** `GET /` runs without the `web` middleware group (`->withoutMiddleware('web')`), so it starts no session and issues no session/XSRF cookies. With the `database` session driver (ADR-009), every anonymous request to it previously inserted a row into `sessions`. The JSON response and status are unchanged; `/up` is unchanged.
- **Sanctum CSRF route temporarily disabled.** `config/sanctum.php` sets `'routes' => false`, so `GET /sanctum/csrf-cookie` (registered by Sanctum in the `web` group, and also writing an anonymous session row) is not registered. The app currently authenticates only with **Bearer tokens**; the `sanctum` guard and personal access tokens are unaffected and keep working.
- **Re-enabling cookie authentication.** When the Admin cookie-based authentication of ADR-019 is built, remove the key (or set it to `true`) together with `statefulApi()`, the stateful domains, CORS credentials (ADR-021 D8) and the session domain.
- **Config and route cache (deployment).** Both changes depend on configuration values (`sanctum.routes`, `auth.guards.sanctum.provider`) that are stored in the config cache, and Sanctum skips route registration when routes are cached. Deploy them with, in this order: `php artisan config:clear`, `php artisan route:clear`, `php artisan config:cache`, `php artisan route:cache`.
  - Clearing the config cache is required so the new `sanctum.routes` and `auth.guards.sanctum.provider` values are loaded; a stale config cache keeps the old route registration and the old `null` provider.
  - Clearing the route cache is required to remove any cached Sanctum routes.
  - Build the config cache **before** the route cache, because route registration reads `sanctum.routes`.
  - If an environment does not use a route cache, do not create one just for this fix.
  - Do not build these caches on development machines: a cached config ignores `.env` changes and the `phpunit.xml` overrides, so tests would see the development database settings (which the test-database guard would refuse).
  - After deployment verify: `php artisan route:list --name=sanctum` shows no `sanctum.csrf-cookie`; `php artisan config:show auth.guards.sanctum` shows `provider` = `users`; `php artisan config:show sanctum.routes` shows `false`. This procedure was run once locally with these three results, and the local caches were removed afterwards.
- **Upgrade review.** The `routes` key is honoured by `SanctumServiceProvider::defineRoutes()` in Sanctum 4.3.3 but is not part of Sanctum's published configuration or the official Laravel 13 Sanctum documentation. Re-verify it on every Laravel or Sanctum upgrade (a feature test asserts the route is absent).
- **Sanctum provider restricted.** `config/auth.php` defines the `sanctum` guard explicitly with `provider => users`, as ADR-019 requires. Sanctum's default provider is `null`, which accepts a token belonging to any model using `HasApiTokens`. Proving that a token owned by another model is rejected needs a database-backed test (a later phase); currently only the configuration is asserted.

**Consequences.** Safer defaults before any authentication endpoint exists. Existing dev/test tables keep `utf8mb4_unicode_ci` until a separately approved rebuild.
