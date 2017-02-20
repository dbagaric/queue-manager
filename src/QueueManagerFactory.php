<?php

namespace Punchkick\QueueManager;

use Disque\Client;
use Disque\Connection\ConnectionException;
use Disque\Connection\Credentials;
use Punchkick\QueueManager\Disque\DisqueQueueManager;
use Punchkick\QueueManager\Exception\BadConnectionException;
use Punchkick\QueueManager\Exception\InvalidTypeException;
use Punchkick\QueueManager\JobHandler\JobHandlerInterface;
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
     * @return QueueManagerInterface
     */
    public function make(int $queueType, string $host, int $port): QueueManagerInterface
    {
        try {
            if ($queueType === self::TYPE_DISQUE) {
                return $this->getDisqueQueueManager($host, $port);
            } else {
                throw new InvalidTypeException(sprintf('Queue type "%s" no supported.', $queueType));
            }
        } catch (BadConnectionException $e) {
            return $this->getOfflineQueueManager();
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
        try {
            return new DisqueQueueManager(
                new Client([
                    new Credentials($host, $port)
                ])
            );
        } catch (ConnectionException $e) {
            throw new BadConnectionException('Could not connect to Disque server', 0, $e);
        }
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