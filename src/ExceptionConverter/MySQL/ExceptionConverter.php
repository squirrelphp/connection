<?php

namespace Squirrel\Connection\ExceptionConverter\MySQL;

use Squirrel\Connection\Exception\AuthorizationException;
use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\ConnectionLost;
use Squirrel\Connection\Exception\DatabaseDoesNotExist;
use Squirrel\Connection\Exception\DeadlockException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Connection\Exception\ForeignKeyConstraintViolationException;
use Squirrel\Connection\Exception\InvalidFieldNameException;
use Squirrel\Connection\Exception\LockWaitTimeoutException;
use Squirrel\Connection\Exception\NonUniqueFieldNameException;
use Squirrel\Connection\Exception\NotNullConstraintViolationException;
use Squirrel\Connection\Exception\SyntaxErrorException;
use Squirrel\Connection\Exception\TableExistsException;
use Squirrel\Connection\Exception\TableNotFoundException;
use Squirrel\Connection\Exception\UniqueConstraintViolationException;
use Squirrel\Connection\ExceptionConverter\ExceptionConverterInterface;

/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /**
     * @link https://dev.mysql.com/doc/mysql-errors/8.0/en/client-error-reference.html
     * @link https://dev.mysql.com/doc/mysql-errors/8.0/en/server-error-reference.html
     */
    public function convert(\PDOException $exception, ?string $query = null): DriverException
    {
        if ($exception->errorInfo !== null) {
            [$sqlState, $code] = $exception->errorInfo;
        } else {
            \trigger_error('No errorInfo available for PDOException', E_USER_WARNING);

            $code     = $exception->getCode();
            $sqlState = '';
        }

        return match ($code) {
            1008,
            1049 => new DatabaseDoesNotExist($exception, $sqlState, $query),
            1213 => new DeadlockException($exception, $sqlState, $query),
            1205 => new LockWaitTimeoutException($exception, $sqlState, $query),
            1050 => new TableExistsException($exception, $sqlState, $query),
            1051,
            1146 => new TableNotFoundException($exception, $sqlState, $query),
            1216,
            1217,
            1451,
            1452,
            1701 => new ForeignKeyConstraintViolationException($exception, $sqlState, $query),
            1062,
            1557,
            1569,
            1586 => new UniqueConstraintViolationException($exception, $sqlState, $query),
            1054,
            1166,
            1611 => new InvalidFieldNameException($exception, $sqlState, $query),
            1052,
            1060,
            1110 => new NonUniqueFieldNameException($exception, $sqlState, $query),
            1064,
            1149,
            1287,
            1341,
            1342,
            1343,
            1344,
            1382,
            1479,
            1541,
            1554,
            1626 => new SyntaxErrorException($exception, $sqlState, $query),
            1044,
            1045,
            1129,
            1130,
            1133 => new AuthorizationException($exception, $sqlState, $query),
            1046,
            1095,
            1142,
            1143,
            1227,
            1370,
            1429,
            2002,
            2005,
            2054,
            3159 => new ConnectionException($exception, $sqlState, $query),
            2006 => new ConnectionLost($exception, $sqlState, $query),
            1048,
            1121,
            1138,
            1171,
            1252,
            1263,
            1364,
            1566 => new NotNullConstraintViolationException($exception, $sqlState, $query),
            default => new DriverException($exception, $sqlState, $query),
        };
    }
}
