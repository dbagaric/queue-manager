<?php
namespace Punchkick\QueueManager\DoneLog;

use PHPUnit\Framework\TestCase;

class DisqueQueueManagerTest extends TestCase
{
    public function testLogJobReturnsTrue()
    {
        $doneLog = new NullDoneLog();

        $this->assertTrue(
            $doneLog->logJob('anyid')
        );
    }

    public function testHasJobReturnsFalse()
    {
        $doneLog = new NullDoneLog();
        $this->assertFalse($doneLog->hasJob('anyid'));
    }
}
