<?php
namespace Punchkick\QueueManager\Offline;

use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Offline\OfflineJob;

class OfflineJobTest extends TestCase
{
    /**
     * @var OfflineJob
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new OfflineJob([
            'mock' => 'data'
        ]);
    }

    public function testIsTesting()
    {
        $this->markTestIncomplete();
    }
}
