<?php

namespace Punchkick\QueueManager;

use Punchkick\QueueManager\Exception\EmptyQueueException;

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
        \pcntl_signal(SIGHUP, [$this, 'hupSignalHandler']);

        $this->queueManager = $queueManager;
        $this->jobHandlers = [];
    }

    /**
     * @return void
     */
    public function hupSignalHandler()
    {
        exit('HUP signal received');
    }

    /**
     * @param JobHandlerInterface $jobHandler
     */
    public function addJobHandler(JobHandlerInterface $jobHandler)
    {
        $this->jobHandlers[] = $jobHandler;
    }

    /**
     * @return void
     */
    public function run()
    {
        while (true) {
            $this->loopThroughHandlers();
        }
    }

    /**
     * @return void
     */
    protected function loopThroughHandlers()
    {
        foreach ($this->jobHandlers as $jobHandler) {
            pcntl_signal_dispatch();
            $this->useJobHandler($jobHandler);
        }
    }

    /**
     * @param $jobHandler
     */
    protected function useJobHandler(JobHandlerInterface $jobHandler)
    {
        try {
            $job = $this->queueManager->getJob($jobHandler::getJobName());
            $job->markProcessing();
            $jobHandler->handle($job);
        } catch (EmptyQueueException $e) {
        }
    }
}
