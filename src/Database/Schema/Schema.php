<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Schema;

use Veldora\Framework\Database\Connection;
use Veldora\Framework\Foundation\Application;

class Schema
{
    /**
     * The active connection instance.
     */
    protected static ?Connection $connection = null;

    /**
     * Set the connection instance manually.
     */
    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    /**
     * Resolve the connection instance from Application kernel.
     */
    protected static function getConnection(): Connection
    {
        if (self::$connection === null) {
            self::$connection = Application::getInstance()->get(Connection::class);
        }

        return self::$connection;
    }

    /**
     * Create a new table on the schema.
     */
    public static function create(string $table, \Closure $callback): void
    {
        $connection = self::getConnection();
        $blueprint = new Blueprint($table);
        
        $callback($blueprint);

        $sql = $blueprint->toSql($connection->getDriver());
        $connection->getPdo()->exec($sql);
    }

    /**
     * Drop a table from the schema if it exists.
     */
    public static function dropIfExists(string $table): void
    {
        $connection = self::getConnection();
        $sql = "DROP TABLE IF EXISTS `{$table}`;";
        $connection->getPdo()->exec($sql);
    }

    /**
     * Determine if a given table exists.
     */
    public static function hasTable(string $table): bool
    {
        $connection = self::getConnection();
        $driver = $connection->getDriver();

        if ($driver === 'sqlite') {
            $stmt = $connection->getPdo()->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
            $stmt->execute([$table]);
            return (bool) $stmt->fetch();
        }

        // MySQL / PostgreSQL / others
        $stmt = $connection->getPdo()->prepare("SELECT table_name FROM information_schema.tables WHERE table_name = ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetch();
    }

    /**
     * Determine if a given column exists on a table.
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $connection = self::getConnection();
        $driver = $connection->getDriver();

        if ($driver === 'sqlite') {
            $stmt = $connection->getPdo()->query("PRAGMA table_info(`{$table}`)");
            if ($stmt === false) {
                return false;
            }
            $cols = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if (($col['name'] ?? null) === $column) {
                    return true;
                }
            }
            return false;
        }

        $stmt = $connection->getPdo()->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetch();
    }

    /**
     * Rename an existing table.
     */
    public static function rename(string $from, string $to): void
    {
        $connection = self::getConnection();
        $sql = "ALTER TABLE `{$from}` RENAME TO `{$to}`;";
        $connection->getPdo()->exec($sql);
    }

    /**
     * Modify an existing table on the schema.
     */
    public static function table(string $table, \Closure $callback): void
    {
        $connection = self::getConnection();
        $blueprint = new Blueprint($table);

        $callback($blueprint);

        $statements = $blueprint->toAlterSql($connection->getDriver());
        foreach ($statements as $sql) {
            $connection->getPdo()->exec($sql);
        }
    }
}
