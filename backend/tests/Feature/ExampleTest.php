<?php

use App\Models\User;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/*
| No database: none of these tests uses a database trait, creates a token or saves a model.
| B2-M1 / B2-L1: the root route is stateless, Sanctum's CSRF route is disabled, the Sanctum guard is
| restricted to the `users` provider, and /api/user never exposes credential fields.
*/

it('exposes the health check endpoint', function () {
    $this->get('/up')->assertOk();
});

it('returns a JSON identifier at the web root', function () {
    $this->getJson('/')
        ->assertOk()
        ->assertJson(['name' => config('app.name'), 'api' => '/api/v1']);
});

it('rejects unauthenticated access to the authenticated user endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('serves the web root without starting a session or issuing cookies (B2-M1)', function () {
    $this->getJson('/')
        ->assertOk()
        ->assertExactJson(['name' => config('app.name'), 'api' => '/api/v1'])
        ->assertCookieMissing(config('session.cookie'))
        ->assertCookieMissing('XSRF-TOKEN');
});

it('does not run session middleware on the web root (B2-M1)', function () {
    $route = app('router')->getRoutes()->match(Request::create('/', 'GET'));

    expect(app('router')->gatherRouteMiddleware($route))->not->toContain(StartSession::class);
});

it('disables the Sanctum CSRF cookie route (B2-M1)', function () {
    expect(config('sanctum.routes'))->toBeFalse()
        ->and(Route::has('sanctum.csrf-cookie'))->toBeFalse();

    $this->getJson('/sanctum/csrf-cookie')->assertNotFound();
});

it('keeps Sanctum bearer-token authentication configured (B2-M1 / B2-L1)', function () {
    $userRoute = app('router')->getRoutes()->match(Request::create('/api/user', 'GET'));

    expect(config('auth.guards.sanctum.driver'))->toBe('sanctum')
        ->and(Auth::guard('sanctum'))->toBeInstanceOf(RequestGuard::class)
        ->and($userRoute->gatherMiddleware())->toContain('auth:sanctum')
        ->and(config('sanctum.expiration'))->toBe(30);
});

it('restricts the Sanctum guard to the users provider (B2-L1)', function () {
    expect(config('auth.guards.sanctum.provider'))->toBe('users')
        ->and(config('auth.providers.users.model'))->toBe(User::class);
});

it('never exposes password or remember_token from /api/user', function () {
    // Unsaved model: no row, no token row, no query. Values are test data, not real credentials.
    $user = User::factory()->make(['remember_token' => 'test-remember-token']);

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email)
        ->assertJsonMissingPath('password')
        ->assertJsonMissingPath('remember_token');
});
