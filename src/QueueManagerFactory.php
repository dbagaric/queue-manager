<?php

namespace Punchkick\QueueManager;

use Disque\Client;
use Disque\Connection\ConnectionException;
use Disque\Connection\Credentials;
use Punchkick\QueueManager\Disque\DisqueQueueManager;
use Punchkick\QueueManager\Exception\BadConnectionException;
use Punchkick\QueueManager\Exception\InvalidTypeException;
use Punchkick\QueueManager\Offline\OfflineQueueManager;

/**
 * Class QueueManagerFactory
 * @package Punchkick\QueueManager
 */
class QueueManagerFactory
{
    const TYPE_DISQUE = 1;

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
     * @param string $host
     * @param int $port
     * @param bool $offlineFallback
     * @return QueueManagerInterface
     * @throws BadConnectionException
     */
    public function make(int $queueType, string $host, int $port, bool $offlineFallback = true): QueueManagerInterface
    {
        try {
            if ($queueType === self::TYPE_DISQUE) {
                return $this->getDisqueQueueManager($host, $port);
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
     * @param string $host
     * @param int $port
     * @return DisqueQueueManager
     * @throws BadConnectionException
     */
    protected function getDisqueQueueManager(string $host, int $port): DisqueQueueManager
    {
        $client = new Client([
            new Credentials($host, $port)
        ]);

        try {
            $client->connect();
        } catch (ConnectionException $e) {
            throw new BadConnectionException('Could not connect to Disque server', 0, $e);
        }

        return new DisqueQueueManager($client);
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