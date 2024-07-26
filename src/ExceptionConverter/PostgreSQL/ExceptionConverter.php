<?php

namespace Squirrel\Connection\ExceptionConverter\PostgreSQL;

use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\DatabaseDoesNotExist;
use Squirrel\Connection\Exception\DeadlockException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\Exception\ForeignKeyConstraintViolationException;
use Squirrel\Connection\Exception\InvalidFieldNameException;
use Squirrel\Connection\Exception\NoIdentityValue;
use Squirrel\Connection\Exception\NonUniqueFieldNameException;
use Squirrel\Connection\Exception\NotNullConstraintViolationException;
use Squirrel\Connection\Exception\SchemaDoesNotExist;
use Squirrel\Connection\Exception\SyntaxErrorException;
use Squirrel\Connection\Exception\TableExistsException;
use Squirrel\Connection\Exception\TableNotFoundException;
use Squirrel\Connection\Exception\UniqueConstraintViolationException;
use Squirrel\Connection\ExceptionConverter\ExceptionConverterInterface;

use function str_contains;

/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /** @link http://www.postgresql.org/docs/9.4/static/errcodes-appendix.html */
    public function convert(\PDOException $exception, ?string $query = null): DriverException
    {
        if ($exception->errorInfo !== null) {
            [$sqlState, $code] = $exception->errorInfo;
        } else {
            \trigger_error('No errorInfo available for PDOException', E_USER_WARNING);
            $sqlState = '';
        }

        if ($sqlState === '0A000' && str_contains($exception->getMessage(), 'truncate')) {
            return new ForeignKeyConstraintViolationException($exception, $sqlState, $query);
        }

        return match ($sqlState) {
            '40001', '40P01' => new DeadlockException($exception, $sqlState, $query),
            '23502' => new NotNullConstraintViolationException($exception, $sqlState, $query),
            '23503' => new ForeignKeyConstraintViolationException($exception, $sqlState, $query),
            '23505' => new UniqueConstraintViolationException($exception, $sqlState, $query),
            '3D000' => new DatabaseDoesNotExist($exception, $sqlState, $query),
            '3F000' => new SchemaDoesNotExist($exception, $sqlState, $query),
            '42601' => new SyntaxErrorException($exception, $sqlState, $query),
            '42702' => new NonUniqueFieldNameException($exception, $sqlState, $query),
            '42703' => new InvalidFieldNameException($exception, $sqlState, $query),
            '42P01' => new TableNotFoundException($exception, $sqlState, $query),
            '42P07' => new TableExistsException($exception, $sqlState, $query),
            '08006' => new ConnectionException($exception, $sqlState, $query),
            '55000' => new NoIdentityValue($exception, $sqlState, $query),
            default => new DriverException($exception, $sqlState, $query),
        };
    }
}
