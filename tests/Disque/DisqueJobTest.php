<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Queue\JobInterface;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;

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

        $disqueJob = new DisqueJob($mockQueue, $mockJob);

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

        $disqueJob = new DisqueJob($mockQueue, $mockJob);

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

        $disqueJob = new DisqueJob($mockQueue, $mockJob);

        $this->assertTrue($disqueJob->markFailed());
    }

}
