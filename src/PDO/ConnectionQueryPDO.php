<?php

namespace Squirrel\Connection\PDO;

use PDOStatement;
use Squirrel\Connection\ConnectionQueryInterface;

readonly class ConnectionQueryPDO implements ConnectionQueryInterface
{
    public function __construct(
        private PDOStatement $statement,
        private string $query,
    ) {
    }

    public function getPDOStatement(): PDOStatement
    {
        return $this->statement;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
