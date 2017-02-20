<?php

namespace Punchkick\QueueManager;

use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\JobHandlerInterface;

/**
 * Class Worker
 * @package Punchkick\QueueManager
 */
class Worker
{
    /**
     * @var QueueManagerInterface
     */
    protected $queueManager;

    /**
     * @var JobHandlerInterface[]
     */
    protected $jobHandlers;

    /**
     * @param QueueManagerInterface $queueManager
     */
    public function __construct(QueueManagerInterface $queueManager)
    {
        pcntl_signal(SIGHUP, [$this, 'hupSignalHandler']);

        $this->queueManager = $queueManager;
        $this->jobHandlers = [];
    }

    /**
     * @return void
     */
    public function hupSignalHandler(): void
    {
        exit('HUP signal received');
    }

    /**
     * @param JobHandlerInterface $jobHandler
     */
    public function addJobHandler(JobHandlerInterface $jobHandler): void
    {
        $this->jobHandlers[] = $jobHandler;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        while (true) {
            $this->loopThroughHandlers();
        }
    }

    /**
     * @return void
     */
    private function loopThroughHandlers(): void
    {
        foreach ($this->jobHandlers as $jobHandler) {
            pcntl_signal_dispatch();
            $this->useJobHandler($jobHandler);
        }
    }

    /**
     * @param $jobHandler
     */
    private function useJobHandler(JobHandlerInterface $jobHandler): void
    {
        try {
            $job = $this->queueManager->getJob($jobHandler::getJobName());
            $job->markProcessing();
            $jobHandler->handle($job);
        } catch (EmptyQueueException $e) {
        }
    }

}