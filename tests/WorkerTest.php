<?php
namespace Punchkick\QueueManager;

use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Worker;

class WorkerTest extends TestCase
{
    /**
     * @var Worker
     */
    protected $instance;

    public function setUp()
    {
        $mockQueueManager = $this->getMockBuilder(QueueManagerInterface::class)
            ->getMock();
        $this->instance = new Worker($mockQueueManager);
    }

    public function testIsTesting()
    {
        $this->markTestIncomplete();
    }
}
