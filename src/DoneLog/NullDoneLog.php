<?php

namespace Punchkick\QueueManager\DoneLog;

use Punchkick\QueueManager\DoneLog\DoneLogInterface;

class NullDoneLog implements DoneLogInterface
{
    /**
     * Logs that a job has been performed
     * @param  mixed $id
     * @return bool
     */
    public function logJob($id): bool
    {
        return true;
    }

    /**
     * Checks if a job has been sent
     * @param  mixed $id
     * @return bool
     */
    public function hasJob($id): bool
    {
        return false;
    }
}
