<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\Config\Pgsql;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Connection\Tests\Integration\Features\SchemaIdentifierTestsTrait;

class PostgreSQLTest extends AbstractCommonTests
{
    use SchemaIdentifierTestsTrait;

    protected static function shouldExecuteTests(): bool
    {
        return isset($_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES']);
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES'], 5432);
    }

    protected static function getConnection(): ConnectionInterface
    {
        return new ConnectionPDO(
            new Pgsql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES'],
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
            ),
        );
    }

    protected static function createAccountTableQuery(): string
    {
        return 'CREATE TABLE account(
                  user_id serial PRIMARY KEY,
                  username VARCHAR (50) NOT NULL,
                  password VARCHAR (50) NOT NULL,
                  email VARCHAR (250) UNIQUE NOT NULL,
                  phone VARCHAR (100) NULL,
                  birthdate DATE NULL,
                  balance NUMERIC(9,2) DEFAULT 0,
                  description BYTEA,
                  picture BYTEA,
                  active BOOLEAN,
                  create_date INTEGER NOT NULL
                );';
    }

    public function testConnectError(): void
    {
        try {
            new ConnectionPDO(
                new Pgsql(
                    host: 'not_reachable',
                    user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                    password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                    dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
                ),
            );
        } catch (DriverException $e) {
            $this->assertSame(ConnectionException::class, $e::class);
            $this->assertSame('08006', $e->getSqlState());
        }
    }

    public function testSpecialTypes(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        self::$db->prepareAndExecuteQuery('DROP TABLE IF EXISTS locations');

        self::$db->prepareAndExecuteQuery(
            'CREATE TABLE locations (
               current_location POINT,
               ip_address INET,
               create_date INTEGER NOT NULL
             );',
        );

        self::$db->prepareAndExecuteQuery('INSERT INTO "locations" (current_location, ip_address, create_date) VALUES (?, ?, ?)', [
            'current_location' => '(5,13)',
            'ip_address' => '212.55.108.55',
            'create_date' => 34534543,
        ]);

        $selectQuery = self::$db->prepareQuery('SELECT * FROM ' . self::$db->quoteIdentifier('locations'));
        self::$db->executeQuery($selectQuery);

        $entry = self::$db->fetchOne($selectQuery);

        if ($entry === null) {
            throw new \LogicException('Inserted row not found');
        }

        $this->assertEquals([
            'current_location' => '(5,13)',
            'ip_address' => '212.55.108.55',
            'create_date' => '34534543',
        ], $entry);
    }
}
