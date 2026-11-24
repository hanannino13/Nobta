<?php

namespace Tests;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run tests against anything other than the dedicated MySQL test database.
     *
     * 1. The default connection is checked before any testing trait (RefreshDatabase,
     *    DatabaseTransactions, …), so a misconfigured environment fails before anything runs.
     * 2. Every connection resolved during the test (named connections, $connectionsToTransact,
     *    a default changed at runtime, DB::connectUsing) is checked when Laravel creates it.
     *    PDO is lazy, so a rejected connection never reaches a database or a file. It is purged so
     *    a later DB::connection() call cannot reuse the cached instance.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connectionName = (string) config('database.default');
        $connectionConfig = config("database.connections.{$connectionName}");

        TestDatabaseGuard::assertSafe(
            $connectionName,
            is_array($connectionConfig) ? $connectionConfig : null,
        );

        $this->app['events']->listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            try {
                TestDatabaseGuard::assertConnectionSafe($event->connection);
            } catch (RuntimeException $exception) {
                $this->app['db']->purge($event->connectionName);

                throw $exception;
            }
        });

        return parent::setUpTraits();
    }
}
