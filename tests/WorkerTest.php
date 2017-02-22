<?php
namespace Punchkick\QueueManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

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

    public function testHupSignal()
    {
        $proc = new Process('exec ' . PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/test-worker-script.php'));
        $proc->start();

        sleep(3);

        $this->assertTrue(
            $proc->isRunning(),
            'Process exited prematurely: ' . $proc->getOutput() . $proc->getErrorOutput()
        );

        $proc->signal(SIGHUP);

        sleep(1);

        $this->assertFalse($proc->isRunning());
        $this->assertEquals('HUP signal received', $proc->getOutput());
    }
}
