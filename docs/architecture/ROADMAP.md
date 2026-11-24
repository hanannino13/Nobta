# NOBTA: Roadmap

Milestones are sequential unless noted. Each milestone ends with: tests green in CI, docs updated, and ADRs added or updated where decisions changed.

| ID | Milestone | Scope | Exit criteria |
|---|---|---|---|
| **M0** | Project initialization | Environment audit, repo structure, architecture docs, ADRs | Done (this milestone) |
| **M1** | Scaffolding and engineering foundations (Implementation **COMPLETE** · Validation **PARTIALLY COMPLETE**, CI unverified · Final sign-off **PENDING**; see the M1 status below) | Laravel 13 app in `backend/`; Expo SDK 58 app in `apps/mobile/`; Vite React-TS app in `apps/admin/`; strict TS configs extending `@nobta/shared-config`; ESLint/Prettier, Pint/PHPStan (Larastan); Pest; Jest/Vitest; CI pipeline; local MySQL DBs and a non-root user; NOBTA Android emulator. *Six items originally planned for M1 were moved to M2 on 2026-11-11 (see "Moved from M1 to M2" below)* | Each app boots locally; `lint`, `typecheck` and `test` pass for each project in CI; Android dev build runs on an emulator |
| **M2** | Identity and tenancy | Users, admins (separate guard), Sanctum tokens and refresh, businesses, branches, memberships, `ResolveTenant`, `TenantContext`, `BelongsToTenant`, permission enum and policies, audit logger. **Moved from M1:** OpenAPI generator (ADR-020), mobile i18n library (ADR-012), admin router, design token package, RTL lint rules, shared ESLint/Prettier presets | Cross-tenant isolation test suite passes; admin/user guard separation tested |
| **M3** | Industry configuration and catalog | Industries and templates (seeded), business settings, services, resources, staff-service assignments, translatable fields | All six industries can be created with correct defaults; no `if industry ==` outside templates |
| **M4** | Scheduling and availability | Working hours, exceptions, timezones, availability computation | Unit tests including DST and cross-midnight cases; performance budget for availability queries |
| **M5** | Booking engine | Holds, create/reschedule/cancel, state machine, idempotency, concurrency control, policy snapshots, domain events | Concurrent booking test proves no double-booking; full state-transition coverage |
| **M6** | Mobile: customer experience | Auth, discover, business page, booking flow, my bookings, AR/EN and RTL | Usability pass in both languages; component and screen tests |
| **M7** | Mobile: owner and employee experience | Context switcher, Today and calendar, manual bookings, manage catalog, staff and branches | Permission-gated UI matches server policies |
| **M8** | Notifications and jobs | Push (Expo), email, in-app inbox, reminders, scheduler, queue monitoring | Reminder timing tested across timezones; jobs are idempotent |
| **M9** | Admin dashboard | Admin auth (+MFA), businesses, users, industries and templates, audit log viewer | Admin cannot reach tenant APIs and vice versa (tested) |
| **M10** | Payments (optional for MVP) | Provider integration via hosted checkout/tokenization, deposits, refunds | No card data stored (verified by review and schema); webhook signature tests |
| **M11** | Hardening and launch | Security review, load tests, backups and restore drill, observability, store submissions, legal (ToS, privacy) | Launch checklist complete |

## Unresolved decisions (need product-owner input)

| # | Topic | Why it matters | Needed by |
|---|---|---|---|
| U1 | **Target market(s) and launch country**. *Proposed: Egypt (ADR-014), confirm before provider integration* | Currency, payment and SMS providers, data residency, week start, legal | M2 |
| U2 | **Payment provider** and whether MVP takes payments at all | Scope of M10, deposits and no-show fees | M5 |
| U3 | **Customer login method**. *Proposed: phone OTP primary, email optional (ADR-015); OTP/SMS provider not yet chosen* | Auth flow and SMS provider cost | M2 |
| ~~U4~~ | ~~Admin dashboard approach~~. **Resolved:** custom React + TS + Vite, no react-admin | — | — |
| U5 | **Deployment topology and hosting** (domains, cloud provider) | Whether the admin Sanctum SPA cookie flow proposed in ADR-019 (ADR status: Proposed) is viable (it needs the admin and the API under a shared parent domain), CORS origins, data residency | M1/M9 |
| U6 | **Monetization**: subscription plans per business, per branch or per staff; commission? | Billing module, plan limits enforced by API | M3 |
| U7 | **Brand identity**: name usage, logo, palette, Arabic and Latin typefaces | Design tokens and component library | M1–M2 |
| U8 | Digits in Arabic UI: Western (0-9) vs Arabic-Indic (٠-٩) | Formatting utilities | M6 |
| U9 | Custom roles for businesses beyond Owner/Manager/Staff | Whether ADR-005 needs superseding | M7 |
| U10 | Audit log and booking data retention periods | Retention jobs, privacy policy | M11 |
| ~~U11~~ | ~~Expo build strategy~~. **Resolved:** EAS cloud builds for dev distribution and production. On the emulator, the **EAS development build is installed locally** (APK + Metro, see Stage H evidence); this is not a local Gradle build, which remains blocked on Windows (U16). Project linked to the personal Expo account `hanannino13` as `@hanannino13/nobta` (2026-11-11), on the EAS Free plan | — | — |
| U12 | Customer app discovery: marketplace (all businesses searchable) vs link-only per business | Discovery API and ranking | M6 |
| U13 | **App identifiers** `com.nobta.app` (Android package / iOS bundle ID) are provisional. They're very hard to change after the first store upload. Approved **for development builds only**. The EAS development keystore and the first build (versionCode 1) are tied to it | Store listings, deep links, EAS credentials | Before the first production or store build |
| U14 | **PHP 8.4 upgrade** (unlocks Pest 5; Laravel 13 supports 8.3+) | Test tooling, runtime parity with production | M2 |
| U15 | **Git hosting / CI provider. CI deferred.** The workflow (`.github/workflows/ci.yml`) is written for GitHub Actions but has **never run**: the repo has 0 commits and no remote. Before the first commit and push, a **human review of all untracked files** is required to confirm no secrets or local-only files are included (automated checks so far: `.env`, `vendor/`, `node_modules/`, `android/`, `dist/` are ignored, and a pattern scan found no DB passwords or app keys, but that doesn't replace human review) | CI verification, M1 final sign-off | M1 final sign-off |
| U16 | **Windows native build toolchain.** Local Android builds need ninja ≥ 1.12 (long-path aware) through a newer SDK CMake, configured for the generated `android/` project via a config plugin. *Current workaround (approved): build the dev client on EAS and install it locally* | Local native builds only (not blocking: EAS path works) | When local native builds are needed |
| U17 | **npm audit: ACCEPTED TEMPORARILY (2026-11-11).** 3 advisories (`glob`, `minimatch`: moderate; `deepmerge`: low) in Expo CLI / Jest / ESLint tooling; `npm audit --omit=dev` reports 22 entries (21 moderate, 1 low), 0 high, 0 critical. Absent from the shipped mobile and admin bundles (verified). Acceptance does not mean the findings are gone or the risk is zero. Conditions: no `npm audit fix` / `--force`; re-review on every Expo patch update, every milestone and before any production release; re-evaluate immediately if a high or critical runtime advisory appears. CI gate `npm audit --omit=dev --audit-level=high` added (not yet run on GitHub). *Re-verified 2026-11-17, acknowledged by the owner 2026-11-18 (D4):* 2 moderate advisories (`glob`, `minimatch`); `deepmerge` no longer reported; `npm audit --omit=dev` 21 entries, all moderate; gate exit 0. The figures above are the 2026-11-11 record. See [DEPENDENCY_AUDIT.md](../security/DEPENDENCY_AUDIT.md) | Release security posture | Re-review at each milestone and before production |
| U18 | **App images.** The icon, adaptive icons, splash and `assets/images` are still Expo template placeholders. *Done 2026-11-11:* the 14 unused template images (`expo-badge*`, `expo-logo`, `logo-glow`, `react-logo*`, `tabIcons/*`, `tutorial-web`) were deleted after a project-wide check found no references. The remaining 9 assets are all referenced by `app.json`. **Still open:** replacing the placeholders, and the EAS project page icon (set from the placeholder) | Branding, store readiness | With brand identity (U7) |

## M1 status

| Track | Status |
|---|---|
| **M1 Implementation** | **COMPLETE.** Stages A–I are implemented (CI is written, not run) |
| **M1 Validation** | **PARTIALLY COMPLETE.** Local checks pass, and the Android dev build was installed and launched on the emulator. *2026-11-19:* the full backend Pest suite passed against MySQL `nobta_testing` (94 tests, 165 assertions, exit 0; authorized local run, not CI; see [M1_SIGNOFF_ASSESSMENT.md](M1_SIGNOFF_ASSESSMENT.md) §15). **CI remains UNVERIFIED** (deferred, see U15) |
| **M1 Final Sign-off** | **PENDING.** Still waiting on U15: GitHub Actions has not run yet. The 130/130 file review is complete and was accepted by the owner as detailed review evidence on 2026-11-18 (D1); the owner's final check of the exact staged file list and the secret-scan summary is still required before the first commit (M1_SIGNOFF_ASSESSMENT A2). U17 is no longer awaiting a decision: it was **ACCEPTED TEMPORARILY** on 2026-11-11, and its CI gate (`npm audit --omit=dev --audit-level=high`) has been added but not yet verified on GitHub |

M1 is **not** signed off. M2 must not start until final sign-off.

| Stage | Result |
|---|---|
| A. Laravel 13 scaffold | Done: Laravel 13.40.0, API-only (front-end Vite/Tailwind tooling removed) |
| B. Sanctum, Pest, Pint, Larastan | Done: Sanctum 4.3.3, Pest 4.7.8, Pint 1.32.1, Larastan 3.14.0 at level 8 |
| C. Expo mobile scaffold | Done: SDK 58 / RN 0.88.1 / React 19.3.0, `expo-doctor` 20/20 |
| D. Admin scaffold | Done: Vite 8 + React 19.3.0 + TS 7 |
| E. Shared TS config | Done: both apps extend `@nobta/shared-config/tsconfig/base.json` |
| F. Lint, format, typecheck, tests | Done: `npm run check` and backend Pint/Larastan/Pest all pass locally. *Re-run 2026-11-19, after the 2026-11-12 backend changes:* the full backend Pest suite on MySQL `nobta_testing` gave 94 passed (165 assertions), exit 0 (local run, not CI) |
| G. Local MySQL | Done: `nobta` and `nobta_testing`, non-root `nobta` user scoped to those two databases |
| H. Android dev build | **Done via EAS cloud build, verified on the emulator (2026-11-11).** See the details below. Local Gradle builds on Windows remain blocked by the ninja path-length limit (U16) |
| I. CI | **Deferred, UNVERIFIED.** Workflow written (`.github/workflows/ci.yml`) but **never executed** (0 commits, no remote). Running it requires human review of the untracked files before the first commit and push (U15) |

### Stage H evidence (EAS build → install → launch)

| Step | Command | Exit | Result |
|---|---|---|---|
| Link project | `npx eas-cli@27.5.0 init --non-interactive --force` | 0 | `@hanannino13/nobta`, project ID `e69bfa5d-fd04-4db7-b8be-5a862c7658c5` |
| Submit (once) | `npx eas-cli@27.5.0 build --platform android --profile development --non-interactive --no-wait` | 0 | Build `bba4ea42-319d-4442-ac3d-f67c76388a5b`. EAS-managed keystore created; `versionCode` initialized to 1 (remote) |
| Track | `npx eas-cli@27.5.0 build:view bba4ea42-…` | 0 | `FINISHED` at 2026-11-11T08:58:13Z, no error, SDK 58.0.0 |
| Verify APK | `aapt2 dump badging`, `apksigner verify` | 0 | `com.nobta.app`, versionName 0.1.0, versionCode 1, targetSdk 36, minSdk 24. Signature verified (V2). 253.5 MB (dev build, all ABIs) |
| Install | `adb -s emulator-5554 install -r <apk>` | 0 | `Success` on `NOBTA_API_35` (API 35, x86_64) |
| Launch (no Metro) | `adb shell am start -W -n com.nobta.app/.MainActivity` | 0 | `Status: ok`, cold start 6.6 s, `DevLauncherActivity`, process alive |
| Launch (Metro) | `adb reverse tcp:8081 tcp:8081`; `am start` with the dev-client URL | 0 | `ReactNativeJS: Running "main"` (Fabric). The NOBTA home screen ("NOBTA / Development build") rendered |
| Logs | `adb logcat` scan | — | 0 fatal exceptions, ANRs, native signals or JS errors. Only non-fatal warnings (`ViewManagerPropertyUpdater`, `SurfaceSyncGroup`) |

Scope of this evidence: it proves the **development** build installs and runs the current JS. It does not cover a production or release build.

### Deferred items (tracked above)

- Confirm the Android package ID before production (U13)
- Windows ninja path-length issue for local native builds (U16)
- Replace template app images (U18)
- PHP 8.4 / Pest 5 when needed (U14)

### Moved from M1 to M2 (approved 2026-11-11)

These six items were promised for M1 in earlier documents but were not implemented. They are **removed from M1 scope**, are **not** required for M1 completion or sign-off, and are now **M2 scope**. Only their timing changed; no technical decision was made or changed.

| Item | Governing document | M2 work |
|---|---|---|
| OpenAPI generator | ADR-020 (primary candidate Scramble; types via `openapi-typescript`) | Choose and install with the first real endpoints; verify Laravel 13 compatibility |
| Mobile i18n library | ADR-012 (candidates `i18next` + `react-i18next` with `expo-localization`) | Choose and install |
| Admin router | `apps/admin/README.md` | Choose and install |
| Design token package | DESIGN_GUIDELINES §2 | Decide the package; token values still depend on brand identity (U7) |
| RTL lint rules | DESIGN_GUIDELINES §3, ADR-012 | Add lint rules against `left`/`right` layout styling in the mobile and admin lint configs |
| Shared ESLint/Prettier presets | `packages/shared-config/README.md` | Add presets to `@nobta/shared-config`. In M1 each app keeps its own lint config (mobile: `eslint-config-expo`; admin: `oxlint`) and Prettier is configured at the repo root |

## Backend hardening before M1 sign-off (P1–P7)

Approved 2026-11-11 (decisions in ADR-021). **This is a separate step: it is not M2 and it is not M1 final sign-off.** The M1 status above is unchanged.

| Item | Change |
|---|---|
| P1 | Test-database guard (`Tests\Support\TestDatabaseGuard`): MySQL `nobta_testing` only, SQLite refused, `DB_URL` resolved; `phpunit.xml` forces the DB variables |
| P2 | `/api/*` guests get JSON 401, never a login redirect, even without `Accept: application/json` |
| P3 | MySQL session time zone forced to `+00:00`; connection collation `utf8mb4_0900_ai_ci` |
| P4 | Sanctum `expiration` backstop = 30 minutes |
| P5 | Demo seeding only in `local`/`testing` (`DemoSeeder`) |
| P6 | Private `local` disk not served over HTTP |
| P7 | Strict CORS (empty origin allowlist, credentials off) |

Open items from this step:

| # | Item | Needed by |
|---|---|---|
| U19 | **Numeric rate limits** (ADR-021 D6): deferred; required before the first auth/OTP endpoint | M2 |
| ~~U20~~ | **RESOLVED (2026-11-11). `RefreshDatabase` on `nobta_testing`.** `DatabaseConnectionTest.php` now uses `DatabaseTransactions` instead of `RefreshDatabase`, so it runs against the existing schema without `migrate:fresh`. The focused test passed (4/4) and the complete Pest suite passed (58/58). No database rebuild, migration, reset, drop or truncation was performed. No current test uses `RefreshDatabase`, `LazilyRefreshDatabase` or `DatabaseMigrations` | — |
| U21 | **Existing table collation:** all tables in `nobta` and `nobta_testing` are still `utf8mb4_unicode_ci` (verified); new tables use `utf8mb4_0900_ai_ci`. A rebuild needs separate approval | Before the first M2 migration |
