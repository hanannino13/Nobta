<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        // Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    // NOBTA (ADR-016): access tokens live 30 minutes via their own `expires_at`. This global
    // backstop also rejects any token older than SANCTUM_EXPIRATION minutes, whatever its expires_at.
    'expiration' => (int) env('SANCTUM_EXPIRATION', 30),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Routes (NOBTA: temporarily disabled, ADR-021 / B2-M1)
    |--------------------------------------------------------------------------
    |
    | NOBTA currently authenticates only with Bearer tokens (personal access
    | tokens), so the cookie-based SPA endpoint GET /sanctum/csrf-cookie is not
    | needed. It runs in the `web` group and would write an anonymous row to the
    | database `sessions` table on every call, so it is not registered.
    | The `sanctum` guard and personal access tokens are NOT affected.
    |
    | Re-enable (remove this key or set it to true) only when the Admin
    | cookie-based authentication of ADR-019 is built, together with
    | statefulApi(), the stateful domains, CORS credentials and the session
    | domain.
    |
    | Deployment (this key and auth.guards.sanctum.provider live in the
    | config cache): run, in this order,
    |   php artisan config:clear
    |   php artisan route:clear
    |   php artisan config:cache
    |   php artisan route:cache
    | Clearing the config cache loads the new `sanctum.routes` and
    | `auth.guards.sanctum.provider` values; clearing the route cache removes
    | any cached Sanctum routes (SanctumServiceProvider skips registration when
    | routes are cached). Build the config cache before the route cache. If an
    | environment does not use a route cache, do not create one just for this.
    | Do not build these caches on development machines. After deployment,
    | verify: `php artisan route:list --name=sanctum` lists no
    | sanctum.csrf-cookie, `php artisan config:show auth.guards.sanctum` shows
    | provider = users, `php artisan config:show sanctum.routes` shows false.
    |
    | This key is read by SanctumServiceProvider::defineRoutes() (Sanctum
    | 4.3.3) and is not in Sanctum's published config; re-verify it after every
    | Laravel or Sanctum upgrade (the ExampleTest asserts it).
    |
    */

    'routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
