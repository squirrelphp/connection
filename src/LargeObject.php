<?php

namespace Squirrel\Connection;

/**
 * Large object - mainly necessary for Postgres, to support correctly saving BYTEA values
 */
readonly class LargeObject
{
    public function __construct(
        private string $data,
    ) {
    }

    public function getString(): string
    {
        return $this->data;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $fp = \fopen('php://temp', 'rb+');

        // @codeCoverageIgnoreStart
        if ($fp === false) {
            throw new \UnexpectedValueException('fopen with php://temp was surprisingly unsuccessful');
        }
        // @codeCoverageIgnoreEnd

        \fwrite($fp, $this->data);
        \fseek($fp, 0);

        return $fp;
    }
}
