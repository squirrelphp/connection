<?php

namespace Squirrel\Connection\ExceptionConverter\SQLite;

use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\Exception\ForeignKeyConstraintViolationException;
use Squirrel\Connection\Exception\InvalidFieldNameException;
use Squirrel\Connection\Exception\LockWaitTimeoutException;
use Squirrel\Connection\Exception\NonUniqueFieldNameException;
use Squirrel\Connection\Exception\NotNullConstraintViolationException;
use Squirrel\Connection\Exception\ReadOnlyException;
use Squirrel\Connection\Exception\SyntaxErrorException;
use Squirrel\Connection\Exception\TableExistsException;
use Squirrel\Connection\Exception\TableNotFoundException;
use Squirrel\Connection\Exception\UniqueConstraintViolationException;
use Squirrel\Connection\ExceptionConverter\ExceptionConverterInterface;

use function str_contains;

/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /** @link http://www.sqlite.org/c3ref/c_abort.html */
    public function convert(\PDOException $exception, ?string $query = null): DriverException
    {
        if ($exception->errorInfo !== null) {
            [$sqlState, $code] = $exception->errorInfo;
        } else {
            \trigger_error('No errorInfo available for PDOException', E_USER_WARNING);
            $sqlState = '';
        }

        if (str_contains($exception->getMessage(), 'database is locked')) {
            return new LockWaitTimeoutException($exception, $sqlState, $query);
        }

        if (
            str_contains($exception->getMessage(), 'must be unique') ||
            str_contains($exception->getMessage(), 'is not unique') ||
            str_contains($exception->getMessage(), 'are not unique') ||
            str_contains($exception->getMessage(), 'UNIQUE constraint failed')
        ) {
            return new UniqueConstraintViolationException($exception, $sqlState, $query);
        }

        if (
            str_contains($exception->getMessage(), 'may not be NULL') ||
            str_contains($exception->getMessage(), 'NOT NULL constraint failed')
        ) {
            return new NotNullConstraintViolationException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'no such table:')) {
            return new TableNotFoundException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'already exists')) {
            return new TableExistsException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'has no column named')) {
            return new InvalidFieldNameException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'ambiguous column name')) {
            return new NonUniqueFieldNameException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'syntax error')) {
            return new SyntaxErrorException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'attempt to write a readonly database')) {
            return new ReadOnlyException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'unable to open database file')) {
            return new ConnectionException($exception, $sqlState, $query);
        }

        if (str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
            return new ForeignKeyConstraintViolationException($exception, $sqlState, $query);
        }

        return new DriverException($exception, $sqlState, $query);
    }
}
