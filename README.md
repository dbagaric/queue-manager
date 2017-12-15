<img src="https://api.ci.pkiapps.com/gitlab/punchkickinteractive/queue-manager.svg?branch=master" />

# Punchkick Queue Manager

Provides a simple interface to handle different types of queues, without coupling your logic to the specific queue software.

## Currently Supported Queues

* Amazon SQS
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
use Punchkick\QueueManager\JobInterface;

class YourJobHandler implements JobHandlerInterface
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

### Create an Instance of QueueManagerFactory

The Factory will provide you an instance of your chosen queue type. The OfflineQueueManager 
will simply execute the job immediately if the queue is not available. This is 
useful for testing in environments where you don't have access to a queue 
server.

```php
$queueManagerFactory = new Punchkick\QueueManager\QueueManagerFactory();
```

### Adding Job Handlers to the QueueManagerFactory

If you are going to use the offline fallback, you'll need to add your job 
handlers to the QueueManagerFactory so they can be executed synchronously. 
You can do that in a few ways.

* During instantiation

```php
$queueManagerFactory = new Punchkick\QueueManager\QueueManagerFactory([
    new YourJobHandler(), 
    new YourOtherJobHandler()
]);
```

* After instantiation, as an array

```php
$queueManager->setJobHandlers([
    new YourJobHandler(), 
    new YourOtherJobHandler()
]);
```

* After instantiation, one at a time

```php
$queueManager->addJobHandler(new YourJobHandler());
```

### Getting an Instance of QueueManagerInterface

The make method accepts the type of queue you are requesting and 
an array with the settings to pass to the queue's client.

```php
/** @var QueueManagerInterface $queueManager */
$queueManager = $queueManagerFactory->make(
    QueueManagerFactory::TYPE_SQS, // or QueueManagerFactory::TYPE_OFFLINE
    $settings
);
```

### Adding a "done log"

As a backup to prevent jobs from being processed more than once, you can provide
an instance of DoneLogInterface and create your own log of the completed jobs 
elsewhere, whether that be in a database or a flat file.

```php

class SomeDoneLog implements \Punchkick\QueueManager\DoneLog\DoneLogInterface
{
    public function logJob($id): bool
    {
        return true; // if the job has been successfully logged
    }

    public function hasJob($id): bool
    {
        return true; // if the job has already been processed
    }
}

$queueManager = $queueManagerFactory->make(
    QueueManagerFactory::TYPE_SQS, // or QueueManagerFactory::TYPE_OFFLINE
    $settings,
    new SomeDoneLog()
);
```


### Queuing Jobs

Once you have an instance of QueueManagerInterface, you can queue up jobs using 
the `addJob` method. Pass in the name of your job and the job data in the form 
of an array.  

```php
$queueManager->addJob(YourJobHandler::getJobName(), ['your_key' => 'your_val']);
```


### Purging a Queue

If you want to remove all jobs with a given name, use the `purgeJobs` method.
It will return true if the jobs have successfully been purged.

```php
$queueManager->purgeJobs(YourJobHandler::getJobName());
```


## Setting Up Workers

## Get a Worker Instance

In your `worker.php` script, create an instance of 
`\Punchkick\QueueManager\Worker`. You'll need to pass in an instance of 
QueueManagerInterface. You'll likely want to disable the offline fallback.

```php
$queueManagerFactory = new Punchkick\QueueManager\QueueManagerFactory([
    new YourJobHandler(), 
    new YourOtherJobHandler()
]);
$queueManager = $quemanagerFactory->make(
    Punchkick\QueueManager\QueueManagerFactory::TYPE_SQS, 
    $settings
);
$worker = new Worker($queueManager);
```

## Add the Job Handlers to the Worker Instance


```php
$worker->addJobHandler(new YourJobHandler());
$worker->addJobHandler(new YourOtherJobHandler());
```

## Run the worker process

```php
$worker->run();
```

## Running the worker script

```bash
/path/to/php /path/to/worker.php 2>/dev/null &
```

## Restarting the worker script

If you send the worker process a HUP signal, it will finish the job it is 
performing before dying. This is useful so that you don't stop a job halfway 
through.

```bash
kill -HUP 1111 # substitute 1111 with the PID of your worker process
```