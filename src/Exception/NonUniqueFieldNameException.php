<?php

namespace Squirrel\Connection\Exception;

/**
 * Exception for a non-unique/ambiguous specified field name in a statement detected in the driver.
 *
 * @psalm-immutable
 */
class NonUniqueFieldNameException extends ServerException
{
}
