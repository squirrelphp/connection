<?php

namespace Squirrel\Connection\Exception;

/**
 * Base class for all errors detected in the driver.
 *
 * @psalm-immutable
 */
class DriverException extends \Exception
{
    /**
     * @internal
     */
    public function __construct(
        \PDOException $driverException,
        private readonly string $sqlState,
        private readonly ?string $query = null,
    ) {
        if ($this->query !== null) {
            $message = 'An exception occurred while executing a query: ' . $driverException->getMessage();
        } else {
            $message = 'An exception occurred in the driver: ' . $driverException->getMessage();
        }

        parent::__construct(
            message: $message,
            code: 1337,
            previous: $driverException,
        );
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getSqlState(): string
    {
        return $this->sqlState;
    }
}
