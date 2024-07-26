<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\Config\Sqlite;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Types\Coerce;

class SQLiteTest extends AbstractCommonTests
{
    protected static function shouldExecuteTests(): bool
    {
        return true;
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        // No need to wait for SQLite, as we are testing with an in-memory database
    }

    protected static function getConnection(): ConnectionInterface
    {
        return new ConnectionPDO(new Sqlite());
    }

    protected static function createAccountTableQuery(): string
    {
        return 'CREATE TABLE account(
                  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                  username VARCHAR (50) NOT NULL,
                  password VARCHAR (50) NOT NULL,
                  email VARCHAR (250) UNIQUE NOT NULL,
                  phone VARCHAR (100) NULL,
                  birthdate DATE NULL,
                  balance DECIMAL(9,2) DEFAULT 0,
                  description BLOB,
                  picture BLOB,
                  active BOOLEAN,
                  create_date INTEGER NOT NULL
                );';
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
}
