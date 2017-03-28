<?php

namespace Punchkick\QueueManager\Disque;

use Disque\Connection\Response\ResponseException;
use Disque\Queue\JobInterface as DisqueJobInterface;
use Disque\Queue\Queue;
use Punchkick\QueueManager\JobInterface;

/**
 * Class DisqueJob
 * @package Punchkick\QueueManager\Disque
 */
class DisqueJob implements JobInterface
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var DisqueJobInterface
     */
    protected $job;

    /**
     * DisqueJob constructor.
     * @param Queue $queue
     * @param DisqueJobInterface $job
     */
    public function __construct(Queue $queue, DisqueJobInterface $job)
    {
        $this->queue = $queue;
        $this->job = $job;
    }

    /**
     * @return bool
     */
    public function markProcessing(): bool
    {
        try {
            $this->queue->processing($this->job);
        } catch (ResponseException $e) {
            // this can occur if the job has already reach 50% TTL
            // it's a strange limitation of disque, but can be safely
            // ignored here
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $body = $this->job->getBody();

        if (!is_array($body)) {
            $body = (array)$body;
        }

        return $body;
    }

    /**
     * @return bool
     */
    public function markDone(): bool
    {
        $this->queue->processed($this->job);

        return true;
    }

    /**
     * @return bool
     */
    public function markFailed(): bool
    {
        $this->queue->failed($this->job);

        return true;
    }

}