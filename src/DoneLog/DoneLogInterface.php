<?php

namespace Punchkick\QueueManager\DoneLog;

/**
 * Interface DoneLogInterface
 * @package Punchkick\QueueManager\DoneLog
 */
interface DoneLogInterface
{
    /**
     * Logs that a job has been performed
     *
     * @param  mixed $id
     *
     * @return bool
     */
    public function logJob($id): bool;

    /**
     * Checks if a job has been sent
     *
     * @param  mixed $id
     *
     * @return bool
     */
    public function hasJob($id): bool;
}