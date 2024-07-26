<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\Config\Ssl;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Connection\Tests\Integration\Features\NonSecureConnectionTestTrait;

class MariaDBSSLTest extends MySQLTest
{
    use NonSecureConnectionTestTrait;

    protected static function waitUntilThisDatabaseReady(): void
    {
        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'] . '_ssl', 3306);
    }

    protected static function getConnection(): ConnectionInterface
    {
        return new ConnectionPDO(
            new Mysql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'] . '_ssl',
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
                ssl: new Ssl(
                    certificatePath: $_SERVER['SQUIRREL_CONNECTION_SSL_CERT'],
                    privateKeyPath: $_SERVER['SQUIRREL_CONNECTION_SSL_KEY'],
                    rootCertificatePath: $_SERVER['SQUIRREL_CONNECTION_SSL_CA'],
                ),
            ),
        );
    }

    public function testNonSecureConnection(): void
    {
        $this->nonSecureConnectionMustFail(new Mysql(
            host: $_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'] . '_ssl',
            user: $_SERVER['SQUIRREL_CONNECTION_USER'],
            password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
            dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
        ));
    }
}
