<?php

namespace Punchkick\QueueManager\Disque;

use Disque\Client;
use Disque\Queue\Job;
use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\QueueManagerInterface;

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
     * DisqueQueueManager constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $queue->push($job);

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
        $job = $queue->pull(3);

        if (!$job) {
            throw new EmptyQueueException;
        }

        return new DisqueJob($queue, $job);
    }
}