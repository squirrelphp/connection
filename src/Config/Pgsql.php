<?php

namespace Squirrel\Connection\Config;

final readonly class Pgsql
{
    public const DEFAULT_PORT = 5432;
    public const DEFAULT_CHARSET = 'UTF8';

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
