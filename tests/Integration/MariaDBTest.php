<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\PDO\ConnectionPDO;

class MariaDBTest extends MySQLTest
{
    protected static function shouldExecuteTests(): bool
    {
        return isset($_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB']);
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'], 3306);
    }

    protected static function getConnection(): ConnectionInterface
    {
        return new ConnectionPDO(
            new Mysql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'],
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
            ),
        );
    }
}
