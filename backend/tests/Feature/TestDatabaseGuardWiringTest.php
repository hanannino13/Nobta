<?php

use Illuminate\Support\Facades\DB;

/*
| Proves TestCase checks every connection a test resolves, not only the default one.
| Connections are only resolved, never queried: Laravel creates PDO lazily and the guard throws first,
| so nothing connects to another database. SQLite is deliberately not used here (no file paths).
*/

it('rejects resolving a named non-MySQL connection', function () {
    DB::connection('mariadb');
})->throws(RuntimeException::class, 'Refusing to run tests on driver [mariadb] (connection [mariadb])');

it('rejects a connection added at runtime that points at another database', function () {
    config(['database.connections.reporting' => [
        ...config('database.connections.mysql'),
        'database' => 'nobta',
    ]]);

    DB::connection('reporting');
})->throws(RuntimeException::class, 'Refusing to run tests against database [nobta]');

it('rejects a connection added at runtime that points at a remote host', function () {
    config(['database.connections.remote' => [
        ...config('database.connections.mysql'),
        'host' => 'db.example.com',
    ]]);

    DB::connection('remote');
})->throws(RuntimeException::class, 'Refusing to run tests against host [db.example.com] (connection [remote])');

it('rejects a read/write connection whose read replica is remote', function () {
    config(['database.connections.replicated' => [
        ...config('database.connections.mysql'),
        'read' => ['host' => ['10.0.0.5']],
        'write' => ['host' => ['127.0.0.1']],
    ]]);

    DB::connection('replicated');
})->throws(RuntimeException::class, 'host [10.0.0.5] (connection [replicated::read])');

it('does not let a rejected connection be reused from the cache', function () {
    try {
        DB::connection('mariadb');
    } catch (RuntimeException) {
        // first rejection
    }

    DB::connection('mariadb');
})->throws(RuntimeException::class, 'driver [mariadb]');

it('rejects switching the default connection at runtime', function () {
    config(['database.default' => 'mariadb']);

    DB::connection();
})->throws(RuntimeException::class, 'driver [mariadb]');

it('still allows the approved test connection', function () {
    expect(DB::connection()->getDatabaseName())->toBe('nobta_testing');
});
