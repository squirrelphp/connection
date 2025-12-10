<?php

namespace Squirrel\Connection\PDO;

use PDO;
use Pdo\Mysql as PdoMysql;
use Pdo\Pgsql as PdoPgsql;
use PDOException;
use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\Config\Pgsql;
use Squirrel\Connection\Config\Sqlite;
use Squirrel\Connection\Config\SslVerification;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\ConnectionQueryInterface;
use Squirrel\Connection\Exception\InvalidArgumentException;
use Squirrel\Connection\ExceptionConverter\ExceptionConverterInterface;
use Squirrel\Connection\LargeObject;

final class ConnectionPDO implements ConnectionInterface
{
    private PDO $pdo; // not readonly because can be recreated to reconnect
    private bool $connected = false;

    /** @var array<int, scalar> */
    private readonly array $options;
    private readonly string $dsn;
    private readonly ExceptionConverterInterface $exceptionConverter;

    public function __construct(
        private readonly Mysql|Pgsql|Sqlite $config,
    ) {
        $options = [];
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_EMULATE_PREPARES] = false;
        $options[PDO::ATTR_AUTOCOMMIT] = true;

        if ($this->config instanceof Mysql) {
            $options[PdoMysql::ATTR_MULTI_STATEMENTS] = false;
            $options[PdoMysql::ATTR_FOUND_ROWS] = true;

            if ($this->config->ssl !== null) {
                $options[PdoMysql::ATTR_SSL_CA] = $this->config->ssl->rootCertificatePath;
                $options[PdoMysql::ATTR_SSL_KEY] = $this->config->ssl->privateKeyPath;
                $options[PdoMysql::ATTR_SSL_CERT] = $this->config->ssl->certificatePath;

                $options[PdoMysql::ATTR_SSL_VERIFY_SERVER_CERT] = match ($this->config->ssl->verification) {
                    SslVerification::None => false,
                    SslVerification::Ca => throw new InvalidArgumentException('Mysql SSL connections do not support to only verify the CA - only no verification or both CA and hostname verification are supported by the PHP driver'),
                    SslVerification::CaAndHostname => true,
                };
            }
        }

        if ($this->config instanceof Pgsql) {
            $options[PdoPgsql::ATTR_DISABLE_PREPARES] = false;
        }

        $this->options = $options;

        $this->exceptionConverter = match ($this->config::class) {
            Mysql::class => new \Squirrel\Connection\ExceptionConverter\MySQL\ExceptionConverter(),
            Pgsql::class => new \Squirrel\Connection\ExceptionConverter\PostgreSQL\ExceptionConverter(),
            Sqlite::class => new \Squirrel\Connection\ExceptionConverter\SQLite\ExceptionConverter(),
        };

        if ($this->config instanceof Pgsql) {
            $sslmode = match ($this->config->ssl?->verification) {
                null => 'prefer',
                SslVerification::None => 'require',
                SslVerification::Ca => 'verify-ca',
                SslVerification::CaAndHostname => 'verify-full',
            };

            $this->dsn = 'pgsql:host=' . $this->config->host . ';port=' . $this->config->port . ( $this->config->dbname !== null ? ';dbname=' . $this->config->dbname : '' ) . ';options=\'--client_encoding=' . $this->config->charset . '\'' . ';sslmode=' . $sslmode . ( $this->config->ssl !== null ? ';sslcert=' . $this->config->ssl->certificatePath . ';sslkey=' . $this->config->ssl->privateKeyPath . ';sslrootcert=' . $this->config->ssl->rootCertificatePath : '' );
        } elseif ($this->config instanceof Sqlite) {
            $this->dsn = 'sqlite:' . ( $this->config->path !== null ? $this->config->path : ':memory:' );
        } else {
            $this->dsn = 'mysql:host=' . $this->config->host . ';port=' . $this->config->port . ( $this->config->dbname !== null ? ';dbname=' . $this->config->dbname : '' ) . ';charset=' . $this->config->charset;
        }
    }

    private function connect(): void
    {
        try {
            if ($this->config instanceof Sqlite) {
                $this->pdo = new \PDO(
                    dsn: $this->dsn,
                    options: $this->options,
                );
            } else {
                $this->pdo = new \PDO(
                    dsn: $this->dsn,
                    username: $this->config->user,
                    password: $this->config->password,
                    options: $this->options,
                );
            }

            $this->connected = true;
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e);
        }
    }

    private function connectIfNecessary(): void
    {
        if (!$this->connected) {
            $this->connect();
        }
    }

    public function beginTransaction(): void
    {
        $this->connectIfNecessary();

        try {
            $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e);
        }
    }

    public function commitTransaction(): void
    {
        $this->connectIfNecessary();

        try {
            $this->pdo->commit();
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e);
        }
    }

    public function rollbackTransaction(): void
    {
        $this->connectIfNecessary();

        try {
            $this->pdo->rollBack();
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e);
        }
    }

    public function prepareQuery(string $query): ConnectionQueryPDO
    {
        $this->connectIfNecessary();

        try {
            return new ConnectionQueryPDO($this->pdo->prepare($query), $query);
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e, $query);
        }
    }

    public function executeQuery(ConnectionQueryInterface $query, array $values = []): void
    {
        $this->connectIfNecessary();

        $this->validateConnectionQueryType($query);

        try {
            $statement = $query->getPDOStatement();

            $paramCounter = 1;
            foreach ($values as $columnValue) {
                if (\is_bool($columnValue)) {
                    $columnValue = \intval($columnValue);
                }

                $statement->bindValue(
                    $paramCounter++,
                    ($columnValue instanceof LargeObject) ? $columnValue->getStream() : $columnValue,
                    ($columnValue instanceof LargeObject) ? \PDO::PARAM_LOB : \PDO::PARAM_STR,
                );
            }

            $statement->execute();
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e, $query->getQuery());
        }
    }

    public function fetchOne(ConnectionQueryInterface $query): ?array
    {
        $this->connectIfNecessary();

        $this->validateConnectionQueryType($query);

        try {
            $result = $query->getPDOStatement()->fetch(PDO::FETCH_ASSOC);

            if ($result === false) {
                return null;
            }

            return $this->resolveStreamsinEntry($result);
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e, $query->getQuery());
        }
    }

    public function fetchAll(ConnectionQueryInterface $query): array
    {
        $this->connectIfNecessary();

        $this->validateConnectionQueryType($query);

        try {
            $results = $query->getPDOStatement()->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $key => $result) {
                $results[$key] = $this->resolveStreamsinEntry($result);
            }

            return $results;
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e, $query->getQuery());
        }
    }

    private function resolveStreamsinEntry(array $entry): array
    {
        foreach ($entry as $key => $value) {
            if (\is_resource($value)) {
                $entry[$key] = \stream_get_contents($value);
            }
        }

        return $entry;
    }

    public function freeResults(ConnectionQueryInterface $query): void
    {
        $this->connectIfNecessary();

        $this->validateConnectionQueryType($query);

        try {
            $query->getPDOStatement()->closeCursor();
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e, $query->getQuery());
        }
    }

    public function rowCount(ConnectionQueryInterface $query): int
    {
        $this->connectIfNecessary();

        $this->validateConnectionQueryType($query);

        try {
            return $query->getPDOStatement()->rowCount();
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e, $query->getQuery());
        }
    }

    public function lastInsertId(): string
    {
        $this->connectIfNecessary();

        try {
            return \strval($this->pdo->lastInsertId());
        } catch (PDOException $e) {
            throw $this->exceptionConverter->convert($e);
        }
    }

    public function reconnect(): void
    {
        $this->connect();
    }

    public function quoteIdentifier(string $identifier): string
    {
        if (\str_contains($identifier, '.')) {
            return \implode('.', \array_map([$this, 'quoteSingleIdentifier'], \explode('.', $identifier)));
        }

        return $this->quoteSingleIdentifier($identifier);
    }

    private function quoteSingleIdentifier(string $identifier): string
    {
        $quoteCharacter = $this->getQuoteCharacter();

        return $quoteCharacter . \str_replace($quoteCharacter, $quoteCharacter . $quoteCharacter, $identifier) . $quoteCharacter;
    }

    private function getQuoteCharacter(): string
    {
        return match ($this->config::class) {
            Mysql::class => '`',
            default => '"',
        };
    }

    /** @phpstan-assert ConnectionQueryPDO $query */
    private function validateConnectionQueryType(ConnectionQueryInterface $query): void
    {
        if (!$query instanceof ConnectionQueryPDO) {
            throw new InvalidArgumentException('Invalid query class provided');
        }
    }
}
