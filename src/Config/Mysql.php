<?php

namespace Squirrel\Connection\Config;

/**
 * Covers both MySQL and MariaDB settings
 */
final readonly class Mysql
{
    public const DEFAULT_PORT = 3306;
    public const DEFAULT_CHARSET = 'utf8mb4';

    public function __construct(
        public string $host,
        public string $user,
        #[\SensitiveParameter] public string $password,
        public int $port = self::DEFAULT_PORT,
        public ?string $dbname = null,
        public string $charset = self::DEFAULT_CHARSET,
        public ?Ssl $ssl = null,
    ) {
    }
}
