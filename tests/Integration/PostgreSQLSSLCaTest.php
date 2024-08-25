<?php

namespace Squirrel\Connection\Tests\Integration;

use Squirrel\Connection\Config\Pgsql;
use Squirrel\Connection\Config\Ssl;
use Squirrel\Connection\Config\SslVerification;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Connection\Tests\Integration\Features\SchemaIdentifierTestsTrait;

/**
 * Cannot test non secure connection failure like for MySQL/MariaDB - PostgreSQL does not seem to support SSL-only access
 */
class PostgreSQLSSLCaTest extends PostgreSQLTest
{
    use SchemaIdentifierTestsTrait;

    protected static function waitUntilThisDatabaseReady(): void
    {
        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES'] . '_ssl', 5432);
    }

    protected static function getConnection(): ConnectionInterface
    {
        return new ConnectionPDO(
            new Pgsql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES'] . '_ssl_ca',
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
                ssl: new Ssl(
                    certificatePath: $_SERVER['SQUIRREL_CONNECTION_SSL_CERT'],
                    privateKeyPath: $_SERVER['SQUIRREL_CONNECTION_SSL_KEY'],
                    rootCertificatePath: $_SERVER['SQUIRREL_CONNECTION_SSL_CA'],
                    verification: SslVerification::Ca,
                ),
            ),
        );
    }
}
