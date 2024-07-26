<?php

namespace Squirrel\Connection\Exception;

use Throwable;

/**
 * Marker interface for all exceptions where retrying a query/transaction makes sense
 *
 * @psalm-immutable
 */
interface RetryableExceptionInterface extends Throwable
{
}
