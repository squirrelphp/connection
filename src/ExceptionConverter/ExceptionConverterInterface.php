<?php

namespace Squirrel\Connection\ExceptionConverter;

use Squirrel\Connection\Exception\DriverException;

/** @internal */
interface ExceptionConverterInterface
{
    public function convert(\PDOException $exception, ?string $query = null): DriverException;
}
