<?php

namespace Squirrel\Connection;

/** @internal */
interface ConnectionQueryInterface
{
    public function getQuery(): string;
}
