<?php
namespace Punchkick\QueueManager\SQS;

use Exception;
use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;
use Punchkick\QueueManager\SQS\SQSJob;
use Aws\Sqs\SqsClient;

class SQSJobTest extends TestCase
{
    private $mockClient;
    private $mockDoneLog;

    public function setUp()
    {
        $this->mockClient = $this->getMockBuilder(SqsClient::class)
            ->setMethods(['deleteMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDoneLog = $this->getMockBuilder(DoneLogInterface::class)
            ->setMethods(['logJob'])
            ->getMockForAbstractClass();
    }

    public function testMarkProcessing()
    {
        $sqsJob = new SQSJob([], $this->mockClient, 'abc', '123', $this->mockDoneLog, 'xyz');
        $this->assertTrue($sqsJob->markProcessing());
    }

    public function testGetData()
    {
        $sqsJob = new SQSJob(['some' => 'data'], $this->mockClient, 'abc', '123', $this->mockDoneLog, 'xyz');
        $this->assertSame(['some' => 'data'], $sqsJob->getData());
    }

    public function testMarkFailed()
    {
        $sqsJob = new SQSJob([], $this->mockClient, 'abc', '123', $this->mockDoneLog, 'xyz');
        $this->assertTrue($sqsJob->markFailed());
    }

    public function testMarkDone()
    {
        $this->mockClient->expects($this->once())
            ->method('deleteMessage')
            ->with([
                'QueueUrl' => 'abc',
                'ReceiptHandle' => '123',
            ])
            ->will($this->returnValue([
                '@metadata' => [
                    'statusCode' => 200
                ]
            ]));

        $this->mockDoneLog->expects($this->once())
            ->method('logJob')
            ->with('xyz')
            ->will($this->returnValue(true));

        $sqsJob = new SQSJob([], $this->mockClient, 'abc', '123', $this->mockDoneLog, 'xyz');
        $this->assertTrue($sqsJob->markDone());
    }

    public function testMarkDoneFailsWhenException()
    {
        $this->mockClient->expects($this->once())
            ->method('deleteMessage')
            ->with([
                'QueueUrl' => 'abc',
                'ReceiptHandle' => '123',
            ])
            ->will($this->throwException(new Exception));

        $sqsJob = new SQSJob([], $this->mockClient, 'abc', '123', $this->mockDoneLog, 'xyz');
        $this->assertFalse($sqsJob->markDone());
    }


}
