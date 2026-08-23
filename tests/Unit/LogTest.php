<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\Logging\LogManager;

class LogTest extends TestCase
{
    protected Application $app;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->tempDir = sys_get_temp_dir() . '/veldora_log_test_' . uniqid();
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function test_logging_levels_and_context(): void
    {
        $logger = new LogManager($this->app);
        $logger->setLogsPath($this->tempDir);
        $logger->setDefaultChannel('single');
        $this->app->instance(LogManager::class, $logger);

        $logger->info('User logged in', ['user_id' => 42]);
        $logger->warning('API rate limit approaching', ['remaining' => 5]);
        $logger->error('Payment failed', [
            'gateway' => 'stripe',
            'exception' => new Exception('Card declined'),
        ]);

        $logFile = $this->tempDir . '/veldora.log';
        $this->assertFileExists($logFile);

        $content = file_get_contents($logFile);
        $this->assertStringContainsString('[INFO] User logged in {"user_id":42}', $content);
        $this->assertStringContainsString('[WARNING] API rate limit approaching', $content);
        $this->assertStringContainsString('[ERROR] Payment failed', $content);
        $this->assertStringContainsString('Card declined', $content);
    }

    public function test_daily_logging_and_global_helpers(): void
    {
        $logger = new LogManager($this->app);
        $logger->setLogsPath($this->tempDir);
        $logger->setDefaultChannel('daily');
        $this->app->instance(LogManager::class, $logger);

        log_info('Application booted successfully');
        log_error('Database timeout', ['host' => '127.0.0.1']);
        logger('Debug trace', ['trace_id' => 'abc']);

        $date = date('Y-m-d');
        $logFile = "{$this->tempDir}/veldora-{$date}.log";
        $this->assertFileExists($logFile);

        $content = file_get_contents($logFile);
        $this->assertStringContainsString('[INFO] Application booted successfully', $content);
        $this->assertStringContainsString('[ERROR] Database timeout {"host":"127.0.0.1"}', $content);
        $this->assertStringContainsString('[DEBUG] Debug trace {"trace_id":"abc"}', $content);
    }
}
