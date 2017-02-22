<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Queue\JobInterface;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Disque\DisqueJob;

class DisqueJobTest extends TestCase
{
    public function testProcessing()
    {
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->getMock();

        $mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['processing'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQueue->expects($this->once())
            ->method('processing')
            ->with($mockJob);

        $disqueJob = new DisqueJob($mockQueue, $mockJob);

        $this->assertTrue($disqueJob->markProcessing());
    }

    public function testGetData()
    {
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->setMethods(['getBody'])
            ->getMock();
        $mock

        $mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['processing'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQueue->expects($this->once())
            ->method('processing')
            ->with($mockJob);

        $disqueJob = new DisqueJob($mockQueue, $mockJob);

        $this->assertTrue($disqueJob->markProcessing());
    }
}
