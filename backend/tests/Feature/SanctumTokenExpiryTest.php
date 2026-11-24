<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/*
| ADR-016: access tokens live 30 minutes; Sanctum's global `expiration` is a 30-minute backstop.
| DatabaseTransactions: every row written here is rolled back; the schema is never rebuilt.
| One request per test, so no authenticated user is cached between requests.
*/

uses(DatabaseTransactions::class);

it('configures a 30-minute Sanctum expiration backstop', function () {
    expect(config('sanctum.expiration'))->toBe(30);
});

it('accepts a freshly issued token', function () {
    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/user')->assertOk();
});

it('rejects a token older than 30 minutes', function () {
    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->travel(31)->minutes();

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});

it('honours a per-token expires_at of 30 minutes (ADR-016 access tokens)', function () {
    $token = User::factory()->create()
        ->createToken('access', ['*'], now()->addMinutes(30))
        ->plainTextToken;

    $this->travel(31)->minutes();

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});

it('applies the backstop even when a token claims a longer expires_at', function () {
    $token = User::factory()->create()
        ->createToken('long-lived', ['*'], now()->addHours(2))
        ->plainTextToken;

    $this->travel(31)->minutes();

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});
