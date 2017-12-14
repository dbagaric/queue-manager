<?php

namespace Punchkick\QueueManager\Offline;

use Punchkick\QueueManager\JobInterface;

/**
 * Class OfflineJob
 * @package Punchkick\QueueManager
 */
class OfflineJob implements JobInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * OfflineJob constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function markProcessing(): bool
    {
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
        return true;
    }

    /**
     * @return bool
     */
    public function markFailed(): bool
    {
        return true;
    }
}
