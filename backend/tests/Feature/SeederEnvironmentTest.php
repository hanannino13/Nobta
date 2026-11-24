<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/*
| DATABASE_STRATEGY.md §5: demo/factory seeders run in local and testing only.
| Seeders are invoked directly (not via `db:seed`) so no production confirmation prompt is involved.
| DatabaseTransactions: every row written here is rolled back; the schema is never rebuilt.
*/

uses(DatabaseTransactions::class);

function runSeederIn(string $environment, string $seeder): void
{
    app()->detectEnvironment(fn () => $environment);
    app($seeder)->__invoke();
}

it('does not seed the demo user in production or staging', function (string $environment) {
    runSeederIn($environment, DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
})->with(['production', 'staging']);

it('refuses to run DemoSeeder directly outside local and testing', function (string $environment) {
    runSeederIn($environment, DemoSeeder::class);
})->with(['production', 'staging'])->throws(RuntimeException::class, 'DemoSeeder refuses to run');

it('seeds the demo user in the testing environment', function () {
    runSeederIn('testing', DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
});

it('limits demo seeding to local and testing', function () {
    expect(DemoSeeder::ALLOWED_ENVIRONMENTS)->toBe(['local', 'testing'])
        ->and(DemoSeeder::isAllowedIn('local'))->toBeTrue()
        ->and(DemoSeeder::isAllowedIn('production'))->toBeFalse()
        ->and(DemoSeeder::isAllowedIn('staging'))->toBeFalse();
});
