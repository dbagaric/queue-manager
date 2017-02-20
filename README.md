# Punchkick Queue Manager

Provides a simple interface to handle different types of queues, without coupling your logic to the specific queue software.

## Currently Supported Queues

* Disque
* Offline (Synchronous)

## Installation

__Note:__ Wherever you are installing the package, you'll need access to Gitlab.

* Add the repository to your composer.json file
```json
{
    "repositories": [
        {
            "type": "vcs", 
            "url": "git@git.pkiapps.com:punchkickinteractive/queue-manager.git"
        }
    ]
}
```

* Add the package to your requirements  
```bash
composer require punchkick/queue-manager
```


## Creating Job Handlers

Simply create a new class that implements the `JobHandlerInterface`.

```php
use Punchkick\QueueManager\JobHandlerInterface;

class YourJob implements JobHandlerInterface
{
    /**
     * @return string
     */
    public static function getJobName(): string
    {
        return 'your_job_name_here';
    }

    /**
     * @param JobInterface $job
     * @return bool
     */
    public function handle(JobInterface $job): bool
    {
        // do your job here
        if (fails()) {
            return false;
        } else {
            return true;
        }
    }
}
```

## Adding Jobs to the Queue

### Create an instance of QueueManagerFactory

The Factory will provide you an instance of your chosen queue type and can 
fallback to offline mode if it has trouble connecting.

```php
$queueManagerFactory = new Punchkick\QueueManager\QueueManagerFactory();
```

### Adding Job Handlers to the QueueManagerFactory

If you are using the offline fallback, you'll need to add your job handlers to
the QueueManagerFactory. You can do that in a few ways.

* During instantiation

```php
$queueManagerFactory = new Punchkick\QueueManager\QueueManagerFactory([
    new YourJob(), 
    new YourOtherJob()
]);
```

* After instantiation, as an array

```php
$queueManager->setJobHandlers([
    new YourJob(), 
    new YourOtherJob()
]);
```

* After instantiation, one at a time

```php
$queueManager->addJobHandler(new YourJob());
```

### Getting an instance of QueueManagerInterface

The make method accepts the type of queue you are requesting, the host of the 
queue server, and the port of the queue server.

```php
/** @var QueueManagerInterface $queueManager */
$queueManager = $queueManagerFactory->make(QueueManagerFactory::TYPE_DISQUE, 127.0.0.1, 7711);
```

#### Disable the offline fallback

You can disable the offline fallback by passing `false` as the fourth parameter to `make`.
If you do that, and the connection fails, `make` will throw a 
`\Punchkick\QueueManager\Exception\BadConnectionException`.

```php
/** @var QueueManagerInterface $queueManager */
$queueManager = $queueManagerFactory->make(QueueManagerFactory::TYPE_DISQUE, 127.0.0.1, 7711, false);
```

### Queuing Jobs

Once you have an instance of QueueManagerInterface, you can queue up jobs using 
the `addJob` method.


## Setting Up Workers


