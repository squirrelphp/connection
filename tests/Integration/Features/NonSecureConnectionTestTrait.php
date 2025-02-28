<?php

namespace Squirrel\Connection\Tests\Integration\Features;

use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\Config\Pgsql;
use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\PDO\ConnectionPDO;

trait NonSecureConnectionTestTrait
{
    private function nonSecureConnectionMustFail(Mysql|Pgsql $config): void
    {
        try {
            $connection = new ConnectionPDO($config);
            $connection->reconnect();

            $this->fail('No exception was thrown');
        } catch (DriverException $e) {
            $this->assertSame(ConnectionException::class, $e::class);
        }
    }
}
