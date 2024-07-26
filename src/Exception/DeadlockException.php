<?php

namespace Squirrel\Connection\Exception;

/**
 * Exception for a deadlock error of a transaction detected in the driver.
 *
 * @psalm-immutable
 */
class DeadlockException extends ServerException implements RetryableExceptionInterface
{
}
