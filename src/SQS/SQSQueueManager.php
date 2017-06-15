<?php
namespace Punchkick\QueueManager\SQS;

use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\QueueManagerInterface;
use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;
use Aws\Sqs\SqsClient;
use Exception;
use Psr\Log\LoggerInterface;


class SQSQueueManager implements QueueManagerInterface
{
    /**
     * @var SqsClient
     */
    private $client;

    /**
     * @var DoneLogInterface
     */
    private $doneLog;

    /**
     * @var string
     */
    private $env;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var int
     */
    private $waitSeconds;

    /**
     * @param SqsClient $client
     * @param DoneLogInterface $doneLog
     * @param string $baseUrl
     * @param string $env
     * @param int $waitSeconds
     */
    public function __construct(
        SqsClient $client,
        DoneLogInterface $doneLog,
        string $baseUrl,
        string $env,
        int $waitSeconds
    ) {
        $this->client = $client;
        $this->doneLog = $doneLog;
        $this->baseUrl = $baseUrl;
        $this->env = $env;
        $this->waitSeconds = $waitSeconds;
    }

    /**
     * @param string $jobName
     * @param array $jobData
     * @return bool
     */
    public function addJob(string $jobName, array $jobData): bool
    {
        try {
            $result = $this->client->sendMessage([
                'QueueUrl' => $this->baseUrl . $jobName . '_' . $this->env,
                'MessageBody' => json_encode($jobData),
            ]);

            return !empty($result['MD5OfMessageBody']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $jobName
     * @return JobInterface
     * @throws EmptyQueueException
     **/
    public function getJob(string $jobName): JobInterface
    {
        try {
            $result = $this->client->receiveMessage([
                'QueueUrl' => $this->baseUrl . $jobName . '_' . $this->env,
                'WaitTimeSeconds' => $this->waitSeconds,
                'MaxNumberOfMessages' => 1
            ]);
        } catch (Exception $e) {
            throw new EmptyQueueException();
        }

        if (empty($result->getPath('Messages'))) {
            throw new EmptyQueueException();
        }

        foreach ($result->getPath('Messages') as $messageData) {
            $body = json_decode($messageData['Body'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new EmptyQueueException();
            }

            $job = new SQSJob(
                $body,
                $this->client,
                $this->baseUrl . $jobName . '_' . $this->env,
                $messageData['ReceiptHandle'],
                $this->doneLog,
                $messageData['MessageId']
            );

            if ($this->doneLog->hasJob($messageData['MessageId'])) {
                $job->markDone();

                throw new EmptyQueueException();
            }

            return $job;
        }
    }
}
