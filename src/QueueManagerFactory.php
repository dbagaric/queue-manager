<?php

namespace Punchkick\QueueManager;

use Exception;
use Aws\Sqs\SqsClient;
use Disque\Client;
use Disque\Connection\ConnectionException;
use Disque\Connection\Credentials;
use Punchkick\QueueManager\Disque\DisqueQueueManager;
use Punchkick\QueueManager\Exception\BadConnectionException;
use Punchkick\QueueManager\Exception\InvalidArgumentException;
use Punchkick\QueueManager\Exception\InvalidTypeException;
use Punchkick\QueueManager\Offline\OfflineQueueManager;
use Punchkick\QueueManager\SQS\SQSQueueManager;
use Punchkick\QueueManager\DoneLog\NullDoneLog;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;

/**
 * Class QueueManagerFactory
 * @package Punchkick\QueueManager
 */
class QueueManagerFactory
{
    const TYPE_DISQUE = 1;
    const TYPE_SQS = 2;

    /**
     * @var JobHandlerInterface[]
     */
    protected $jobHandlers;

    /**
     * QueueManagerFactory constructor.
     * @param JobHandlerInterface[] $jobHandlers
     */
    public function __construct(array $jobHandlers = [])
    {
        $this->jobHandlers = $jobHandlers;
    }

    /**
     * @param int $queueType
     * @param bool $offlineFallback
     * @param DoneLogInterface|null $doneLog
     * @return QueueManagerInterface
     * @throws BadConnectionException
     */
    public function make(
        int $queueType,
        array $settings,
        DoneLogInterface $doneLog = null,
        bool $offlineFallback = false
    ): QueueManagerInterface {

        if (!$doneLog) {
            $doneLog = new NullDoneLog();
        }

        try {
            if ($queueType === self::TYPE_DISQUE) {
                return $this->getDisqueQueueManager($doneLog, $settings);
            } elseif ($queueType === self::TYPE_SQS) {
                return $this->getSqsQueueManager($doneLog, $settings);
            } else {
                throw new InvalidTypeException(sprintf('Queue type "%s" is not supported', $queueType));
            }
        } catch (BadConnectionException $e) {
            if ($offlineFallback) {
                return $this->getOfflineQueueManager();
            } else {
                throw $e;
            }
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
     * @param array $settings
     * @return DisqueQueueManager
     * @throws BadConnectionException
     */
    protected function getDisqueQueueManager(DoneLogInterface $doneLog, array $settings): DisqueQueueManager
    {
        if (empty($settings['host']) || empty($settings['port'])) {
            throw new InvalidArgumentException('Please set host and port to Disque server.');
        }

        $client = new Client([
            new Credentials($settings['host'], $settings['port'])
        ]);

        try {
            $client->connect();
        } catch (ConnectionException $e) {
            throw new BadConnectionException('Could not connect to Disque server', 0, $e);
        }

        return new DisqueQueueManager($client);
    }

    /**
     * @param array $settings
     * @return SQSQueueManager
     */
    protected function getSqsQueueManager(DoneLogInterface $doneLog, array $settings): SQSQueueManager
    {
        if (
            empty($settings['profile'])
            || empty($settings['region'])
            || empty($settings['baseUrl'])
            || empty($settings['env'])
        ) {
            throw new InvalidArgumentException(
                'Please set host and port to SQS settings.'
            );
        }

        try {
            $sqsClient = SqsClient::factory([
                'profile' => $settings['profile'],
                'region'  => $settings['region'],
                'version' => '2012-11-05',
            ]);
        } catch (Exception $e) {
            throw new BadConnectionException('Could not connect to SQS', 0, $e);
        }

        return new SQSQueueManager(
            $sqsClient,
            $doneLog,
            $settings['baseUrl'],
            $settings['env'],
            isset($settings['waitSeconds'])? (int)$settings['waitSeconds']: 5
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
