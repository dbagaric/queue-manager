<?php

namespace Punchkick\QueueManager;

use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\Exception\InvalidArgumentException;
use Punchkick\QueueManager\Exception\InvalidTypeException;
use Punchkick\QueueManager\Offline\OfflineQueueManager;
use Punchkick\QueueManager\SQS\SQSQueueManager;

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
        $queueManagerFactory->make(
            999999999, [
            'host' => '127.0.0.1',
            'port' => 7711,
        ]
        );
    }

    /**
     * @dataProvider failsWithInvalidSQSCredsDataProvider
     */
    public function testFailsWithInvalidSQSCreds($creds)
    {
        $this->expectException(InvalidArgumentException::class);

        $queueManagerFactory = new QueueManagerFactory();
        $queueManagerFactory->make(QueueManagerFactory::TYPE_SQS, $creds);
    }

    public function failsWithInvalidSQSCredsDataProvider()
    {
        return [
            [[]],
            [['profile' => 'a', 'region' => 'a', 'baseUrl' => 'a']],
            [['profile' => 'a', 'region' => 'a', 'env' => 'a']],
            [['profile' => 'a', 'baseUrl' => 'a', 'env' => 'a']],
            [['region' => 'a', 'baseUrl' => 'a', 'env' => 'a']],
            [['profile' => '', 'region' => 'a', 'baseUrl' => 'a', 'env' => 'a']],
        ];
    }

    public function testReturnsSQSQueueManager()
    {
        $queueManagerFactory = new QueueManagerFactory();
        $queueManager = $queueManagerFactory->make(
            QueueManagerFactory::TYPE_SQS,
            ['profile' => 'a', 'region' => 'a', 'baseUrl' => 'a', 'env' => 'a']
        );

        $this->assertInstanceOf(QueueManagerInterface::class, $queueManager);
    }

    public function testSQSTakesProperSettings()
    {
        $queueManagerFactory = new QueueManagerFactory();
        /** @var SQSQueueManager $queueManager */
        $queueManager = $queueManagerFactory->make(
            QueueManagerFactory::TYPE_SQS,
            ['profile' => 'a', 'region' => 'b', 'baseUrl' => 'c', 'env' => 'd', 'waitSeconds' => 10]
        );

        $this->assertInstanceOf(SQSQueueManager::class, $queueManager);
        $this->assertSame('c', $queueManager->getBaseUrl());
        $this->assertSame('d', $queueManager->getEnv());
        $this->assertSame(10, $queueManager->getWaitSeconds());
    }

    public function testReturnsOfflineQueueManager()
    {
        $queueManagerFactory = new QueueManagerFactory();
        $queueManager = $queueManagerFactory->make(QueueManagerFactory::TYPE_OFFLINE);

        $this->assertInstanceOf(OfflineQueueManager::class, $queueManager);
    }

}
