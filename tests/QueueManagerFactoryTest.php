<?php
namespace Punchkick\QueueManager;

use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Exception\BadConnectionException;
use Punchkick\QueueManager\Exception\InvalidTypeException;
use Punchkick\QueueManager\Offline\OfflineQueueManager;

class QueueManagerFactoryTest extends TestCase
{
    public function testAddHandlers()
    {
        $mockJobHandler = $this->getMockBuilder(JobHandlerInterface::class)
            ->getMock();
        $queueManagerFactory = new QueueManagerFactory([$mockJobHandler]);

        $this->assertEquals([$mockJobHandler], $queueManagerFactory->getJobHandlers());

        $queueManagerFactory = new QueueManagerFactory();
        $queueManagerFactory->setJobHandlers([$mockJobHandler]);

        $this->assertEquals([$mockJobHandler], $queueManagerFactory->getJobHandlers());

        $queueManagerFactory = new QueueManagerFactory();
        $queueManagerFactory->addJobHandler($mockJobHandler);

        $this->assertEquals([$mockJobHandler], $queueManagerFactory->getJobHandlers());
    }

    public function testThrowsExceptionForInvalidType()
    {
        $this->expectException(InvalidTypeException::class);

        $queueManagerFactory = new QueueManagerFactory();
        $queueManagerFactory->make(2, '127.0.0.1', 7711);
    }

    public function testFallsbackToOffline()
    {
        $oldErrorReporting = error_reporting();
        error_reporting(E_ALL & ~E_WARNING);
        Warning::$enabled = false;

        $queueManagerFactory = new QueueManagerFactory();
        $offlineQueueManager = $queueManagerFactory->make(QueueManagerFactory::TYPE_DISQUE, '127.0.0.1', 7711);
        $this->assertInstanceOf(OfflineQueueManager::class, $offlineQueueManager);

        error_reporting($oldErrorReporting);
    }

    public function testDoesntFallbackToOffline()
    {
        $oldErrorReporting = error_reporting();
        error_reporting(E_ALL & ~E_WARNING);
        Warning::$enabled = false;

        $this->expectException(BadConnectionException::class);
        $queueManagerFactory = new QueueManagerFactory();
        $offlineQueueManager = $queueManagerFactory->make(QueueManagerFactory::TYPE_DISQUE, '127.0.0.1', 7711, false);

        error_reporting($oldErrorReporting);
    }

}
