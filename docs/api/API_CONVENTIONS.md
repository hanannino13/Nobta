# NOBTA: API Conventions

Applies to every HTTP endpoint the Laravel backend exposes. ADR-004, ADR-005 and ADR-008 are the key decisions behind these conventions.

## 1. Surfaces and versioning

| Prefix | Consumers | Guard |
|---|---|---|
| `/api/v1/auth/*` | mobile | public / `sanctum` |
| `/api/v1/me/*` | mobile, authenticated user as customer | `sanctum` |
| `/api/v1/discover/*` | mobile, public discovery | public (rate-limited) |
| `/api/v1/businesses/{business}/*` | mobile, owner/employee | `sanctum` + `ResolveTenant` + policy |
| `/api/admin/v1/*` | admin dashboard | `admin` |

- The version is in the URI. Changes within a version are **additive only**: new endpoints, new optional fields, and new enum values that clients are told to tolerate.
- Breaking changes go into a new version. The old version returns `Deprecation` and `Sunset` headers during the deprecation window.
- Clients must ignore unknown fields.

## 2. Resource naming

- Plural, kebab-case nouns: `/businesses/{business}/booking-policies`.
- Path IDs are **ULIDs**, never internal auto-increment IDs.
- Actions that aren't plain CRUD use sub-resources with a verb: `POST /bookings/{booking}/cancel`, `POST /bookings/{booking}/check-in`.
- Max nesting is `businesses/{business}/{collection}/{id}`. Deeper relations are filters, e.g. `/bookings?branch=...`.

## 3. Tenant scoping rules

- The tenant comes **only** from the `{business}` route segment, and is authorized against the caller's membership. A `business_id` in a request body is rejected or ignored, never used.
- Every nested ID (`branch`, `service`, `resource`, `booking`) is resolved **through** the business. A valid ID from another business returns **404**.
- Owner and employee responses never include data from other tenants. Customer endpoints return only the caller's own data.

## 4. Request format

- `Content-Type: application/json`, UTF-8.
- JSON fields are `snake_case`. Generated TS types keep `snake_case` (no runtime case conversion).
- Instants are ISO 8601 in UTC with `Z`: `2026-11-05T09:30:00Z`. Local dates and times (working hours) are `YYYY-MM-DD` and `HH:MM`, with the branch's IANA timezone named explicitly.
- Money: `{ "amount_minor": 12500, "currency": "KWD" }`. Clients format it using the currency's precision.
- Translatable fields are written as `{ "en": "...", "ar": "..." }`. On read, the resolved string comes back according to `Accept-Language`, plus a `*_translations` object on management endpoints.

## 5. Headers

| Header | Direction | Purpose |
|---|---|---|
| `Authorization: Bearer <token>` | req | Mobile auth (Sanctum token) |
| `Accept: application/json` | req | Required. Laravel then returns JSON errors |
| `Accept-Language: ar` / `en` | req | Response language (falls back to the user preference, then `en`) |
| `Idempotency-Key: <uuid>` | req | **Required** on booking creation, reschedule and payment-initiating requests. Same key + same body returns the stored response. Same key + different body returns 422 |
| `X-Request-Id` | req/resp | Correlation. Generated if missing and echoed back |
| `X-App-Version`, `X-Platform` | req | Mobile client diagnostics and forced-update checks |

## 6. Response format

**Single resource**
```json
{ "data": { "id": "01JC...", "type": "booking", "status": "confirmed", "starts_at": "2026-11-05T09:30:00Z" } }
```

**Collection (cursor pagination by default)**
```json
{
  "data": [ { "id": "01JC..." } ],
  "meta": { "next_cursor": "eyJ...", "prev_cursor": null, "per_page": 20 }
}
```
- `?cursor=…&per_page=…` (max 100). Offset pagination only for admin tables that need page numbers.
- Filtering uses `?filter[status]=confirmed&filter[branch]=01JC...`. Sorting uses `?sort=-starts_at`. Only allow-listed fields can be used.

## 7. Errors

There's one consistent error shape, based on RFC 9457 (problem details) and compatible with Laravel validation:

```json
{
  "type": "https://docs.nobta.app/errors/slot-unavailable",
  "title": "The selected time slot is no longer available.",
  "status": 409,
  "code": "booking.slot_unavailable",
  "request_id": "a1b2c3",
  "errors": { "starts_at": ["The selected time slot is no longer available."] }
}
```

| Status | When |
|---|---|
| 400 | Malformed request |
| 401 | Not authenticated / token expired. For `/api/*` this is always JSON and never a redirect, even when the client omits `Accept: application/json` (ADR-021) |
| 403 | Authenticated and inside the tenant, but the role lacks permission |
| 404 | Not found **or not in the caller's tenant** (we don't reveal it exists) |
| 409 | Conflict: slot taken, invalid state transition |
| 422 | Validation failed / idempotency key reused with a different body |
| 429 | Rate limited (`Retry-After` header) |
| 5xx | Server error. The body never contains stack traces outside local development |

`code` is a stable, machine-readable identifier that clients switch on. `title` is localized.

*Current state (pre-M2):* errors still use Laravel's default JSON body (`{"message": …}`). The problem-details shape above is implemented in M2.

## 8. Authentication endpoints (outline)

> Aligned 2026-11-11 with ADR-015 (phone OTP, *Proposed*) and ADR-016 (token lifecycle). Exact endpoint shapes are designed in M2.

- **Sign-in:** phone OTP request and verification (ADR-015, *Proposed*); verification returns an **access token (30 minutes) and a refresh token** (ADR-016). Email is optional; email/password login may be enabled per market later. The OTP/SMS provider is not chosen yet.
- `POST /api/v1/auth/token/refresh` rotates the pair inside a transaction; reusing a used refresh token revokes the whole token family (ADR-016)
- `POST /api/v1/auth/logout` revokes the current device's token family
- `GET /api/v1/me` returns the profile plus **memberships** (business, role, branch scope, permissions), which drive the app's context switcher
- Sanctum's global `expiration` is a 30-minute backstop (ADR-021 D1). The temporary `GET /api/user` scaffold route is kept until `/api/v1/me` exists (ADR-021 D7)

## 9. Rate limiting

Named limiters: `auth` (strict, per IP + identifier), `public-discovery`, `authenticated` (per user), `admin`. Booking creation gets its own limiter per user.

OTP abuse controls are in ADR-015 (per phone, IP and device; resend cooldown with exponential backoff; daily cap per phone; country allowlist). The only numbers decided so far are OTP verification limits: 5-minute code expiry and at most 5 attempts per code.

**Numeric thresholds for all limiters are not decided yet and are deferred (ADR-021 D6).** No API rate limiting is active until they are approved; it must be in place before the first auth or OTP endpoint ships.

## 10. Documentation and contract

- The OpenAPI 3.1 spec will be generated from routes, FormRequests and API Resources. Generation is planned for M2, when the generator is chosen (ADR-020); it is not implemented yet.
- `packages/api-types` will be regenerated from the spec, and CI will fail on drift (M2, ADR-020). Not implemented yet: the package is currently an empty placeholder and CI has no drift check.
- Every endpoint has feature tests covering 401, 403/404 (cross-tenant), 422 validation and the happy path.
