<?php

namespace Squirrel\Connection\Config;

final readonly class Ssl
{
    public function __construct(
        public string $rootCertificatePath,
        public string $privateKeyPath,
        public string $certificatePath,
    ) {
    }
}
