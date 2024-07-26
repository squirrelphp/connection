<?php

namespace Squirrel\Connection\Config;

/**
 * Covers both MySQL and MariaDB settings
 */
final readonly class Mysql
{
    public function __construct(
        public string $host,
        public string $user,
        #[\SensitiveParameter] public string $password,
        public int $port = 3306,
        public ?string $dbname = null,
        public string $charset = 'utf8mb4',
        public ?Ssl $ssl = null,
    ) {
    }
}
