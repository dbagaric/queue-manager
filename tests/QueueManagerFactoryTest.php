<?php
namespace Punchkick\QueueManager;

use PHPUnit\Framework\TestCase;
use Punchkick\QueueManager\QueueManagerFactory;

class QueueManagerFactoryTest extends TestCase
{
    /**
     * @var QueueManagerFactory
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new QueueManagerFactory();
    }

    public function testIsTesting()
    {
        $this->assertTrue(true);
    }
}
