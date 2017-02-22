<?php
namespace Punchkick\QueueManager\Offline;

use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Offline\OfflineJob;

class OfflineJobTest extends TestCase
{
    /**
     * @var OfflineJob
     */
    protected $offlineJob;

    /**
     * @var array
     */
    protected $mockData;

    public function setUp()
    {
        $this->mockData = [
            'mock' => 'data'
        ];
        $this->offlineJob = new OfflineJob($this->mockData);
    }

    public function testMarkProcessing()
    {
        $this->assertTrue($this->offlineJob->markProcessing());
    }

    public function testGetData()
    {
        $this->assertEquals($this->mockData, $this->offlineJob->getData());
    }

    public function testMarkDone()
    {
        $this->assertTrue($this->offlineJob->markDone());
    }

    public function testMarkFailed()
    {
        $this->assertTrue($this->offlineJob->markFailed());
    }
}
