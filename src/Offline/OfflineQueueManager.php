<?php

namespace Punchkick\QueueManager\Offline;

use BadMethodCallException;
use Punchkick\QueueManager\JobHandlerInterface;
use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\QueueManagerInterface;

/**
 * Class OfflineQueueManager
 * @package Punchkick\QueueManager\Offline
 */
class OfflineQueueManager implements QueueManagerInterface
{
    /**
     * @var JobHandlerInterface[]
     */
    protected $jobHandlers;

    /**
     * OfflineQueueManager constructor.
     *
     * @param JobHandlerInterface[] $jobHandlers
     */
    public function __construct(array $jobHandlers)
    {
        $this->jobHandlers = $jobHandlers;
    }

    /**
     * @param string $jobName
     * @param array $jobData
     *
     * @return bool
     */
    public function addJob(string $jobName, array $jobData): bool
    {
        $job = new OfflineJob($jobData);

        foreach ($this->jobHandlers as $jobHandler) {
            if ($jobHandler::getJobName() === $jobName) {
                return $jobHandler->handle($job);
            };
        }

        return false;
    }

    /**
     * @param string $jobName
     *
     * @return JobInterface
     */
    public function getJob(string $jobName): JobInterface
    {
        throw new BadMethodCallException('Offline Queue does not have anywhere to pull jobs from');
    }

    /**
     * @param string $jobName
     *
     * @return bool
     */
    public function purgeJobs(string $jobName): bool
    {
        throw new BadMethodCallException('Offline Queue does not have anything to purge');
    }
}
