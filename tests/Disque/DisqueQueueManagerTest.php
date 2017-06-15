<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Client;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Disque\DisqueQueueManager;
use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;

class DisqueQueueManagerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockQueue;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockJob;

    /**
     * @var DisqueQueueManager
     */
    protected $disqueQueueManager;

    public function setUp()
    {
        $this->mockClient = $this->getMockBuilder(Client::class)
            ->setMethods(['queue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['push', 'pull', 'processed'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockJob = $this->getMockBuilder(Job::class)
            ->setMethods(['markDone'])
            ->getMock();

        $this->mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->setMethods(['hasJob'])
            ->getMockForAbstractClass();

        $this->disqueQueueManager = new DisqueQueueManager(
            $this->mockClient,
            $this->mockDoneLog
        );
    }

    public function testAddJob()
    {
        $jobName = 'foo_bar';
        $jobData = [
            'foo' => 'bar',
        ];

        $this->mockClient->expects($this->once())
            ->method('queue')
            ->with($jobName)
            ->willReturn($this->mockQueue);

        $this->mockQueue->expects($this->once())
            ->method('push')
            ->with($this->callback(function (Job $job) use ($jobData) {
                return $job->getBody() === $jobData;
            }), ['ttl' => 604800, 'async' => true,]);

        $this->assertTrue($this->disqueQueueManager->addJob($jobName, $jobData));
    }

    public function testGetJobEmptyQueue()
    {
        $jobName = 'foo_bar';

        $this->mockClient->expects($this->once())
            ->method('queue')
            ->with($jobName)
            ->willReturn($this->mockQueue);

        $this->mockQueue->expects($this->once())
            ->method('pull')
            ->with(1000)
            ->willReturn(null);

        $this->expectException(EmptyQueueException::class);
        $this->disqueQueueManager->getJob($jobName);
    }

    public function testJobHasBeenDone()
    {
        $jobName = 'foo_bar';

        $this->mockClient->expects($this->once())
            ->method('queue')
            ->with($jobName)
            ->willReturn($this->mockQueue);

        $this->mockQueue->expects($this->once())
            ->method('pull')
            ->with(1000)
            ->willReturn($this->mockJob);
        $this->mockQueue->expects($this->once())
            ->method('processed')
            ->with($this->mockJob)
            ->willReturn(null);

        $this->mockDoneLog->expects($this->once())
            ->method('hasJob')
            ->willReturn(true);

        $this->expectException(EmptyQueueException::class);
        $this->disqueQueueManager->getJob($jobName);
    }

    public function testGetJobReturnsJob()
    {
        $jobName = 'foo_bar';

        $this->mockClient->expects($this->once())
            ->method('queue')
            ->with($jobName)
            ->willReturn($this->mockQueue);

        $this->mockQueue->expects($this->once())
            ->method('pull')
            ->with(1000)
            ->willReturn($this->mockJob);

        $job = $this->disqueQueueManager->getJob($jobName);
        $this->assertInstanceOf(DisqueJob::class, $job);
    }
}
