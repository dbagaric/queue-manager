<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Queue\JobInterface;
use Disque\Queue\Queue;
use Disque\Connection\Response\ResponseException;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;

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

        $mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->getMockForAbstractClass();

        $disqueJob = new DisqueJob($mockQueue, $mockJob, $mockDoneLog);

        $this->assertTrue($disqueJob->markProcessing());
    }

    public function testProcessingFails()
    {
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->getMock();

        $mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['processing'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQueue->expects($this->once())
            ->method('processing')
            ->with($mockJob)
            ->will($this->throwException(new ResponseException()));

        $mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->getMockForAbstractClass();

        $disqueJob = new DisqueJob($mockQueue, $mockJob, $mockDoneLog);

        $this->assertFalse($disqueJob->markProcessing());
    }

    /**
     * @param $output
     * @param $expectedReturn
     * @dataProvider getDataReturnsArrayDataProvider
     */
    public function testGetDataReturnsArray($output, $expectedReturn)
    {
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->setMethods(['getBody'])
            ->getMockForAbstractClass();

        $mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['getBody'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockJob->expects($this->once())
            ->method('getBody')
            ->willReturn($output);

        $mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->getMockForAbstractClass();

        $disqueJob = new DisqueJob($mockQueue, $mockJob, $mockDoneLog);

        $this->assertEquals($expectedReturn, $disqueJob->getData());
    }

    public function getDataReturnsArrayDataProvider()
    {
        return [
            [0, [0]],
            [null, []],
            ['string', ['string']],
            [['test'=>'value'], ['test'=>'value']],
        ];
    }

    public function testMarkDone()
    {
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();

        $mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['processed'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQueue->expects($this->once())
            ->method('processed')
            ->with($mockJob);

        $mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->getMockForAbstractClass();

        $disqueJob = new DisqueJob($mockQueue, $mockJob, $mockDoneLog);

        $this->assertTrue($disqueJob->markDone());
    }

    public function testMarkFailed()
    {
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();

        $mockQueue = $this->getMockBuilder(Queue::class)
            ->setMethods(['failed'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockQueue->expects($this->once())
            ->method('failed')
            ->with($mockJob);

        $mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->getMockForAbstractClass();

        $disqueJob = new DisqueJob($mockQueue, $mockJob, $mockDoneLog);

        $this->assertTrue($disqueJob->markFailed());
    }
}
