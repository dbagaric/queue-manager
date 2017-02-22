<?php

use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\JobHandlerInterface;
use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\QueueManagerInterface;
use Punchkick\QueueManager\Worker;

require __DIR__ . '/../vendor/autoload.php';

set_time_limit(10);

$fooBarJobHandler = new class implements JobHandlerInterface
{
    public static function getJobName(): string
    {
        return 'foo_bar';
    }

    public function handle(JobInterface $job): bool
    {
        return true;
    }

};

$fooBarQueueManager = new class implements QueueManagerInterface
{
    public function addJob(string $jobName, array $jobData): bool
    {
        assert($jobName === 'foo_bar');
        return true;
    }

    public function getJob(string $jobName): JobInterface
    {
        assert($jobName === 'foo_bar');
        throw new EmptyQueueException();
    }
};

$worker = new Worker(
    $fooBarQueueManager
);

$worker->addJobHandler($fooBarJobHandler);

$worker->run();