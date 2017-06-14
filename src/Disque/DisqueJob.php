<?php

namespace Punchkick\QueueManager\Disque;

use Disque\Connection\Response\ResponseException;
use Disque\Queue\JobInterface as DisqueJobInterface;
use Disque\Queue\Queue;
use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;

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
     * @var DoneLogInterface
     */
    protected $doneLog;

    /**
     * DisqueJob constructor.
     * @param Queue $queue
     * @param DisqueJobInterface $job
     */
    public function __construct(
        Queue $queue,
        DisqueJobInterface $job,
        DoneLogInterface $doneLog
    ) {
        $this->queue = $queue;
        $this->job = $job;
        $this->doneLog = $doneLog;
    }

    /**
     * @return bool
     */
    public function markProcessing(): bool
    {
        try {
            $this->queue->processing($this->job);

            return true;
        } catch (ResponseException $e) {
            // this can occur if the job has already reach 50% TTL
            // it's a strange limitation of disque, but can be safely
            // ignored here
            return false;
        }
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

        $this->doneLog->logJob($this->job->getId());

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
