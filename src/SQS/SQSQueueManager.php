<?php

namespace Punchkick\QueueManager\SQS;

use Aws\Result;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Psr\Log\LoggerInterface;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;
use Punchkick\QueueManager\Exception\EmptyQueueException;
use Punchkick\QueueManager\Exception\QueueServerErrorException;
use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\QueueManagerInterface;

/**
 * Class SQSQueueManager
 * @package Punchkick\QueueManager\SQS
 */
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
     * @var int|null
     */
    private $delaySeconds;

    /**
     * @param SqsClient $client
     * @param DoneLogInterface $doneLog
     * @param string $baseUrl
     * @param string $env
     * @param int $waitSeconds
     * @param int|null $delaySeconds
     */
    public function __construct(
        SqsClient $client,
        DoneLogInterface $doneLog,
        string $baseUrl,
        string $env,
        int $waitSeconds,
        int $delaySeconds = null
    ) {
        $this->client = $client;
        $this->doneLog = $doneLog;
        $this->baseUrl = $baseUrl;
        $this->env = $env;
        $this->waitSeconds = $waitSeconds;
        $this->delaySeconds = $delaySeconds;
    }

    /**
     * @return SqsClient
     */
    public function getClient(): SqsClient
    {
        return $this->client;
    }

    /**
     * @param SqsClient $client
     */
    public function setClient(SqsClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return DoneLogInterface
     */
    public function getDoneLog(): DoneLogInterface
    {
        return $this->doneLog;
    }

    /**
     * @param DoneLogInterface $doneLog
     */
    public function setDoneLog(DoneLogInterface $doneLog)
    {
        $this->doneLog = $doneLog;
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->env;
    }

    /**
     * @param string $env
     */
    public function setEnv(string $env)
    {
        $this->env = $env;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return int
     */
    public function getWaitSeconds(): int
    {
        return $this->waitSeconds;
    }

    /**
     * @param int $waitSeconds
     */
    public function setWaitSeconds(int $waitSeconds)
    {
        $this->waitSeconds = $waitSeconds;
    }

    /**
     * @return int|null
     */
    public function getDelaySeconds(): ?int
    {
        return $this->delaySeconds;
    }

    /**
     * @param int|null $delaySeconds
     */
    public function setDelaySeconds(?int $delaySeconds)
    {
        $this->delaySeconds = $delaySeconds;
    }

    /**
     * @param string $jobName
     * @param array $jobData
     *
     * @return bool
     * @throws QueueServerErrorException
     */
    public function addJob(string $jobName, array $jobData): bool
    {
        $messageData = [
            'QueueUrl'    => $this->getQueueUrlForJobName($jobName),
            'MessageBody' => json_encode($jobData),
        ];

        if (!is_null($this->delaySeconds)) {
            $messageData['DelaySeconds'] = $this->delaySeconds;
        }

        try {
            $result = $this->client->sendMessage($messageData);
        } catch (SqsException $e) {
            throw new QueueServerErrorException('Failed to add job to queue', $e->getCode(), $e);
        }

        return !empty($result['MD5OfMessageBody']);
    }

    /**
     * @param string $jobName
     *
     * @return JobInterface
     * @throws EmptyQueueException
     * @throws QueueServerErrorException
     **/
    public function getJob(string $jobName): JobInterface
    {
        $result = $this->receiveMessage($jobName);

        $messages = $result->get('Messages');
        if (empty($messages) || !is_array($messages)) {
            throw new EmptyQueueException();
        }

        $messageData = $messages[0];

        $body = json_decode($messageData['Body'], true) ?: [];

        $job = $this->createJob($jobName, $body, $messageData);

        $this->verifyMessage($messageData, $job);

        return $job;
    }

    /**
     * Purge the queue of a given job
     *
     * @param string $jobName
     *
     * @return bool
     * @throws QueueServerErrorException
     */
    public function purgeJobs(string $jobName): bool
    {
        try {
            $this->client->purgeQueue(
                [
                    'QueueUrl' => $this->getQueueUrlForJobName($jobName),
                ]
            );
        } catch (SqsException $e) {
            throw new QueueServerErrorException('Failed to purge the specified queue', $e->getCode(), $e);
        }

        return true;
    }

    /**
     * @param string $jobName
     *
     * @return string
     */
    private function getQueueUrlForJobName(string $jobName): string
    {
        return $this->baseUrl . $jobName . '_' . $this->env;
    }

    /**
     * @return bool
     */
    private function wasJsonError(): bool
    {
        return json_last_error() !== JSON_ERROR_NONE;
    }

    /**
     * @param string $jobName
     *
     * @return Result
     * @throws QueueServerErrorException
     */
    private function receiveMessage(string $jobName): Result
    {
        try {
            return $this->client->receiveMessage(
                [
                    'QueueUrl'            => $this->getQueueUrlForJobName($jobName),
                    'WaitTimeSeconds'     => $this->waitSeconds,
                    'MaxNumberOfMessages' => 1,
                ]
            );
        } catch (SqsException $e) {
            throw new QueueServerErrorException('Unable to send request to receive message', $e->getCode(), $e);
        }
    }

    /**
     * @param string $jobName
     * @param $body
     * @param $messageData
     *
     * @return SQSJob
     */
    private function createJob(string $jobName, $body, $messageData): SQSJob
    {
        return new SQSJob(
            $body,
            $this->client,
            $this->getQueueUrlForJobName($jobName),
            $messageData['ReceiptHandle'],
            $this->doneLog,
            $messageData['MessageId']
        );
    }

    /**
     * @param array $messageData
     * @param JobInterface $job
     *
     * @throws EmptyQueueException
     */
    private function verifyMessage(array $messageData, JobInterface $job): void
    {
        if ($this->wasJsonError() || $this->doneLog->hasJob($messageData['MessageId'])) {
            $job->markDone();

            throw new EmptyQueueException();
        }
    }
}
