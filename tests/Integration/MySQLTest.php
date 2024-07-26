<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\Exception\AuthorizationException;
use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Connection\Tests\Integration\Features\SchemaIdentifierTestsTrait;
use Squirrel\Types\Coerce;

class MySQLTest extends AbstractCommonTests
{
    use SchemaIdentifierTestsTrait;

    protected static function shouldExecuteTests(): bool
    {
        return isset($_SERVER['SQUIRREL_CONNECTION_HOST_MYSQL']);
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_MYSQL'], 3306);
    }

    protected static function getConnection(): ConnectionInterface
    {
        return new ConnectionPDO(
            new Mysql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_MYSQL'],
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
            ),
        );
    }

    protected static function createAccountTableQuery(): string
    {
        return 'CREATE TABLE account(
                  user_id INT AUTO_INCREMENT,
                  username VARCHAR (50) NOT NULL,
                  password VARCHAR (50) NOT NULL,
                  email VARCHAR (250) NOT NULL,
                  phone VARCHAR (100) NULL,
                  birthdate DATE NULL,
                  balance DECIMAL(9,2) DEFAULT 0,
                  description BLOB,
                  picture BLOB,
                  active TINYINT,
                  create_date INTEGER NOT NULL,
                  PRIMARY KEY (user_id),
                  UNIQUE (email)
                ) ENGINE InnoDB;';
    }

    public function testConnectError(): void
    {
        try {
            new ConnectionPDO(
                new Mysql(
                    host: 'not_reachable',
                    user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                    password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                    dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
                ),
            );
        } catch (DriverException $e) {
            $this->assertSame(ConnectionException::class, $e::class);
        }
    }

    public function testInsertNoLargeObject(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mysql@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.20,
            'description' => 'I am dynamic and nice!',
            'picture' => \hex2bin(\md5('dadaism')),
            'active' => true,
            'create_date' => '48674935',
        ];

        $insertQuery = self::prepareInsertIntoAccount($accountData);

        self::$db->executeQuery($insertQuery, $accountData);

        $userId = self::$db->lastInsertId();

        $where = ['user_id' => $userId];

        $selectQuery = self::prepareSelectFromAccount($where);
        self::$db->executeQuery($selectQuery, $where);

        $insertedData = self::$db->fetchOne($selectQuery);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $accountData['picture'] = \hex2bin(\md5('dadaism'));
        $accountData['phone'] = null;
        $accountData['user_id'] = $userId;
        $insertedData['user_id'] = Coerce::toInt($insertedData['user_id']);

        $accountData['active'] = Coerce::toBool($accountData['active']);
        $insertedData['active'] = Coerce::toBool($insertedData['active']);

        $accountData['create_date'] = Coerce::toInt($accountData['create_date']);
        $insertedData['create_date'] = Coerce::toInt($insertedData['create_date']);

        $accountData['balance'] = \round(Coerce::toFloat($accountData['balance']), 2);
        $insertedData['balance'] = \round(Coerce::toFloat($insertedData['balance']), 2);

        $this->assertEquals($accountData, $insertedData);
    }

    public function testDatabaseDoesNotExistError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        try {
            $insertQuery = self::$db->prepareQuery('USE doesnotexist');

            self::$db->executeQuery($insertQuery);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(AuthorizationException::class, $e::class);
        }
    }
}
