<?php

namespace Punchkick\QueueManager\SQS;

use Aws\Command;
use Aws\Result;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;
use Punchkick\QueueManager\DoneLog\NullDoneLog;
use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\Exception\QueueServerErrorException;
use Punchkick\QueueManager\JobInterface;

class SQSQueueManagerTest extends TestCase
{
    /**
     * @var SQSQueueManager
     */
    protected $queueManager;

    /**
     * @var SqsClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var DoneLogInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doneLog;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(SqsClient::class)
            ->setMethods(['sendMessage', 'receiveMessage', 'purgeQueue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->doneLog = $this->getMockBuilder(NullDoneLog::class)
            ->setMethods(['hasJob'])
            ->getMock();
        $this->queueManager = new SQSQueueManager(
            $this->client,
            $this->doneLog,
            'http://example.org/',
            'testing',
            0,
            null
        );
    }

    public function testGetClient()
    {
        $this->assertInstanceOf(SqsClient::class, $this->queueManager->getClient());
    }

    public function testSetClient()
    {
        $client = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->queueManager->setClient($client);
        $this->assertSame($client, $this->queueManager->getClient());
    }

    public function testGetDoneLog()
    {
        $this->assertInstanceOf(NullDoneLog::class, $this->queueManager->getDoneLog());
    }

    public function testSetDoneLog()
    {
        $doneLong = new NullDoneLog();
        $this->queueManager->setDoneLog($doneLong);
        $this->assertSame($doneLong, $this->queueManager->getDoneLog());
    }

    public function testGetEnv()
    {
        $this->assertSame('testing', $this->queueManager->getEnv());
    }

    public function testSetEnv()
    {
        $env = 'some_env';
        $this->queueManager->setEnv($env);
        $this->assertSame($env, $this->queueManager->getEnv());
    }

    public function testGetBaseUrl()
    {
        $this->assertSame('http://example.org/', $this->queueManager->getBaseUrl());
    }

    public function testSetBaseUrl()
    {
        $baseUrl = 'http://base.url';
        $this->queueManager->setBaseUrl($baseUrl);
        $this->assertSame($baseUrl, $this->queueManager->getBaseUrl());
    }

    public function testGetWaitSeconds()
    {
        $this->assertSame(0, $this->queueManager->getWaitSeconds());
    }

    public function testSetWaitSeconds()
    {
        $wait = 99;
        $this->queueManager->setWaitSeconds($wait);
        $this->assertSame($wait, $this->queueManager->getWaitSeconds());
    }

    public function testGetDelaySeconds()
    {
        $this->assertNull($this->queueManager->getDelaySeconds());
    }

    public function testSetDelaySeconds()
    {
        $delay = 99;
        $this->queueManager->setDelaySeconds($delay);
        $this->assertSame($delay, $this->queueManager->getDelaySeconds());
    }

    public function testAddJobEmptyResponse()
    {
        $jobData = ['key' => 'value'];
        $this->client->expects($this->once())->method('sendMessage')->with(
            [
                'QueueUrl'    => 'http://example.org/some_job_testing',
                'MessageBody' => json_encode($jobData),
            ]
        )->will($this->returnValue(null));

        $this->assertFalse($this->queueManager->addJob('some_job', $jobData));
    }

    public function testAddJobValidResponse()
    {
        $jobData = ['key' => 'value'];
        $this->client->expects($this->once())->method('sendMessage')->with(
            [
                'QueueUrl'    => 'http://example.org/some_job_testing',
                'MessageBody' => json_encode($jobData),
            ]
        )->will($this->returnValue(['MD5OfMessageBody' => md5('ok')]));

        $this->assertTrue($this->queueManager->addJob('some_job', $jobData));
    }

    public function testAddJobThrowsException()
    {
        $jobData = ['key' => 'value'];
        $this->client->expects($this->once())->method('sendMessage')->with(
            [
                'QueueUrl'    => 'http://example.org/some_job_testing',
                'MessageBody' => json_encode($jobData),
            ]
        )->will(
            $this->throwException(
                new SqsException('error', new Command('sendMessage'))
            )
        );

        $this->expectException(QueueServerErrorException::class);

        $this->queueManager->addJob('some_job', $jobData);
    }

    public function testAddJobUsesDelayMessage()
    {
        $jobData = ['key' => 'value'];
        $delaySeconds = 88;

        $this->client->expects($this->once())->method('sendMessage')->with(
            [
                'QueueUrl'     => 'http://example.org/some_job_testing',
                'MessageBody'  => json_encode($jobData),
                'DelaySeconds' => 88,
            ]
        )->will($this->returnValue(null));

        $this->queueManager->setDelaySeconds($delaySeconds);
        $this->queueManager->addJob('some_job', $jobData);
    }

    public function testGetJobThrowsExceptionWhenReceivingMessage()
    {
        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->throwException(
                    new SqsException('error', new Command('receiveMessage'))
                )
            );

        $this->expectException(QueueServerErrorException::class);

        $this->queueManager->getJob('some_job');
    }

    public function testGetJobThrowsEmptyQueueExceptionWhenNotArray()
    {
        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->returnValue(
                    new Result(
                        [
                            'Messages' => null,
                        ]
                    )
                )
            );

        $this->expectException(EmptyQueueException::class);

        $this->queueManager->getJob('some_job');
    }

    public function testGetJobThrowsEmptyQueueExceptionWhenNotSet()
    {
        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->returnValue(
                    new Result([])
                )
            );

        $this->expectException(EmptyQueueException::class);

        $this->queueManager->getJob('some_job');
    }

    public function testGetJobThrowsEmptyQueueExceptionWhenEmptyArray()
    {
        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->returnValue(
                    new Result(
                        [
                            'Messages' => [],
                        ]
                    )
                )
            );

        $this->expectException(EmptyQueueException::class);

        $this->queueManager->getJob('some_job');
    }

    public function testGetJobThrowsEmptyQueueExceptionWhenBodyNotJson()
    {
        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->returnValue(
                    new Result(
                        [
                            'Messages' => [
                                [
                                    'Body'          => '',
                                    'ReceiptHandle' => md5(''),
                                    'MessageId'     => md5(''),
                                ],
                            ],
                        ]
                    )
                )
            );

        $this->expectException(EmptyQueueException::class);

        $this->queueManager->getJob('some_job');
    }

    public function testGetJobThrowsEmptyQueueExceptionWhenJobAlreadyDone()
    {
        $messageId = md5('id');

        $this->doneLog->expects($this->once())
            ->method('hasJob')
            ->with($messageId)
            ->will($this->returnValue(true));

        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->returnValue(
                    new Result(
                        [
                            'Messages' => [
                                [
                                    'Body'          => '{}',
                                    'ReceiptHandle' => md5(''),
                                    'MessageId'     => $messageId,
                                ],
                            ],
                        ]
                    )
                )
            );

        $this->expectException(EmptyQueueException::class);

        $this->queueManager->getJob('some_job');
    }

    public function testGetJobReturnsJob()
    {
        $messageId = md5('id');

        $this->doneLog->expects($this->once())
            ->method('hasJob')
            ->with($messageId)
            ->will($this->returnValue(false));

        $this->client->expects($this->once())
            ->method('receiveMessage')
            ->will(
                $this->returnValue(
                    new Result(
                        [
                            'Messages' => [
                                [
                                    'Body'          => '{}',
                                    'ReceiptHandle' => md5(''),
                                    'MessageId'     => $messageId,
                                ],
                            ],
                        ]
                    )
                )
            );

        $this->assertInstanceOf(JobInterface::class, $this->queueManager->getJob('some_job'));
    }

    public function testPurgeJobsThrowsException()
    {
        $this->client->expects($this->once())
            ->method('purgeQueue')
            ->will(
                $this->throwException(
                    new SqsException('error', new Command('purgeQueue'))
                )
            );

        $this->expectException(QueueServerErrorException::class);
        $this->queueManager->purgeJobs('some_job');
    }

    public function testPurgeJobsReturnsTrue()
    {
        $this->client->expects($this->once())
            ->method('purgeQueue')
            ->will($this->returnValue(null));

        $this->assertTrue($this->queueManager->purgeJobs('some_job'));
    }
}
