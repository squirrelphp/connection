<?php

namespace Squirrel\Connection\Config;

final readonly class Pgsql
{
    public function __construct(
        public string $host,
        public string $user,
        #[\SensitiveParameter] public string $password,
        public int $port = 5432,
        public ?string $dbname = null,
        public string $charset = 'UTF8',
        public ?Ssl $ssl = null,
    ) {
    }
}
