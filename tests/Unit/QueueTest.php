<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Veldora\Framework\Database\Connection;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\Queue\Drivers\DatabaseDriver;
use Veldora\Framework\Queue\Drivers\SyncDriver;
use Veldora\Framework\Queue\Job;
use Veldora\Framework\Queue\QueueManager;
use Veldora\Framework\Queue\Worker;

class TestSuccessfulJob extends Job
{
    public static int $executedCount = 0;
    public static ?string $receivedName = null;

    public function __construct(public string $name)
    {
    }

    public function handle(): void
    {
        self::$executedCount++;
        self::$receivedName = $this->name;
    }
}

class TestFailingJob extends Job
{
    public static int $attemptCount = 0;
    public static bool $failedCalled = false;

    public function handle(): void
    {
        self::$attemptCount++;
        throw new RuntimeException("Simulated job failure");
    }

    public function failed(?\Throwable $exception = null): void
    {
        self::$failedCalled = true;
    }
}

class QueueTest extends TestCase
{
    protected Application $app;
    protected Connection $db;
    protected QueueManager $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));

        // Create an in-memory SQLite database connection
        $this->db = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->app->singleton(Connection::class, fn () => $this->db);

        $this->queue = new QueueManager($this->app);
        $this->app->singleton(QueueManager::class, fn () => $this->queue);

        TestSuccessfulJob::$executedCount = 0;
        TestSuccessfulJob::$receivedName = null;
        TestFailingJob::$attemptCount = 0;
        TestFailingJob::$failedCalled = false;
    }

    public function test_sync_driver_executes_job_immediately(): void
    {
        $driver = new SyncDriver();
        $driver->push(new TestSuccessfulJob('test_sync'));

        $this->assertSame(1, TestSuccessfulJob::$executedCount);
        $this->assertSame('test_sync', TestSuccessfulJob::$receivedName);
    }

    public function test_database_driver_push_and_pop(): void
    {
        $driver = new DatabaseDriver($this->db);
        $driver->ensureTablesExist();

        $this->assertSame(0, $driver->size());

        $driver->push(new TestSuccessfulJob('hello_db'));
        $this->assertSame(1, $driver->size());

        $job = $driver->pop('default');
        $this->assertNotNull($job);

        $job->handle();
        $this->assertSame(1, TestSuccessfulJob::$executedCount);
        $this->assertSame('hello_db', TestSuccessfulJob::$receivedName);

        $driver->delete($job);
        $this->assertSame(0, $driver->size());
    }

    public function test_worker_processes_job_successfully(): void
    {
        $driver = new DatabaseDriver($this->db);
        $driver->ensureTablesExist();
        $this->queue->extend('database', fn () => $driver);

        $driver->push(new TestSuccessfulJob('worker_test'));

        $worker = new Worker($this->queue);
        $processed = $worker->processNextJob('database', 'default');

        $this->assertTrue($processed);
        $this->assertSame(1, TestSuccessfulJob::$executedCount);
        $this->assertSame('worker_test', TestSuccessfulJob::$receivedName);
        $this->assertSame(0, $driver->size());
    }

    public function test_worker_retries_and_logs_failed_job(): void
    {
        $driver = new DatabaseDriver($this->db);
        $driver->ensureTablesExist();
        $this->queue->extend('database', fn () => $driver);

        $failingJob = new TestFailingJob();
        $driver->push($failingJob);

        $worker = new Worker($this->queue);

        // Attempt 1: Should fail and release back to queue
        $worker->processNextJob('database', 'default');
        $this->assertSame(1, TestFailingJob::$attemptCount);
        $this->assertFalse(TestFailingJob::$failedCalled);

        // Reset reservation so it is available immediately for test
        $this->db->getPdo()->exec("UPDATE jobs SET available_at = " . (time() - 1) . ", reserved_at = NULL");

        // Attempt 2: Should fail and release back
        $worker->processNextJob('database', 'default');
        $this->assertSame(2, TestFailingJob::$attemptCount);
        $this->assertFalse(TestFailingJob::$failedCalled);

        $this->db->getPdo()->exec("UPDATE jobs SET available_at = " . (time() - 1) . ", reserved_at = NULL");

        // Attempt 3: (maxTries = 3) -> Should log to failed_jobs and call failed()
        $worker->processNextJob('database', 'default');
        $this->assertSame(3, TestFailingJob::$attemptCount);
        $this->assertTrue(TestFailingJob::$failedCalled);

        // Queue should now be empty and failed_jobs should have 1 entry
        $this->assertSame(0, $driver->size());
        $failedJobs = $driver->getFailedJobs();
        $this->assertCount(1, $failedJobs);
        $this->assertStringContainsString('Simulated job failure', $failedJobs[0]['exception']);
    }

    public function test_can_retry_failed_jobs(): void
    {
        $driver = new DatabaseDriver($this->db);
        $driver->ensureTablesExist();

        $job = new TestSuccessfulJob('retry_me');
        $driver->logFailed($job, new RuntimeException('Initial error'));

        $this->assertCount(1, $driver->getFailedJobs());

        $retried = $driver->retryFailed('all');
        $this->assertSame(1, $retried);
        $this->assertCount(0, $driver->getFailedJobs());
        $this->assertSame(1, $driver->size());
    }

    public function test_dispatchable_trait_and_helpers(): void
    {
        // Using sync driver by default
        TestSuccessfulJob::dispatch('dispatchable_user');

        $this->assertSame(1, TestSuccessfulJob::$executedCount);
        $this->assertSame('dispatchable_user', TestSuccessfulJob::$receivedName);
    }
}
