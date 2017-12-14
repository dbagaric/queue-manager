<?php

namespace Punchkick\QueueManager;

/**
 * Interface QueueManagerInterface
 * @package Punchkick\QueueManager
 */
interface QueueManagerInterface
{
    /**
     * Add a job to their respective queue
     *
     * @param string $jobName
     * @param array $jobData
     *
     * @return bool
     */
    public function addJob(string $jobName, array $jobData): bool;

    /**
     * Pull a job off their respective queue
     *
     * @param string $jobName
     *
     * @return JobInterface
     */
    public function getJob(string $jobName): JobInterface;

    /**
     * Purge the queue of a given job
     *
     * @param string $jobName
     *
     * @return bool
     */
    public function purgeJobs(string $jobName): bool;
}