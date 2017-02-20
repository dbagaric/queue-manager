<?php

namespace Punchkick\QueueManager;

/**
 * Interface JobInterface
 * @package Punchkick\QueueManager
 */
interface JobInterface
{
    /**
     * @return bool
     */
    public function markProcessing(): bool;

    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @return bool
     */
    public function markDone(): bool;

    /**
     * @return bool
     */
    public function markFailed(): bool;
}