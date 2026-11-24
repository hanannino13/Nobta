<?php

use Tests\TestCase;

/*
| Feature tests boot the full Laravel application.
| Database-backed tests opt in to DatabaseTransactions per file (uses(DatabaseTransactions::class)):
| each test runs in a rolled-back transaction against the existing, already-migrated nobta_testing
| schema. Tests never create, migrate or reset the schema. Unit and Arch tests stay framework-free.
*/

pest()->extend(TestCase::class)->in('Feature');
