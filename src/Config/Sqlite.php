<?php

namespace Squirrel\Connection\Config;

final readonly class Sqlite
{
    public function __construct(
        public ?string $path = null,
    ) {
    }
}
