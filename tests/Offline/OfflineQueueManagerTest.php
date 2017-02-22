<?php
namespace Punchkick\QueueManager\Offline;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\JobHandlerInterface;
use Punchkick\QueueManager\JobInterface;

class OfflineQueueManagerTest extends TestCase
{
    public function testAddJob()
    {
        $jobName = 'foo_bar';
        $jobData = ['foo' => 'bar'];

        $mockJobHandler = new class implements JobHandlerInterface {
            public static function getJobName(): string
            {
                return 'foo_bar';
            }

            public function handle(JobInterface $job): bool
            {
                assert($job->getData() === ['foo' => 'bar']);

                return false;
            }
        };

        $offlineQueueManager = new OfflineQueueManager([$mockJobHandler]);

        $this->assertFalse($offlineQueueManager->addJob($jobName, $jobData));
    }

    public function testGetJob()
    {
        $this->expectException(BadMethodCallException::class);

        $offlineQueueManager = new OfflineQueueManager([]);
        $offlineQueueManager->getJob('some_job');
    }
}
