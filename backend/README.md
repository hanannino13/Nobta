# backend: NOBTA API

This is the Laravel API: a modular monolith that will serve the mobile app (`/api/v1`, planned for M2) and the admin dashboard (`/api/admin/v1`, planned for M9). Neither surface exists yet; the only current API route is the temporary `GET /api/user`, kept until `/api/v1/me` replaces it (ADR-021 D7).

**Status:** scaffolded in M1 with `composer create-project laravel/laravel backend "^13.0"`. It runs Laravel 13.40.0 on PHP 8.3.30, as an API-only app (the skeleton's Vite/Tailwind front-end tooling was removed).

Tooling: Sanctum 4.3.3 (`php artisan install:api`), Pest 4.7.8 (Pest 5 needs PHP 8.4), Pint 1.32.1, Larastan 3.14.0 at level 8.

| Command | Purpose |
|---|---|
| `composer lint` / `composer format` | Pint check / fix |
| `composer analyse` | Larastan (PHPStan level 8). `config/` is excluded because it's framework boilerplate full of untyped `env()` calls |
| `composer test` | Pest: Unit, Feature, Arch suites |

Database: `.env` uses MySQL `nobta` with the non-root `nobta` user. `phpunit.xml` points tests at `nobta_testing` and forces `DB_CONNECTION`, `DB_DATABASE` and `DB_URL`. Tests require **MySQL, the exact database `nobta_testing`, and a local host**. `tests/TestCase.php` enforces this with `Tests\Support\TestDatabaseGuard`. Every effective connection configuration must satisfy all three rules: driver exactly `mysql`, database exactly `nobta_testing`, and host exactly `127.0.0.1`, `localhost` or `::1`.

- the default connection is checked before any testing trait runs (e.g. `DatabaseTransactions`);
- **every database connection a test resolves** is checked when Laravel creates it, including named connections, a default changed at runtime, and `DB_URL` overrides, before any query or file access;
- the rules apply after Laravel parses `DB_URL`, so a remote host given in `DB_URL` is refused even if `DB_HOST` is local;
- every configured `read` and `write` section is checked, and every entry of a host list must be allowed;
- SQLite (file-backed or `:memory:`), any other database, and any missing, empty, remote or non-allowlisted host are refused.

Limitations: a port forward or SSH tunnel listening on an allowed loopback host can still lead to a remote database; `DB_SOCKET` is not validated on its own (an allowed host is still required); `::1` is accepted but PDO connectivity over it has not been verified here; the read-replica check reads Laravel's internal `readPdoConfig` via reflection, so it may need maintenance after framework upgrades; and only connections resolved after `TestCase` registers its listener are checked (a named connection resolved earlier, during application boot, is not).

Tests run inside rolled-back transactions against the existing schema; they do not create or migrate it. Before the first local run, migrate `nobta_testing` once. CI sets `APP_ENV=testing` explicitly and runs `php artisan migrate --force` against its own ephemeral MySQL service before Pest (the workflow has not run on GitHub yet).

Planned module layout (see [SYSTEM_ARCHITECTURE.md](../docs/architecture/SYSTEM_ARCHITECTURE.md)):

```
app/
├── Http/                 # thin controllers, form requests, API resources, middleware
├── Domain/
│   ├── Identity/         # users, customer profiles, authentication
│   ├── Tenancy/          # businesses, branches, memberships, tenant context
│   ├── Industry/         # industry templates & configuration
│   ├── Catalog/          # services, resources, pricing
│   ├── Scheduling/       # working hours, time off, availability
│   ├── Booking/          # booking engine, state machine, holds
│   ├── Notifications/    # channels, templates, reminders
│   ├── Billing/          # payment-provider integration (no card data)
│   └── Audit/            # audit log
└── Admin/                # platform admin features (separate guard)
```

Tests: Pest (PHPUnit-compatible), run against a dedicated MySQL test database, with no SQLite for integration tests. See [DATABASE_STRATEGY.md](../docs/database/DATABASE_STRATEGY.md).
