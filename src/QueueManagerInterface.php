<?php

namespace Punchkick\QueueManager;

/**
 * Interface QueueManagerInterface
 * @package Punchkick\QueueManager
 */
interface QueueManagerInterface
{
    /**
     * @param string $jobName
     * @param array $jobData
     * @return bool
     */
    public function addJob(string $jobName, array $jobData): bool;

    /**
     * @param string $jobName
     * @return JobInterface
     */
    public function getJob(string $jobName): JobInterface;
}