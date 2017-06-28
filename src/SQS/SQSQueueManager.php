<?php
namespace Punchkick\QueueManager\SQS;

use Aws\Sqs\Exception\SqsException;
use Punchkick\QueueManager\Exception\QueueServerErrorException;
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
     * @param string $jobName
     * @param array $jobData
     * @return bool
     */
    public function addJob(string $jobName, array $jobData): bool
    {
        try {
            $result = $this->client->sendMessage([
                'QueueUrl' => $this->getQueueUrlForJobName($jobName),
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
     * @throws QueueServerErrorException
     **/
    public function getJob(string $jobName): JobInterface
    {
        try {
            $result = $this->client->receiveMessage([
                'QueueUrl' => $this->getQueueUrlForJobName($jobName),
                'WaitTimeSeconds' => $this->waitSeconds,
                'MaxNumberOfMessages' => 1
            ]);
        } catch (SqsException $e) {
            throw new QueueServerErrorException('Unable to send request to receive message', $e->getCode(), $e);
        }

        if (empty($result->getPath('Messages'))) {
            throw new EmptyQueueException();
        }

        foreach ($result->getPath('Messages') as $messageData) {
            $body = json_decode($messageData['Body'], true);

            if ($this->wasJsonError()) {
                $body = [];
            }

            $job = new SQSJob(
                $body,
                $this->client,
                $this->getQueueUrlForJobName($jobName),
                $messageData['ReceiptHandle'],
                $this->doneLog,
                $messageData['MessageId']
            );

            if (
                $this->wasJsonError()
                || $this->doneLog->hasJob($messageData['MessageId'])
            ) {
                $job->markDone();

                throw new EmptyQueueException();
            }

            return $job;
        }
    }

    /**
     * @param string $jobName
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
}
