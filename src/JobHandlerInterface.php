<?php

namespace Punchkick\QueueManager;

/**
 * Interface JobHandlerInterface
 * @package Punchkick\QueueManager\JobHandler
 */
interface JobHandlerInterface
{
    /**
     * @return string
     */
    public static function getJobName(): string;

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    public function handle(JobInterface $job): bool;
}