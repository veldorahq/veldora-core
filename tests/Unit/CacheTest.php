<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Cache\CacheManager;
use Veldora\Framework\Cache\Drivers\ArrayDriver;
use Veldora\Framework\Cache\Drivers\FileDriver;
use Veldora\Framework\Foundation\Application;

class CacheTest extends TestCase
{
    protected Application $app;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->tempDir = sys_get_temp_dir() . '/veldora_cache_test_' . uniqid();
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

    public function test_array_driver_operations(): void
    {
        $cache = new ArrayDriver();

        $this->assertTrue($cache->set('foo', 'bar', 60));
        $this->assertTrue($cache->has('foo'));
        $this->assertSame('bar', $cache->get('foo'));

        // Remember
        $called = 0;
        $val = $cache->remember('foo', 60, function () use (&$called) {
            $called++;
            return 'computed';
        });
        $this->assertSame('bar', $val);
        $this->assertSame(0, $called);

        $val2 = $cache->remember('new_key', 60, function () use (&$called) {
            $called++;
            return 'computed';
        });
        $this->assertSame('computed', $val2);
        $this->assertSame(1, $called);

        // Increment / Decrement
        $this->assertSame(1, $cache->increment('counter'));
        $this->assertSame(5, $cache->increment('counter', 4));
        $this->assertSame(3, $cache->decrement('counter', 2));

        // Forget & Flush
        $this->assertTrue($cache->forget('foo'));
        $this->assertFalse($cache->has('foo'));
        $this->assertTrue($cache->flush());
        $this->assertFalse($cache->has('counter'));
    }

    public function test_file_driver_operations(): void
    {
        $cache = new FileDriver($this->tempDir);

        $this->assertTrue($cache->set('site_name', 'Veldora', 3600));
        $this->assertTrue($cache->has('site_name'));
        $this->assertSame('Veldora', $cache->get('site_name'));

        // Complex types (array and objects)
        $data = ['name' => 'John', 'roles' => ['admin', 'editor']];
        $cache->set('user_profile', $data, 3600);
        $this->assertSame($data, $cache->get('user_profile'));

        // Forget
        $this->assertTrue($cache->forget('site_name'));
        $this->assertNull($cache->get('site_name'));

        // Flush
        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->flush();
        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function test_cache_manager_and_helper(): void
    {
        $manager = new CacheManager($this->app);
        $manager->setDefaultDriver('array');
        $this->app->instance(CacheManager::class, $manager);

        cache(['theme' => 'dark', 'font' => 'Outfit'], 60);

        $this->assertSame('dark', cache('theme'));
        $this->assertSame('Outfit', cache('font'));
        $this->assertSame('default_val', cache('missing', 'default_val'));
    }
}
