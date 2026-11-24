<?php

use Illuminate\Database\Connection;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Tests\Support\TestDatabaseGuard;

/*
| Pure unit tests: the guard only receives arrays. No Laravel application is booted, no database
| connection is opened, and no file is created, read or deleted (SQLite paths are plain strings).
*/

$safe = [
    'driver' => 'mysql',
    'url' => '',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'nobta_testing',
    'username' => 'nobta',
    'password' => 'not-a-real-password',
];

it('accepts the dedicated MySQL test database', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', $safe);
})->throwsNoExceptions();

it('accepts a URL whose effective database is the test database', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [
        ...$safe,
        'database' => 'nobta',
        'url' => 'mysql://nobta:secret@127.0.0.1:3306/nobta_testing',
    ]);
})->throwsNoExceptions();

it('rejects a MySQL database other than the test database', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'database' => 'nobta']);
})->throws(RuntimeException::class, 'Refusing to run tests against database [nobta]');

it('rejects a DB_URL that overrides the database name', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [
        ...$safe,
        'url' => 'mysql://nobta:secret@127.0.0.1:3306/nobta',
    ]);
})->throws(RuntimeException::class, 'Refusing to run tests against database [nobta]');

it('rejects in-memory SQLite', function () {
    TestDatabaseGuard::assertSafe('sqlite', ['driver' => 'sqlite', 'database' => ':memory:']);
})->throws(RuntimeException::class, 'Refusing to run tests on driver [sqlite]');

it('rejects file-backed SQLite, even when named like the test database', function () {
    TestDatabaseGuard::assertSafe('sqlite', ['driver' => 'sqlite', 'database' => 'nobta_testing']);
})->throws(RuntimeException::class, 'Refusing to run tests on driver [sqlite]');

it('rejects a DB_URL that switches the driver to SQLite', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'url' => 'sqlite:///nobta_testing']);
})->throws(RuntimeException::class, 'Refusing to run tests on driver [sqlite]');

it('rejects non-MySQL drivers such as MariaDB', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mariadb', [...$safe, 'driver' => 'mariadb']);
})->throws(RuntimeException::class, 'Refusing to run tests on driver [mariadb]');

it('rejects a missing database name', function () use ($safe) {
    $config = $safe;
    unset($config['database']);

    TestDatabaseGuard::assertSafe('mysql', $config);
})->throws(RuntimeException::class, 'Refusing to run tests against database [none]');

it('rejects an unconfigured connection', function () {
    TestDatabaseGuard::assertSafe('missing', null);
})->throws(RuntimeException::class, 'is not configured');

/*
| Host rule (F5): the database name alone is not trusted. Only exact loopback hosts are accepted.
*/

it('accepts the allowed local hosts', function (string $host) use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'host' => $host]);
})->with([
    '127.0.0.1' => '127.0.0.1',
    'localhost' => 'localhost',
    '::1' => '::1',
    'bracketed IPv6 [::1]' => '[::1]',
    'mixed case and spaces' => ' LocalHost ',
])->throwsNoExceptions();

it('rejects non-local or missing hosts', function (mixed $host, string $shown) use ($safe) {
    expect(fn () => TestDatabaseGuard::assertSafe('mysql', [...$safe, 'host' => $host]))
        ->toThrow(RuntimeException::class, "Refusing to run tests against host [{$shown}] (connection [mysql])");
})->with([
    'remote hostname' => ['db.example.com', 'db.example.com'],
    'hostname containing localhost' => ['localhost.example.com', 'localhost.example.com'],
    'private IP 10/8' => ['10.0.0.5', '10.0.0.5'],
    'private IP 192.168/16' => ['192.168.1.20', '192.168.1.20'],
    'public IP' => ['203.0.113.10', '203.0.113.10'],
    'other 127/8 address (allowlist not broadened)' => ['127.0.0.2', '127.0.0.2'],
    'wildcard 0.0.0.0' => ['0.0.0.0', '0.0.0.0'],
    'empty string' => ['', 'none'],
    'whitespace only' => ['   ', 'none'],
    'null' => [null, 'none'],
    'empty host list' => [[], 'none'],
]);

it('rejects a missing host key', function () use ($safe) {
    $config = $safe;
    unset($config['host']);

    TestDatabaseGuard::assertSafe('mysql', $config);
})->throws(RuntimeException::class, 'Refusing to run tests against host [none]');

it('rejects a DB_URL that points at a remote host', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [
        ...$safe,
        'url' => 'mysql://nobta:secret@db.example.com:3306/nobta_testing',
    ]);
})->throws(RuntimeException::class, 'Refusing to run tests against host [db.example.com]');

it('accepts a DB_URL that points at a local host', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [
        ...$safe,
        'host' => 'db.example.com',
        'url' => 'mysql://nobta:secret@localhost:3306/nobta_testing',
    ]);
})->throwsNoExceptions();

it('accepts a host list only when every entry is local', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'host' => ['127.0.0.1', 'localhost']]);
})->throwsNoExceptions();

it('rejects a host list that contains a remote entry', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'host' => ['127.0.0.1', '10.0.0.5']]);
})->throws(RuntimeException::class, 'host [10.0.0.5]');

it('rejects a read/write split whose read host is remote', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'read' => ['host' => ['10.0.0.5']], 'write' => ['host' => ['127.0.0.1']]]);
})->throws(RuntimeException::class, 'host [10.0.0.5] (connection [mysql::read])');

it('rejects a read/write list entry that overrides the database', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'write' => [['host' => '127.0.0.1'], ['database' => 'nobta']]]);
})->throws(RuntimeException::class, 'database [nobta]');

it('accepts a read/write split where every section is local', function () use ($safe) {
    TestDatabaseGuard::assertSafe('mysql', [...$safe, 'read' => ['host' => 'localhost'], 'write' => ['host' => '127.0.0.1']]);
})->throwsNoExceptions();

/*
| Named / resolved connections (assertConnectionSafe). Real Laravel connection objects are built with a
| PDO resolver that throws if it is ever called, proving the guard decides without connecting.
*/

function unconnectedConnection(string $class, array $config): Connection
{
    $neverConnect = function (): never {
        throw new LogicException('The guard must not open a database connection.');
    };

    return new $class($neverConnect, (string) ($config['database'] ?? ''), '', $config);
}

it('accepts a resolved mysql connection to the test database', function () use ($safe) {
    TestDatabaseGuard::assertConnectionSafe(unconnectedConnection(MySqlConnection::class, [...$safe, 'name' => 'mysql']));
})->throwsNoExceptions();

it('rejects named connections that are not the approved test database', function (string $class, array $config, string $message) {
    expect(fn () => TestDatabaseGuard::assertConnectionSafe(unconnectedConnection($class, $config)))
        ->toThrow(RuntimeException::class, $message);
})->with([
    'sqlite (file path, string only)' => [SQLiteConnection::class, ['driver' => 'sqlite', 'name' => 'sqlite', 'database' => 'database/database.sqlite'], 'driver [sqlite]'],
    'sqlite (:memory:)' => [SQLiteConnection::class, ['driver' => 'sqlite', 'name' => 'sqlite', 'database' => ':memory:'], 'driver [sqlite]'],
    'mariadb' => [MariaDbConnection::class, ['driver' => 'mariadb', 'name' => 'mariadb', 'database' => 'nobta_testing'], 'driver [mariadb]'],
    'pgsql' => [PostgresConnection::class, ['driver' => 'pgsql', 'name' => 'pgsql', 'database' => 'nobta_testing'], 'driver [pgsql]'],
    'mysql, other database' => [MySqlConnection::class, ['driver' => 'mysql', 'name' => 'reporting', 'database' => 'nobta'], 'database [nobta]'],
    'mysql, no driver key' => [MySqlConnection::class, ['name' => 'mysql', 'database' => 'nobta_testing'], 'driver [none]'],
    'mysql, remote host' => [MySqlConnection::class, ['driver' => 'mysql', 'name' => 'replica', 'database' => 'nobta_testing', 'host' => '10.0.0.5'], 'host [10.0.0.5] (connection [replica])'],
    'mysql, no host' => [MySqlConnection::class, ['driver' => 'mysql', 'name' => 'mysql', 'database' => 'nobta_testing'], 'host [none]'],
]);

it('rejects a resolved connection whose read replica is remote', function () use ($safe) {
    $connection = unconnectedConnection(MySqlConnection::class, [...$safe, 'name' => 'mysql']);
    $connection->setReadPdoConfig([...$safe, 'name' => 'mysql', 'host' => 'replica.example.com']);

    TestDatabaseGuard::assertConnectionSafe($connection);
})->throws(RuntimeException::class, 'host [replica.example.com] (connection [mysql::read])');

it('accepts a resolved connection whose read replica is local', function () use ($safe) {
    $connection = unconnectedConnection(MySqlConnection::class, [...$safe, 'name' => 'mysql']);
    $connection->setReadPdoConfig([...$safe, 'name' => 'mysql', 'host' => 'localhost']);

    TestDatabaseGuard::assertConnectionSafe($connection);
})->throwsNoExceptions();

it('names the rejected connection in the error message', function () {
    TestDatabaseGuard::assertConnectionSafe(
        unconnectedConnection(SQLiteConnection::class, ['driver' => 'sqlite', 'name' => 'sqlite', 'database' => ':memory:'])
    );
})->throws(RuntimeException::class, 'Refusing to run tests on driver [sqlite] (connection [sqlite])');
