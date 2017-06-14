<?php

namespace Punchkick\QueueManager\Disque;

use Disque\Client;
use Disque\Queue\Job;
use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\QueueManagerInterface;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;

/**
 * Class DisqueQueueManager
 * @package Punchkick\QueueManager\Disque
 */
class DisqueQueueManager implements QueueManagerInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var DoneLogInterface
     */
    protected $doneLog;

    /**
     * DisqueQueueManager constructor.
     * @param Client $client
     */
    public function __construct(Client $client, DoneLogInterface $doneLog)
    {
        $this->client = $client;
        $this->doneLog = $doneLog;
    }

    /**
     * @param string $jobName
     * @param array $jobData
     * @return bool
     */
    public function addJob(string $jobName, array $jobData): bool
    {
        $job = new Job($jobData);

        $queue = $this->client->queue($jobName);
        $queue->push($job, [
            'ttl' => 604800,
            'async' => true
        ]); // 1 week

        return true;
    }

    /**
     * @param string $jobName
     * @return JobInterface
     * @throws EmptyQueueException
     */
    public function getJob(string $jobName): JobInterface
    {
        $queue = $this->client->queue($jobName);
        $disqueJob = $queue->pull(1000); // 1 second

        if (!$disqueJob) {
            throw new EmptyQueueException;
        }

        $job = new DisqueJob($queue, $disqueJob, $this->doneLog);

        if ($this->doneLog->hasJob($disqueJob->getId())) {
            $job->markDone();

            throw new EmptyQueueException;
        }

        return $job;
    }
}