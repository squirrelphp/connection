<?php

namespace Squirrel\Connection\Tests\Integration\Features;

trait SchemaIdentifierTestsTrait
{
    public function testSelectWithDatabase(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $this->initializeDataWithDefaultTwoEntries();

        $selectQuery = self::$db->prepareQuery(
            'SELECT * FROM ' .
            self::$db->quoteIdentifier('shop.account') .
            ' WHERE 1=1',
        );

        self::$db->executeQuery($selectQuery);

        $rows = self::$db->fetchAll($selectQuery);

        $this->assertSame(2, \count($rows));

        $defaultRows = $this->getDefaultTwoEntries();

        foreach ($rows as $key => $row) {
            $this->compareDataArraysWithoutCount($defaultRows[$key], $row);
        }

        self::$db->freeResults($selectQuery);
    }

    public function testInsertWithDatabase(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $accountData = [
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 800,
            'description' => 'I am dynamicer and nicer!',
            'active' => true,
            'create_date' => 486749356,
        ];

        $insertQuery = self::$db->prepareQuery(
            'INSERT INTO ' .
            self::$db->quoteIdentifier('shop.account') .
            ' (' .
            \implode(', ', self::quoteIdentifiers(\array_keys($accountData))) .
            ') VALUES (' .
            \implode(', ', self::generatePlaceholders($accountData)) .
            ')',
        );

        self::$db->executeQuery($insertQuery, $accountData);

        $userId = self::$db->lastInsertId();

        $where = ['user_id' => $userId];

        $selectQuery = self::prepareSelectFromAccount($where);
        self::$db->executeQuery($selectQuery, $where);

        $insertedData = self::$db->fetchOne($selectQuery);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $this->compareDataArraysWithoutCount($accountData, $insertedData);
    }
}
