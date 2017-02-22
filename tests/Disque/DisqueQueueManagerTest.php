<?php
namespace Punchkick\QueueManager\Disque;

use Disque\Client;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Disque\DisqueQueueManager;

class DisqueQueueManagerTest extends TestCase
{
    /**
     * @var DisqueQueueManager
     */
    protected $instance;

    public function setUp()
    {
        $mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new DisqueQueueManager($mockClient);
    }

    public function testIsTesting()
    {
        $this->markTestIncomplete();
    }
}
