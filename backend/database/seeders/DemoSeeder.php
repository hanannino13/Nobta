<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo/test data with known credentials. Never runs outside local and testing.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var list<string> */
    public const ALLOWED_ENVIRONMENTS = ['local', 'testing'];

    public static function isAllowedIn(string $environment): bool
    {
        return in_array($environment, self::ALLOWED_ENVIRONMENTS, true);
    }

    public function run(): void
    {
        $environment = (string) app()->environment();

        if (! self::isAllowedIn($environment)) {
            throw new RuntimeException(
                "DemoSeeder refuses to run in the [{$environment}] environment; it is allowed only in: "
                .implode(', ', self::ALLOWED_ENVIRONMENTS).'.'
            );
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
