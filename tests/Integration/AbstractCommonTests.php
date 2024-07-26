<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\ConnectionQueryInterface;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\Exception\InvalidFieldNameException;
use Squirrel\Connection\Exception\NonUniqueFieldNameException;
use Squirrel\Connection\Exception\NotNullConstraintViolationException;
use Squirrel\Connection\Exception\SyntaxErrorException;
use Squirrel\Connection\Exception\TableExistsException;
use Squirrel\Connection\Exception\TableNotFoundException;
use Squirrel\Connection\Exception\UniqueConstraintViolationException;
use Squirrel\Connection\LargeObject;
use Squirrel\Types\Coerce;

abstract class AbstractCommonTests extends \PHPUnit\Framework\TestCase
{
    protected static ConnectionInterface $db;

    abstract protected static function shouldExecuteTests(): bool;

    abstract protected static function waitUntilThisDatabaseReady(): void;

    abstract protected static function getConnection(): ConnectionInterface;

    abstract protected static function createAccountTableQuery(): string;

    protected static function waitUntilDatabaseReady(string $host, int $port): void
    {
        $maxSleep = 60;

        while (!@fsockopen($host, $port)) {
            $maxSleep--;

            // Quit after 60 seconds
            if ($maxSleep <= 0) {
                throw new \Exception('No connection possible to ' . $host . ':' . $port);
            }

            sleep(1);
        }
    }

    protected static function getConnectionAndInitializeAccount(): ConnectionInterface
    {
        $db = static::getConnection();

        $db->prepareAndExecuteQuery('DROP TABLE IF EXISTS account');
        $db->prepareAndExecuteQuery(static::createAccountTableQuery());

        return $db;
    }

    /** @param string[] $identifiers */
    protected static function quoteIdentifiers(array $identifiers): array
    {
        return \array_map(self::$db->quoteIdentifier(...), $identifiers);
    }

    protected static function generatePlaceholders(array $values): array
    {
        return \array_map(fn($v) => '?', $values);
    }

    protected static function prepareSelectFromAccount(array $where): ConnectionQueryInterface
    {
        return self::$db->prepareQuery(
            'SELECT * FROM ' .
            self::$db->quoteIdentifier('account') .
            ' WHERE ' .
            \implode(' AND ', \array_map(fn (string $w): string => $w . ' = ?', self::quoteIdentifiers(\array_keys($where)))),
        );
    }

    protected static function prepareInsertIntoAccount(array $accountData): ConnectionQueryInterface
    {
        return self::$db->prepareQuery(
            'INSERT INTO ' .
            self::$db->quoteIdentifier('account') .
            ' (' .
            \implode(', ', self::quoteIdentifiers(\array_keys($accountData))) .
            ') VALUES (' .
            \implode(', ', self::generatePlaceholders($accountData)) .
            ')',
        );
    }

    protected static function prepareUpdateAccount(array $update, array $where): ConnectionQueryInterface
    {
        return self::$db->prepareQuery(
            'UPDATE ' .
            self::$db->quoteIdentifier('account') .
            ' SET ' .
            \implode(', ', \array_map(fn (string $w): string => $w . ' = ?', self::quoteIdentifiers(\array_keys($update)))) .
            ' WHERE ' .
            \implode(', ', \array_map(fn (string $w): string => $w . ' = ?', self::quoteIdentifiers(\array_keys($where)))),
        );
    }

    public static function setUpBeforeClass(): void
    {
        static::waitUntilThisDatabaseReady();
    }

    protected function setUp(): void
    {
        if (!static::shouldExecuteTests()) {
            $this->markTestSkipped('Not in an environment with correct database');
        }
    }

    public function testInsert(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.2,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => 48674935,
        ];

        $insertQuery = self::prepareInsertIntoAccount($accountData);

        self::$db->executeQuery($insertQuery, $accountData);

        $userId = self::$db->lastInsertId();

        $this->assertSame('1', $userId);

        $where = ['user_id' => 1];

        $selectQuery = self::prepareSelectFromAccount($where);
        self::$db->executeQuery($selectQuery, $where);

        $insertedData = self::$db->fetchOne($selectQuery);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $accountData['phone'] = null;
        $accountData['user_id'] = Coerce::toInt($userId);

        $this->compareDataArrays($accountData, $insertedData);
    }

    public function testUpdate(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $this->initializeDataWithDefaultTwoEntries();

        $picture = new LargeObject(\hex2bin(\md5('dadaism')));

        $accountData = [
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 800,
            'description' => 'I am dynamicer and nicer!',
            'picture' => $picture,
            'active' => true,
            'create_date' => 486749356,
        ];

        $where = ['user_id' => 2];

        // UPDATE where changes are made and we should get one affected row
        $updateQuery = self::prepareUpdateAccount($accountData, $where);

        self::$db->executeQuery($updateQuery, \array_merge(\array_values($accountData), \array_values($where)));

        $rowsAffected = self::$db->rowCount($updateQuery);

        $this->assertEquals(1, $rowsAffected);

        $selectQuery = self::prepareSelectFromAccount($where);
        self::$db->executeQuery($selectQuery, $where);

        $insertedData = self::$db->fetchOne($selectQuery);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $comparableAccountData = $accountData;
        $comparableAccountData['phone'] = null;
        $comparableAccountData['user_id'] = 2;

        $this->compareDataArrays($comparableAccountData, $insertedData);

        $accountData['picture'] = $picture;

        // UPDATE where we do not change anything and test if we still get 1 as $rowsAffected
        self::$db->executeQuery($updateQuery, \array_merge(\array_values($accountData), \array_values($where)));

        $rowsAffected = self::$db->rowCount($updateQuery);

        $this->assertEquals(1, $rowsAffected);
    }

    public function testCount(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $countQuery = self::$db->prepareQuery('SELECT COUNT(*) AS num FROM ' . self::$db->quoteIdentifier('account'));
        self::$db->executeQuery($countQuery);

        $rowData = self::$db->fetchOne($countQuery);

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['num'] = \intval($rowData['num']);

        $this->assertEquals(['num' => 0], $rowData);

        $this->initializeDataWithDefaultTwoEntries();

        self::$db->executeQuery($countQuery);

        $rowData = self::$db->fetchOne($countQuery);

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['num'] = \intval($rowData['num']);

        $this->assertEquals(['num' => 2], $rowData);
    }

    public function testSelect(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $selectQuery = self::$db->prepareQuery('SELECT * FROM ' . self::$db->quoteIdentifier('account'));
        self::$db->executeQuery($selectQuery);

        $rowData = self::$db->fetchOne($selectQuery);

        $this->assertEquals(null, $rowData);

        $this->initializeDataWithDefaultTwoEntries();

        $accountData = [
            'user_id' => 2,
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'phone' => null,
            'birthdate' => '1984-05-08',
            'balance' => 800,
            'description' => 'I am dynamicer and nicer!',
            'picture' => \hex2bin(\md5('dadaism')),
            'active' => true,
            'create_date' => 486749356,
        ];

        $selectQuery = self::$db->prepareQuery('SELECT * FROM ' . self::$db->quoteIdentifier('account') . ' WHERE user_id = ?');
        self::$db->executeQuery($selectQuery, ['user_id' => 2]);

        $rowData = self::$db->fetchOne($selectQuery);

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['user_id'] = Coerce::toInt($rowData['user_id']);
        $rowData['active'] = Coerce::toBool($rowData['active']);
        $rowData['create_date'] = Coerce::toInt($rowData['create_date']);
        $rowData['balance'] = \round(Coerce::toFloat($rowData['balance']), 2);

        $this->assertEquals($accountData, $rowData);
    }

    public function testSelectFetchAll(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $this->initializeDataWithDefaultTwoEntries();

        $selectQuery = self::$db->prepareQuery('SELECT * FROM ' . self::$db->quoteIdentifier('account'));
        self::$db->executeQuery($selectQuery);

        $rows = self::$db->fetchAll($selectQuery);

        $this->assertSame(2, \count($rows));

        $defaultRows = $this->getDefaultTwoEntries();

        foreach ($rows as $key => $row) {
            $defaultRows[$key]['phone'] = null;
            $defaultRows[$key]['user_id'] = $row['user_id'];

            $this->compareDataArrays($defaultRows[$key], $row);
        }

        self::$db->freeResults($selectQuery);
    }

    public function testTransactionSelectAndUpdate(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $this->initializeDataWithDefaultTwoEntries();

        self::$db->beginTransaction();

        $selectQuery = self::prepareSelectFromAccount(['user_id' => 2]);
        self::$db->executeQuery($selectQuery, ['user_id' => 2]);

        $rowData = self::$db->fetchOne($selectQuery);

        $this->assertEquals(true, $rowData['active'] ?? false);

        $updateQuery = self::prepareUpdateAccount(['active' => false], ['user_id' => 2]);
        self::$db->executeQuery($updateQuery, ['active' => false, 'user_id' => 2]);

        self::$db->commitTransaction();

        self::$db->executeQuery($selectQuery, ['user_id' => 2]);

        $rowData = self::$db->fetchOne($selectQuery);

        $this->assertEquals(false, $rowData['active'] ?? true);
    }

    public function testTransactionWithRollback(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        self::$db->beginTransaction();

        $this->initializeDataWithDefaultTwoEntries();

        self::$db->rollbackTransaction();

        $selectQuery = self::$db->prepareQuery('SELECT * FROM ' . self::$db->quoteIdentifier('account'));
        self::$db->executeQuery($selectQuery);

        $rows = self::$db->fetchAll($selectQuery);

        $this->assertSame(0, \count($rows));
    }

    public function testDelete(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $this->initializeDataWithDefaultTwoEntries();

        $deleteQuery = self::$db->prepareQuery('DELETE FROM ' . self::$db->quoteIdentifier('account') . ' WHERE user_id = ?');
        self::$db->executeQuery($deleteQuery, ['user_id' => 2]);

        $rowsAffected = self::$db->rowCount($deleteQuery);

        $this->assertEquals(1, $rowsAffected);

        $selectQuery = self::prepareSelectFromAccount(['user_id' => 2]);
        self::$db->executeQuery($selectQuery, ['user_id' => 2]);

        $rowData = self::$db->fetchOne($selectQuery);

        $this->assertEquals(null, $rowData);

        self::$db->executeQuery($deleteQuery, ['user_id' => 2]);

        $rowsAffected = self::$db->rowCount($deleteQuery);

        $this->assertEquals(0, $rowsAffected);
    }

    public function testInsertInvalidFieldnameError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.2,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => 48674935,
            'missing' => 5,
        ];

        try {
            $insertQuery = self::prepareInsertIntoAccount($accountData);
            self::$db->executeQuery($insertQuery, $accountData);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(InvalidFieldNameException::class, $e::class);
        }
    }

    public function testInsertNullInNotNullFieldError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $accountData = [
            'username' => null,
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.2,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => 48674935,
        ];

        try {
            $insertQuery = self::prepareInsertIntoAccount($accountData);
            self::$db->executeQuery($insertQuery, $accountData);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(NotNullConstraintViolationException::class, $e::class);
        }
    }

    public function testInsertDuplicateError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        self::initializeDataWithDefaultTwoEntries();

        $accountData = [
            'user_id' => 1,
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.2,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => 48674935,
        ];

        $insertQuery = self::prepareInsertIntoAccount($accountData);

        try {
            self::$db->executeQuery($insertQuery, $accountData);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(UniqueConstraintViolationException::class, $e::class);
        }
    }

    public function testInsertInNonexistentTableError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $query = 'INSERT INTO nonexistent (ladida) VALUES (?)';

        try {
            $insertQuery = self::$db->prepareQuery($query);

            self::$db->executeQuery($insertQuery, ['lulu']);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(TableNotFoundException::class, $e::class);
            $this->assertSame($query, $e->getQuery());
        }
    }

    public function testSyntaxError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        $query = 'nonsense';

        try {
            $insertQuery = self::$db->prepareQuery($query);

            self::$db->executeQuery($insertQuery, ['lulu']);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(SyntaxErrorException::class, $e::class);
            $this->assertSame($query, $e->getQuery());
        }
    }

    public function testNonUniqueFieldNameError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        try {
            $insertQuery = self::$db->prepareQuery('SELECT * FROM account a, account b WHERE user_id = ?');

            self::$db->executeQuery($insertQuery, [1]);

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(NonUniqueFieldNameException::class, $e::class);
        }
    }

    public function testDuplicateTableError(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        try {
            self::$db->prepareAndExecuteQuery(static::createAccountTableQuery());

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(TableExistsException::class, $e::class);
        }
    }

    public function testReconnect(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        self::$db->reconnect();

        $this->assertTrue(true);
    }

    protected function getDefaultTwoEntries(): array
    {
        return [
            [
                'username' => 'Mary',
                'password' => 'secret',
                'email' => 'mary@mary.com',
                'birthdate' => '1984-05-08',
                'balance' => 105.20,
                'description' => 'I am dynamic and nice!',
                'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
                'active' => true,
                'create_date' => 48674935,
            ],
            [
                'username' => 'John',
                'password' => 'othersecret',
                'email' => 'supi@mary.com',
                'birthdate' => '1984-05-08',
                'balance' => 800.0,
                'description' => 'I am dynamicer and nicer!',
                'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
                'active' => true,
                'create_date' => 486749356,
            ],
        ];
    }

    protected function initializeDataWithDefaultTwoEntries(): void
    {
        foreach ($this->getDefaultTwoEntries() as $accountData) {
            $insertQuery ??= self::prepareInsertIntoAccount($accountData);

            self::$db->executeQuery($insertQuery, $accountData);
        }
    }

    protected function compareDataArrays(array $expected, array $actual): void
    {
        $this->assertCount(\count($expected), $actual);

        $this->compareDataArraysWithoutCount($expected, $actual);
    }

    protected function compareDataArraysWithoutCount(array $expected, array $actual): void
    {
        foreach ($expected as $fieldName => $value) {
            if ($value instanceof LargeObject) {
                $value = $value->getString();
            }

            if (\is_int($value)) {
                $this->assertSame($value, Coerce::toInt($actual[$fieldName]));
            } elseif (\is_float($value)) {
                $this->assertSame($value, Coerce::toFloat($actual[$fieldName]));
            } elseif (\is_bool($value)) {
                $this->assertSame($value, Coerce::toBool($actual[$fieldName]));
            } else {
                $this->assertSame($value, $actual[$fieldName]);
            }
        }
    }
}
