<?php

/*
| Strict CORS: no origin is allowed by default and credentials are disabled. Preflight requests are
| answered by the global HandleCors middleware without reaching any route or the database.
*/

function corsPreflightHeaders(string $origin): array
{
    return [
        'Origin' => $origin,
        'Access-Control-Request-Method' => 'GET',
        'Access-Control-Request-Headers' => 'Authorization',
    ];
}

it('allows no origins by default and keeps credentials disabled', function () {
    expect(config('cors.allowed_origins'))->toBe([])
        ->and(config('cors.allowed_origins_patterns'))->toBe([])
        ->and(config('cors.supports_credentials'))->toBeFalse();
});

it('gives an arbitrary origin no permissive Access-Control-Allow-Origin on preflight', function () {
    $server = $this->transformHeadersToServerVars(corsPreflightHeaders('https://evil.example'));

    $this->call('OPTIONS', '/api/user', server: $server)
        ->assertHeaderMissing('Access-Control-Allow-Origin')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');
});

it('gives an arbitrary origin no Access-Control-Allow-Origin on a normal request', function () {
    $this->getJson('/api/user', ['Origin' => 'https://evil.example'])
        ->assertUnauthorized()
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});

it('allows a single explicitly configured origin and never grants another origin', function () {
    config(['cors.allowed_origins' => ['https://admin.example.test']]);

    $allowed = $this->transformHeadersToServerVars(corsPreflightHeaders('https://admin.example.test'));
    $other = $this->transformHeadersToServerVars(corsPreflightHeaders('https://other.example.test'));

    $this->call('OPTIONS', '/api/user', server: $allowed)
        ->assertHeader('Access-Control-Allow-Origin', 'https://admin.example.test')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');

    // With exactly one allowed origin, fruitcake/php-cors always answers with that configured origin
    // (CorsService::configureAllowedOrigin). A browser on another origin therefore sees a mismatch and
    // blocks the response: the header must never be `*` or echo the foreign origin.
    $foreign = $this->call('OPTIONS', '/api/user', server: $other);

    expect($foreign->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('*')
        ->not->toBe('https://other.example.test');
});

it('echoes only listed origins when several origins are configured', function () {
    config(['cors.allowed_origins' => ['https://admin.example.test', 'https://ops.example.test']]);

    $listed = $this->transformHeadersToServerVars(corsPreflightHeaders('https://ops.example.test'));
    $other = $this->transformHeadersToServerVars(corsPreflightHeaders('https://other.example.test'));

    $this->call('OPTIONS', '/api/user', server: $listed)
        ->assertHeader('Access-Control-Allow-Origin', 'https://ops.example.test');

    $this->call('OPTIONS', '/api/user', server: $other)
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});
