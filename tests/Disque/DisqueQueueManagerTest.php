<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Client;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Disque\DisqueQueueManager;
use Punchkick\QueueManager\Exception\EmptyQueueException;

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
            ->disableOriginalConstructor()
            ->setMethods(['push', 'pull'])
            ->getMock();

        $this->mockJob = $this->getMockBuilder(Job::class)
            ->getMock();

        $this->disqueQueueManager = new DisqueQueueManager($this->mockClient);
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
            }), ['ttl' => 604800]);

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
