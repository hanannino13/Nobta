<?php

/*
| Regression: an API guest request without `Accept: application/json` used to resolve route('login'),
| which does not exist, and fail with HTTP 500. API guests must get a JSON 401 and never a redirect.
| These tests make no database queries (no bearer token is sent).
*/

it('returns a JSON 401 for an API guest request without an Accept header', function () {
    $response = $this->get('/api/user');

    $response->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson(['message' => 'Unauthenticated.']);

    expect($response->headers->has('Location'))->toBeFalse();
});

it('returns a JSON 401 for an API guest request that explicitly asks for HTML', function () {
    $this->get('/api/user', ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('still returns a JSON 401 when the client sends Accept: application/json', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('leaves non-API web routes unchanged', function () {
    $this->get('/')
        ->assertOk()
        ->assertJson(['name' => config('app.name'), 'api' => '/api/v1']);
});
