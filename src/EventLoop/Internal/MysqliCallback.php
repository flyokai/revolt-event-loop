<?php

namespace Revolt\EventLoop\Internal;

class MysqliCallback extends DriverCallback
{
    /**
     * @param \mysqli $mysqli
     */
    public function __construct(
        string $id,
        \Closure $closure,
        public readonly \mysqli $mysqli,
        public readonly int $streamId
    ) {
        parent::__construct($id, $closure);
    }
}
