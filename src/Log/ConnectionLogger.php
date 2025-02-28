<?php

namespace Squirrel\Connection\Log;

use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\ConnectionQueryInterface;
use Squirrel\Connection\LargeObject;

/*
 * Logs executed queries - could log more things in the future, for now the queries seem the most important
 */
final class ConnectionLogger implements ConnectionInterface
{
    /** @var list<array{query: string, values: array<int|float|string|bool|null|LargeObject>, time: int}> */
    private array $logs = [];

    public function __construct(
        private readonly ConnectionInterface $implementation,
    ) {
    }

    public function beginTransaction(): void
    {
        $this->implementation->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->implementation->commitTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->implementation->rollbackTransaction();
    }

    public function prepareQuery(string $query): ConnectionQueryInterface
    {
        return $this->implementation->prepareQuery($query);
    }

    public function executeQuery(ConnectionQueryInterface $query, array $values = []): void
    {
        $start = \hrtime(true);

        $this->implementation->executeQuery($query, $values);

        $measured = \hrtime(true) - $start;

        // Log query as a string, values (query parameters) as an array of values, and time in nanoseconds
        $this->logs[] = [
            'query' => $query->getQuery(),
            'values' => $values,
            'time' => $measured,
        ];
    }

    public function fetchOne(ConnectionQueryInterface $query): ?array
    {
        return $this->implementation->fetchOne($query);
    }

    public function fetchAll(ConnectionQueryInterface $query): array
    {
        return $this->implementation->fetchAll($query);
    }

    public function freeResults(ConnectionQueryInterface $query): void
    {
        $this->implementation->freeResults($query);
    }

    public function rowCount(ConnectionQueryInterface $query): int
    {
        return $this->implementation->rowCount($query);
    }

    public function lastInsertId(): string
    {
        return $this->implementation->lastInsertId();
    }

    public function reconnect(): void
    {
        $this->implementation->reconnect();
    }

    public function quoteIdentifier(string $identifier): string
    {
        return $this->implementation->quoteIdentifier($identifier);
    }

    /** @return list<array{query: string, values: array<int|float|string|bool|null|LargeObject>, time: int}> */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function resetLogs(): void
    {
        $this->logs = [];
    }
}
