# NOBTA

NOBTA is a multi-industry SaaS for bookings and running a business. It covers Beauty & Salons, Clinics (appointments only), Fitness, Education, Home Services and Professional Services.

| Surface | Users | Stack | Location |
|---|---|---|---|
| Mobile app (one app, role-based) | Customer, Business Owner, Employee | React Native + Expo + TypeScript | [apps/mobile/](apps/mobile/) |
| Admin dashboard | Platform Admin | React + TypeScript (Vite) | [apps/admin/](apps/admin/) |
| API | all clients | Laravel (PHP 8.3) + MySQL 8.4, REST `/api/v1` (planned for M2; currently only the temporary `/api/user`, ADR-021 D7) | [backend/](backend/) |
| Shared API types | mobile, admin | TypeScript (to be generated from OpenAPI; generator planned for M2) | [packages/api-types/](packages/api-types/) |
| Shared tooling config | all TS projects | tsconfig preset (lint/format presets in M2) | [packages/shared-config/](packages/shared-config/) |
| Infrastructure | — | local dev, CI, deployment | [infrastructure/](infrastructure/) |

> **Status: M1 (scaffolding and foundations).** All three apps are scaffolded with tooling, tests and CI config. Product features start in M2. See [docs/architecture/ROADMAP.md](docs/architecture/ROADMAP.md).

## Common commands

```bash
# JS workspaces (from the repo root)
npm install
npm run check            # prettier check + lint + typecheck + tests for all workspaces
npm run start -w @nobta/mobile     # Metro for the installed EAS development build (see note below)
npm run android -w @nobta/mobile   # local Gradle build: currently BLOCKED on Windows (ROADMAP U16)
npm run dev -w @nobta/admin        # admin dashboard (Vite)

# Backend (from backend/, using Laragon's PHP/Composer)
composer lint            # Pint (check only)
composer analyse         # Larastan level 8
composer test            # Pest (MySQL nobta_testing)
php artisan serve
```

**Android on Windows:** local Gradle builds (`npm run android`) are currently blocked by the ninja path-length limit (ROADMAP U16). The verified workflow is to install the existing EAS development build APK on the emulator (`adb install -r <apk>`), then start Metro with `npm run start -w @nobta/mobile` and open the app. A new EAS build is only needed when native code or native configuration changes. See [infrastructure/README.md](infrastructure/README.md#android-emulator).

## Documentation

- [Product overview](docs/product/PRODUCT_OVERVIEW.md)
- [System architecture](docs/architecture/SYSTEM_ARCHITECTURE.md)
- [Architecture decisions (ADRs)](docs/architecture/ARCHITECTURE_DECISIONS.md)
- [Database strategy](docs/database/DATABASE_STRATEGY.md)
- [API conventions](docs/api/API_CONVENTIONS.md)
- [Design guidelines](docs/design/DESIGN_GUIDELINES.md)
- [Roadmap](docs/architecture/ROADMAP.md)

## Repository layout

```
NOBTA/
├── apps/
│   ├── mobile/          # Expo app (Customer / Owner / Employee)
│   └── admin/           # Platform admin web dashboard
├── backend/             # Laravel API (modular monolith)
├── packages/
│   ├── api-types/       # TS types to be generated from the OpenAPI spec (M2)
│   └── shared-config/   # tsconfig preset (ESLint/Prettier presets in M2)
├── infrastructure/      # local dev, CI, deployment notes/config
└── docs/                # product, architecture, database, api, design
```

JavaScript workspaces are managed with **npm workspaces** (root `package.json`). The Laravel backend is a separate Composer project and is not an npm workspace.

## Local prerequisites (verified on the dev machine in M0)

| Tool | Required | Verified |
|---|---|---|
| PHP | ^8.3 (Laravel 13) | 8.3.30 (Laragon) |
| Composer | 2.x | 2.9.4 (Laragon) |
| MySQL | 8.4 LTS | 8.4.3 (Laragon, running on :3306) |
| Node.js | ^22.13 or ^24.3 (Expo SDK 58) | 24.13.1 |
| npm | 10+ | 11.8.0 |
| JDK | 17 (React Native Android) | Microsoft OpenJDK 17.0.19 |
| Android SDK | platform 35/36, build-tools 35+ | present at `%LOCALAPPDATA%\Android\Sdk` |

See [infrastructure/README.md](infrastructure/README.md) for known PATH caveats on Windows.

## Engineering rules (summary)

- Strict TypeScript everywhere; no `any` without a written justification.
- Follow Laravel conventions and SOLID principles. Domain logic goes in domain modules, not controllers.
- Tenant isolation is designed in from day one. **A client-supplied business/tenant ID is never trusted without an authorization check.**
- There is one booking engine. Industries configure it and never fork it.
- Clinics get appointments only. **No medical records, diagnoses or clinical notes.**
- **Never store payment card data.** Use the payment provider's tokenization or hosted checkout.
- Every feature ships with automated tests, and tenant-isolation tests are mandatory.
- Platform admins and tenant users use separate identities, guards and API surfaces.
- This is a modular monolith. No microservices until a measured need exists.
- No dependency gets added without a stated reason.
