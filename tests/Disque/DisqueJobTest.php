<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Queue\JobInterface;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Disque\DisqueJob;

class DisqueJobTest extends TestCase
{
    /**
     * @var DisqueJob
     */
    protected $instance;

    public function setUp()
    {
        $mockQueue = $this->getMockBuilder(Queue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockJob = $this->getMockBuilder(JobInterface::class)
            ->getMock();
        $this->instance = new DisqueJob($mockQueue, $mockJob);
    }

    public function testIsTesting()
    {
        $this->markTestIncomplete();
    }
}
