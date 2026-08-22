<?php

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Veldora\Framework\Console\Commands\AddComponentCommand;
use Veldora\Framework\Console\Commands\ListComponentsCommand;

class UITest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/veldora_ui_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/resources/views/components', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function test_can_list_available_components(): void
    {
        $app = new Application();
        $app->add(new ListComponentsCommand($this->tempDir));

        $command = $app->find('ui:list');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Veldora UI — Available Components', $output);
        $this->assertStringContainsString('button', $output);
        $this->assertStringContainsString('0/15 components installed', $output);
    }

    public function test_can_add_components_to_project(): void
    {
        $app = new Application();
        $app->add(new AddComponentCommand($this->tempDir));

        $command = $app->find('add');
        $tester = new CommandTester($command);
        
        // Test adding single component
        $tester->execute(['components' => ['button']]);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Added [button]', $output);
        $destFile = $this->tempDir . '/resources/views/components/button.veldora.php';
        $this->assertFileExists($destFile);
        $this->assertStringContainsString('Veldora UI — Button Component', file_get_contents($destFile));

        // Test running list command reports 1/15 installed
        $listApp = new Application();
        $listApp->add(new ListComponentsCommand($this->tempDir));
        $listTester = new CommandTester($listApp->find('ui:list'));
        $listTester->execute([]);
        $this->assertStringContainsString('1/15 components installed', $listTester->getDisplay());
    }

    public function test_skips_if_component_already_exists(): void
    {
        $app = new Application();
        $app->add(new AddComponentCommand($this->tempDir));
        $command = $app->find('add');
        $tester = new CommandTester($command);

        // Pre-create the component
        $destFile = $this->tempDir . '/resources/views/components/badge.veldora.php';
        file_put_contents($destFile, 'existing custom badge');

        $tester->execute(['components' => ['badge']]);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('already exists — skipped', $output);
        $this->assertEquals('existing custom badge', file_get_contents($destFile));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $curr = $path . '/' . $file;
            is_dir($curr) ? $this->removeDirectory($curr) : unlink($curr);
        }

        rmdir($path);
    }
}
