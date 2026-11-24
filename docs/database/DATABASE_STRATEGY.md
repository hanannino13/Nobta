# NOBTA: Database Strategy

Engine: **MySQL 8.4 LTS** (8.4.3 verified locally). Server defaults verified: `utf8mb4` / `utf8mb4_0900_ai_ci`, strict `sql_mode` (`STRICT_TRANS_TABLES`, `ONLY_FULL_GROUP_BY`, `NO_ZERO_DATE`, …). Production must use the same major version and the same strict mode.

Tenancy model: single database, shared schema, `business_id` column. See ADR-003.

## 1. Conventions

| Topic | Rule |
|---|---|
| Naming | Laravel defaults: plural snake_case tables, `snake_case` columns, `{singular}_id` foreign keys |
| Primary keys | `BIGINT UNSIGNED` auto-increment internally. **Public identifiers are ULIDs** (`ulid` column, unique) exposed in the API, so IDs can't be enumerated |
| Tenant column | `business_id BIGINT UNSIGNED NOT NULL` on every tenant-owned table, with an FK to `businesses`, and as the **leading column of tenant composite indexes** |
| Timestamps | `created_at`, `updated_at`; `deleted_at` (soft deletes) only where the domain needs restore or history |
| Time | Instants in **domain tables** are stored in **UTC** as `DATETIME` (not `TIMESTAMP`, which has a 2038 limit and session-timezone conversion). Laravel framework/internal tables (cache, jobs, failed_jobs, sessions, password_reset_tokens, personal_access_tokens, scaffold `users` columns) keep their framework types (ADR-021 D3). The app connection's session time zone is forced to `+00:00` (`config/database.php`), because the server default is the host time zone (Egypt, with DST). Branches store an IANA `timezone` string |
| Money | `amount_minor BIGINT` + `currency CHAR(3)`. Precision comes from the currency (KWD 3, USD 2). **Never FLOAT/DOUBLE** |
| Translatable text | `JSON` column `{"en": "...", "ar": "..."}` |
| Enums | `VARCHAR` + PHP backed enums, not MySQL `ENUM`, so changes don't need table rebuilds |
| Booleans | `TINYINT(1)` via Laravel `boolean()` |
| Charset | `utf8mb4` everywhere (Arabic text and emoji) |
| Collation | `utf8mb4_0900_ai_ci` for the application connection and new tables (ADR-021 D2). Tables created before 2026-11-11 in `nobta` / `nobta_testing` are `utf8mb4_unicode_ci` and are not rebuilt without separate approval |

## 2. Core entities (initial outline; detailed design in M2–M5)

**Platform scope (no `business_id`)**
- `admins`: platform admin identities (separate guard)
- `users`: all mobile users. Includes `locale`, `phone` (E.164), `email`, verification timestamps
- `industries`, `industry_templates` (versioned JSON config)
- `personal_access_tokens` (Sanctum; user API tokens. Admins use Sanctum SPA cookie sessions, not bearer tokens, per ADR-019)
- `device_push_tokens` (user_id, platform, expo token)

**Tenant scope (`business_id`)**
- `businesses` is the tenant root, so its own row has no `business_id`. It has `owner_user_id`, `industry_id`, `status`, `default_currency`, `default_locale`
- `business_settings` (copied from the industry template, then overridden)
- `branches` (timezone, address, geo point, status)
- `business_memberships` (user_id, role, status), `membership_branches` (branch scope)
- `services`, `service_branch` (availability per branch), `resources` (staff/room/equipment), `resource_service`
- `working_hours`, `schedule_exceptions` (time off, holidays)
- `business_customers` (a tenant-local customer record: notes allowed by policy, tags, the customer's stats in that business)
- `bookings`, `booking_items`, `booking_holds`, `booking_status_history`
- `audit_logs` (`business_id` nullable for platform actions)

## 3. Isolation rules at the data layer

1. Tenant models use the `BelongsToTenant` trait (global scope plus write guard).
2. **Foreign keys within a tenant must stay inside the same tenant.** For example, `booking_items.resource_id` must belong to the same `business_id` as the booking. This is enforced in the domain layer (always resolve via `$business->resources()`). Composite FKs `(business_id, id)` get evaluated for the highest-risk relations in M2.
3. Unique constraints are tenant-scoped where they apply, e.g. `UNIQUE(business_id, slug)`.
4. Raw queries and the query builder (which bypass Eloquent scopes) must include `business_id` explicitly, and code review flags any `DB::table()` on tenant tables.
5. Platform admin cross-tenant reads go through dedicated admin query classes, never by disabling scopes in tenant code.

## 4. Booking integrity

- Overlap prevention: inside one transaction, lock the resource's day row (`resource_day_locks`, keyed by `(resource_id, local_date)`), re-check overlap against `booking_items` with an index on `(business_id, resource_id, starts_at, ends_at)`, then insert. MySQL has no exclusion constraints, so we rely on the lock plus the check, and verify that with concurrency tests.
- Group capacity: `sessions.booked_count` is updated under a lock with `booked_count < capacity` checked.
- Holds expire via the scheduler, and expired holds are ignored by availability even before cleanup runs.
- Idempotency: `idempotency_keys (key, user_id, request_hash, response, expires_at)` for booking creation.

## 5. Migrations

- Laravel migrations only, and every one must be reversible (`down()` implemented) until launch. After launch, prefer forward-fix migrations.
- Schema changes go into **new** migrations; existing migrations (including the Laravel scaffold ones) are not edited (ADR-021 D4).
- No destructive change in a single step after launch: expand → migrate data → contract.
- Seeders: `IndustrySeeder` (planned for M3, not created yet: reference data, idempotent, runs in all environments) and demo or factory seeders (local and testing only). `DatabaseSeeder` calls `DemoSeeder` only in `local`/`testing`, and `DemoSeeder` throws if invoked directly anywhere else.

## 6. Testing databases

- Feature tests run on **MySQL** (`nobta_testing`), not SQLite, because isolation, locking, JSON and strict-mode behaviour must match production.
- Enforced by `Tests\Support\TestDatabaseGuard` before any testing trait runs: SQLite (file or `:memory:`) and any database other than `nobta_testing` are refused, using the effective connection settings including `DB_URL` (ADR-021 D5). Every effective connection configuration must have driver exactly `mysql`, database exactly `nobta_testing` and host exactly `127.0.0.1`, `localhost` or `::1`. The rules apply after `DB_URL` is parsed (a remote `DB_URL` host is refused even if `DB_HOST` is local), every `read`/`write` section and every entry of a host list is checked, and missing, empty, remote or non-allowlisted hosts are refused.
- Guard limitations: a loopback port forward or SSH tunnel can still reach a remote database; `DB_SOCKET` is not validated on its own (an allowed host is still required); `::1` is accepted but PDO connectivity over it is unverified; the read-replica check reads Laravel's internal `readPdoConfig` via reflection and may need maintenance after framework upgrades; connections resolved before `TestCase` registers its listener (during application boot) are not checked.
- Database-backed tests use `DatabaseTransactions`: each test runs inside a transaction that is rolled back, against the **existing** `nobta_testing` schema. Tests never create, migrate, reset or rebuild the schema, and no test uses `RefreshDatabase`, `LazilyRefreshDatabase` or `DatabaseMigrations`.
- The schema must exist before tests run: CI sets `APP_ENV=testing` explicitly and runs `php artisan migrate --force` against its ephemeral MySQL `nobta_testing` service before Pest (the workflow has not run on GitHub yet); locally, `nobta_testing` is migrated once (it currently has all four migrations applied).
- Factories for every model, and tenant-aware factories that always create inside a business.
- Required test categories: cross-tenant access (expect 404), tenant write-guard (expect exception), concurrent booking (expect exactly one success).

## 7. Data protection and retention (to be finalized before launch)

- PII: name, phone and email. No health data (ADR-011), and no card data (ADR-011).
- Account deletion: anonymize customer PII on bookings and keep aggregate business records.
- Backups: daily automated backups with point-in-time recovery in production, and a restore drill before launch.
- Audit log retention period: to be decided (see roadmap).
