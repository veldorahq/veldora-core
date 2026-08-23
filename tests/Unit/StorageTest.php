<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\Storage\Drivers\LocalDriver;
use Veldora\Framework\Storage\StorageManager;

class StorageTest extends TestCase
{
    protected Application $app;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->tempDir = sys_get_temp_dir() . '/veldora_storage_test_' . uniqid();
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

    public function test_local_driver_crud_and_directory_operations(): void
    {
        $storage = new LocalDriver($this->tempDir, '/uploads');

        // Put & Get
        $this->assertTrue($storage->put('notes/todo.txt', 'Buy milk'));
        $this->assertTrue($storage->exists('notes/todo.txt'));
        $this->assertFalse($storage->missing('notes/todo.txt'));
        $this->assertSame('Buy milk', $storage->get('notes/todo.txt'));

        // Prepend & Append
        $storage->prepend('notes/todo.txt', 'Urgent: ');
        $storage->append('notes/todo.txt', ' and cookies');
        $this->assertSame('Urgent: Buy milk and cookies', $storage->get('notes/todo.txt'));

        // Size & Modified
        $this->assertGreaterThan(0, $storage->size('notes/todo.txt'));
        $this->assertGreaterThan(0, $storage->lastModified('notes/todo.txt'));

        // URL
        $this->assertSame('/uploads/notes/todo.txt', $storage->url('notes/todo.txt'));

        // Copy & Move
        $this->assertTrue($storage->copy('notes/todo.txt', 'backup/todo.bak'));
        $this->assertTrue($storage->exists('backup/todo.bak'));

        $this->assertTrue($storage->move('backup/todo.bak', 'backup/todo_archived.txt'));
        $this->assertFalse($storage->exists('backup/todo.bak'));
        $this->assertTrue($storage->exists('backup/todo_archived.txt'));

        // Files & Directories
        $files = $storage->files('notes');
        $this->assertCount(1, $files);
        $this->assertContains('notes/todo.txt', $files);

        $dirs = $storage->directories('');
        $this->assertContains('notes', $dirs);
        $this->assertContains('backup', $dirs);

        // Delete File & Delete Directory
        $this->assertTrue($storage->delete('notes/todo.txt'));
        $this->assertFalse($storage->exists('notes/todo.txt'));

        $this->assertTrue($storage->deleteDirectory('backup'));
        $this->assertFalse($storage->exists('backup/todo_archived.txt'));
    }

    public function test_storage_manager_and_helper(): void
    {
        $manager = new StorageManager($this->app);
        $localDisk = new LocalDriver($this->tempDir, '/media');
        $manager->setDisk('local', $localDisk);
        $manager->setDefaultDisk('local');
        $this->app->instance(StorageManager::class, $manager);

        storage()->put('test.txt', 'Storage manager working!');
        $this->assertSame('Storage manager working!', storage('local')->get('test.txt'));
    }
}
