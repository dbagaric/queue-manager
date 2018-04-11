<?php

namespace Punchkick\QueueManager;

use Aws\Sqs\SqsClient;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;
use Punchkick\QueueManager\DoneLog\NullDoneLog;
use Punchkick\QueueManager\Exception\InvalidArgumentException;
use Punchkick\QueueManager\Exception\InvalidTypeException;
use Punchkick\QueueManager\Offline\OfflineQueueManager;
use Punchkick\QueueManager\SQS\SQSQueueManager;

/**
 * Class QueueManagerFactory
 * @package Punchkick\QueueManager
 */
class QueueManagerFactory
{
    /**
     * @var int
     */
    const TYPE_SQS = 2;

    /**
     * @var int
     */
    const TYPE_OFFLINE = 3;

    /**
     * @var JobHandlerInterface[]
     */
    protected $jobHandlers;

    /**
     * QueueManagerFactory constructor.
     *
     * @param JobHandlerInterface[] $jobHandlers
     */
    public function __construct(array $jobHandlers = [])
    {
        $this->jobHandlers = $jobHandlers;
    }

    /**
     * @param int $queueType
     * @param array $settings
     * @param DoneLogInterface|null $doneLog
     *
     * @return QueueManagerInterface
     */
    public function make(
        int $queueType,
        array $settings = [],
        DoneLogInterface $doneLog = null
    ): QueueManagerInterface {
        if (!$doneLog) {
            $doneLog = new NullDoneLog();
        }

        if ($queueType === self::TYPE_SQS) {
            return $this->getSqsQueueManager($doneLog, $settings);
        } elseif ($queueType === self::TYPE_OFFLINE) {
            return $this->getOfflineQueueManager();
        } else {
            throw new InvalidTypeException(sprintf('Queue type "%s" is not supported', $queueType));
        }
    }

    /**
     * @return JobHandlerInterface[]
     */
    public function getJobHandlers(): array
    {
        return $this->jobHandlers;
    }

    /**
     * @param JobHandlerInterface $jobHandler
     */
    public function addJobHandler(JobHandlerInterface $jobHandler)
    {
        $this->jobHandlers[] = $jobHandler;
    }

    /**
     * @param JobHandlerInterface[] $jobHandlers
     */
    public function setJobHandlers(array $jobHandlers)
    {
        $this->jobHandlers = $jobHandlers;
    }

    /**
     * @param DoneLogInterface $doneLog
     * @param array $settings
     *
     * @return SQSQueueManager
     * @throws InvalidArgumentException
     */
    protected function getSqsQueueManager(
        DoneLogInterface $doneLog,
        array $settings
    ): SQSQueueManager {
        if (empty($settings['region'])
            || empty($settings['baseUrl'])
            || empty($settings['env'])
        ) {
            throw new InvalidArgumentException(
                'Please set host and port to SQS settings.'
            );
        }

        return new SQSQueueManager(
            new SqsClient(
                [
                    'region'  => $settings['region'],
                    'version' => '2012-11-05',
                ]
            ),
            $doneLog,
            $settings['baseUrl'],
            $settings['env'],
            isset($settings['waitSeconds']) ? (int)$settings['waitSeconds'] : 5,
            isset($settings['delaySeconds']) ? (int)$settings['delaySeconds'] : null
        );
    }

    /**
     * @return OfflineQueueManager
     */
    protected function getOfflineQueueManager(): OfflineQueueManager
    {
        return new OfflineQueueManager(
            $this->jobHandlers
        );
    }
}
