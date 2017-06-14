<?php
namespace Punchkick\QueueManager\SQS;

use Punchkick\QueueManager\JobInterface;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;

class SQSJob implements JobInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $queueUrl;

    /**
     * @var DoneLogInterface
     */
    protected $queueUrl;

    /**
     * @var string
     */
    protected $receiptHandle;

    public function __construct(
        array $data,
        string $queueUrl,
        string $receiptHandle,
        DoneLogInterface $doneLog
    ) {
        $this->data = $data;
        $this->queueUrl = $queueUrl;
        $this->receiptHandle = $receiptHandle;
        $this->doneLog = $doneLog;
    }

    /**
     * @return bool
     */
    public function markProcessing(): bool
    {
        // no need, the item won't be given to anyone else for 30 seconds
        return true;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function markDone(): bool
    {
        try {
            $result = $client->deleteMessage([
                'QueueUrl' => $this->queueUrl,
                'ReceiptHandle' => $this->receiptHandle,
            ]);

            $this->doneLog->logJob($this->job->getId());

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function markFailed(): bool
    {
        // SQS will re-queue an item after 30 seconds
        return true;
    }
}
