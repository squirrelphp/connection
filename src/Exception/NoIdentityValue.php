<?php

namespace Squirrel\Connection\Exception;

/**
 * Exception when no identity value is available when getting lastInsertId
 *
 * @psalm-immutable
 */
class NoIdentityValue extends ServerException
{
}
