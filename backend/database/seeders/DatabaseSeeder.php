<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Reference-data seeders (e.g. IndustrySeeder, M3) run in every environment and are added here.
     * Demo data runs only in local and testing (DATABASE_STRATEGY.md §5).
     */
    public function run(): void
    {
        if (DemoSeeder::isAllowedIn((string) app()->environment())) {
            $this->call(DemoSeeder::class);
        }
    }
}
