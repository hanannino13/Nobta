<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/*
| Runs against the existing nobta_testing schema. DatabaseTransactions wraps each test in a
| transaction that is rolled back: no migrations, no migrate:fresh, no schema changes.
*/

uses(DatabaseTransactions::class);

it('runs feature tests against the dedicated MySQL test database', function () {
    expect(DB::connection()->getDriverName())->toBe('mysql')
        ->and(DB::connection()->getDatabaseName())->toBe('nobta_testing');
});

it('can persist and read a model inside a rolled-back transaction', function () {
    $user = User::factory()->create();

    expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();
});

it('forces the MySQL session time zone to UTC', function () {
    $row = DB::selectOne('SELECT @@session.time_zone AS tz, NOW() = UTC_TIMESTAMP() AS session_is_utc');

    expect($row->tz)->toBe('+00:00')
        ->and((int) $row->session_is_utc)->toBe(1);
});

it('uses the approved collation for the application connection', function () {
    $row = DB::selectOne('SELECT @@session.collation_connection AS collation');

    expect($row->collation)->toBe('utf8mb4_0900_ai_ci');
});
