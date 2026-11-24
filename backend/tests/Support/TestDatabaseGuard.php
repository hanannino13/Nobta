<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\ConfigurationUrlParser;
use ReflectionProperty;
use RuntimeException;

/**
 * Refuses any test database other than the dedicated, local MySQL test database.
 *
 * Feature tests run on MySQL only (docs/database/DATABASE_STRATEGY.md §6), so SQLite is rejected
 * in every form (file-backed and :memory:). The check uses the *effective* connection settings:
 * Laravel merges a connection's `url` (DB_URL) over its discrete keys when connecting, so the URL is
 * resolved here exactly as Laravel resolves it, without opening a connection or touching any file.
 *
 * Every effective configuration must satisfy all three rules: driver `mysql`, database `nobta_testing`,
 * and a loopback host from LOCAL_HOSTS. The database name alone is never trusted, because a remote
 * server could also have a database called `nobta_testing`. Read/write split sections (`read`, `write`)
 * are checked too, since Laravel connects with those merged settings.
 */
final class TestDatabaseGuard
{
    public const REQUIRED_DRIVER = 'mysql';

    public const REQUIRED_DATABASE = 'nobta_testing';

    /**
     * Exact loopback hosts only (compared case-insensitively, IPv6 brackets removed). Deliberately not
     * broadened: no other 127.0.0.0/8 addresses, no 0.0.0.0, no hostnames that merely contain "localhost".
     *
     * @var list<string>
     */
    public const LOCAL_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    /**
     * Validate a resolved connection (any name: default, named, transacted, or created on the fly).
     *
     * Laravel creates the PDO lazily, so this runs before any network connection or file access.
     *
     * @throws RuntimeException when the connection is not the approved test database
     */
    public static function assertConnectionSafe(Connection $connection): void
    {
        $connectionName = (string) ($connection->getName() ?? 'unnamed');
        $config = $connection->getConfig();

        // For a read/write connection this is the merged *write* configuration.
        self::assertSafe($connectionName, is_array($config) ? $config : null);

        // Laravel keeps the merged *read* configuration on the connection without a public getter.
        // Reading the property is side-effect free (getReadPdo() would connect, so it is not used).
        $readConfig = (new ReflectionProperty(Connection::class, 'readPdoConfig'))->getValue($connection);

        if (is_array($readConfig) && $readConfig !== []) {
            self::assertSafe("{$connectionName}::read", $readConfig);
        }

        // Pooled "direct" connections exist for pgsql only (already refused by the driver rule); checked for completeness.
        $directConfig = $connection->getDirectPdoConfig();

        if ($directConfig !== []) {
            self::assertSafe("{$connectionName}::direct", $directConfig);
        }
    }

    /**
     * @param  array<string, mixed>|null  $connectionConfig
     *
     * @throws RuntimeException when the effective connection is not the approved test database
     */
    public static function assertSafe(string $connectionName, ?array $connectionConfig): void
    {
        if ($connectionConfig === null || $connectionConfig === []) {
            throw new RuntimeException("Refusing to run tests: database connection [{$connectionName}] is not configured.");
        }

        $effective = (new ConfigurationUrlParser)->parseConfiguration($connectionConfig);

        self::assertEffectiveSafe($connectionName, $effective);

        // Mirror Laravel's ConnectionFactory: each `read` / `write` section (a single array, or a list of
        // arrays of which Laravel picks one at random) is merged over the base configuration.
        $base = Arr::except($effective, ['read', 'write']);

        foreach (['read', 'write'] as $type) {
            if (! array_key_exists($type, $effective)) {
                continue;
            }

            $section = $effective[$type];
            $variants = is_array($section) && isset($section[0]) ? $section : [$section];

            foreach ($variants as $variant) {
                if (! is_array($variant)) {
                    throw new RuntimeException(
                        "Refusing to run tests: the [{$type}] configuration of connection [{$connectionName}] is invalid."
                    );
                }

                self::assertEffectiveSafe("{$connectionName}::{$type}", array_merge($base, $variant));
            }
        }
    }

    /**
     * @param  array<mixed>  $effective  configuration after DB_URL resolution
     *
     * @throws RuntimeException when the driver, database or host is not the approved local test database
     */
    private static function assertEffectiveSafe(string $connectionName, array $effective): void
    {
        $driver = $effective['driver'] ?? null;
        $database = $effective['database'] ?? null;

        if ($driver !== self::REQUIRED_DRIVER) {
            $shown = is_string($driver) ? $driver : 'none';

            throw new RuntimeException(
                "Refusing to run tests on driver [{$shown}] (connection [{$connectionName}]). Tests require MySQL."
            );
        }

        if ($database !== self::REQUIRED_DATABASE) {
            $shown = is_string($database) ? $database : 'none';

            throw new RuntimeException(
                "Refusing to run tests against database [{$shown}]. Tests require [".self::REQUIRED_DATABASE.'].'
            );
        }

        self::assertLocalHost($connectionName, $effective['host'] ?? null);
    }

    /**
     * Laravel accepts a single host or a list of hosts (it tries them in random order), so every entry
     * must be local. A missing or empty host is refused: config/database.php already applies its
     * `127.0.0.1` fallback before the guard sees the configuration, so an absent host here is not safe.
     *
     * @throws RuntimeException when any host is missing or not in LOCAL_HOSTS
     */
    private static function assertLocalHost(string $connectionName, mixed $host): void
    {
        $hosts = is_array($host) ? array_values($host) : [$host];

        if ($hosts === []) {
            $hosts = [null];
        }

        foreach ($hosts as $candidate) {
            $normalized = self::normalizeHost($candidate);

            if ($normalized === null || ! in_array($normalized, self::LOCAL_HOSTS, true)) {
                $shown = is_string($candidate) && trim($candidate) !== '' ? trim($candidate) : 'none';

                throw new RuntimeException(
                    "Refusing to run tests against host [{$shown}] (connection [{$connectionName}]). "
                    .'Tests require a local MySQL host: '.implode(', ', self::LOCAL_HOSTS).'.'
                );
            }
        }
    }

    /**
     * Trim, lower-case, and remove one pair of IPv6 URL brackets ("[::1]" → "::1"). Non-strings and empty
     * values return null so they are refused.
     */
    private static function normalizeHost(mixed $host): ?string
    {
        if (! is_string($host)) {
            return null;
        }

        $normalized = strtolower(trim($host));

        if (str_starts_with($normalized, '[') && str_ends_with($normalized, ']')) {
            $normalized = substr($normalized, 1, -1);
        }

        return $normalized === '' ? null : $normalized;
    }
}
