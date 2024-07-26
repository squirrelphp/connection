<?php

namespace Squirrel\Connection;

/**
 * Connection interface providing the minimum possible surface for using a database connection
 */
interface ConnectionInterface
{
    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;

    public function prepareQuery(string $query): ConnectionQueryInterface;

    /** @param array<scalar|LargeObject> $values */
    public function executeQuery(ConnectionQueryInterface $query, array $values = []): void;

    /** @param array<scalar|LargeObject> $values */
    public function prepareAndExecuteQuery(string $query, array $values = []): ConnectionQueryInterface;

    /** @return array<string, scalar|null>|null */
    public function fetchOne(ConnectionQueryInterface $query): ?array;

    /** @return list<array<string, scalar|null>> */
    public function fetchAll(ConnectionQueryInterface $query): array;

    public function freeResults(ConnectionQueryInterface $query): void;

    public function rowCount(ConnectionQueryInterface $query): int;

    public function lastInsertId(): string;

    public function reconnect(): void;

    public function quoteIdentifier(string $identifier): string;
}
