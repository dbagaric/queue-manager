<?php
namespace Punchkick\QueueManager\Offline;

use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Offline\OfflineQueueManager;

class OfflineQueueManagerTest extends TestCase
{
    /**
     * @var OfflineQueueManager
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new OfflineQueueManager([]);
    }

    public function testIsTesting()
    {
        $this->markTestIncomplete();
    }
}
