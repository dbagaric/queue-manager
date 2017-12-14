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

                return true;
            }
        };

        $offlineQueueManager = new OfflineQueueManager([$mockJobHandler]);

        $this->assertTrue($offlineQueueManager->addJob($jobName, $jobData));
    }

    public function testDoesntAddJobIfNoHandler()
    {
        $jobName = 'foo_bar';
        $jobData = ['foo' => 'bar'];

        $offlineQueueManager = new OfflineQueueManager([]);

        $this->assertFalse($offlineQueueManager->addJob($jobName, $jobData));
    }

    public function testGetJob()
    {
        $this->expectException(BadMethodCallException::class);

        $offlineQueueManager = new OfflineQueueManager([]);
        $offlineQueueManager->getJob('some_job');
    }

    public function testPurgeQueue()
    {
        $this->expectException(BadMethodCallException::class);

        $offlineQueueManager = new OfflineQueueManager([]);
        $offlineQueueManager->purgeJobs('some_job');
    }
}
