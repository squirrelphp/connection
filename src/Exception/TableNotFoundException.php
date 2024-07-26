<?php

namespace Squirrel\Connection\Exception;

/**
 * Exception for an unknown table referenced in a statement detected in the driver.
 *
 * @psalm-immutable
 */
class TableNotFoundException extends DatabaseObjectNotFoundException
{
}
