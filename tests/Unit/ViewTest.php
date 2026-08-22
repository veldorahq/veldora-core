<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\View\Engine;

class ViewTest extends TestCase
{
    protected Application $app;
    protected Engine $engine;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = __DIR__ . '/../temp_views';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        // Setup Application to override view paths
        $this->app = new Application($this->tempDir);
        $this->engine = new Engine($this->app);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_it_renders_variables_with_escaping(): void
    {
        $viewFile = $this->tempDir . '/resources/views/test.veldora.php';
        mkdir(dirname($viewFile), 0755, true);
        file_put_contents($viewFile, 'Hello {{ $name }} {!! $raw !!}');

        $rendered = $this->engine->render('test', [
            'name' => '<strong>Veldora</strong>',
            'raw' => '<b>Raw</b>'
        ]);

        $this->assertSame('Hello &lt;strong&gt;Veldora&lt;/strong&gt; <b>Raw</b>', $rendered);
    }

    public function test_it_compiles_control_directives(): void
    {
        $viewFile = $this->tempDir . '/resources/views/loop.veldora.php';
        mkdir(dirname($viewFile), 0755, true);
        file_put_contents($viewFile, '@if($show)@foreach($items as $i){{ $i }}@endforeach@endif');

        $rendered = $this->engine->render('loop', [
            'show' => true,
            'items' => ['a', 'b', 'c']
        ]);

        $this->assertSame('abc', $rendered);
    }

    public function test_it_supports_layout_inheritance(): void
    {
        $layoutFile = $this->tempDir . '/resources/views/layout.veldora.php';
        $childFile = $this->tempDir . '/resources/views/child.veldora.php';
        mkdir(dirname($layoutFile), 0755, true);

        file_put_contents($layoutFile, '<html>@yield("content")</html>');
        file_put_contents($childFile, '@extends("layout")@section("content")body-content@endsection');

        $rendered = $this->engine->render('child');
        $this->assertSame('<html>body-content</html>', $rendered);
    }

    public function test_it_renders_component_templates_and_slots(): void
    {
        $componentFile = $this->tempDir . '/app/components/ui/Alert.veldora.php';
        $viewFile = $this->tempDir . '/resources/views/page.veldora.php';
        
        mkdir(dirname($componentFile), 0755, true);
        mkdir(dirname($viewFile), 0755, true);

        file_put_contents($componentFile, '<div class="{{ $class }}"><h3>{{ $title }}</h3>{{ $slot }}</div>');
        file_put_contents($viewFile, '<x-alert class="danger"><x-slot name="title">Warning</x-slot>Something failed.</x-alert>');

        $rendered = $this->engine->render('page');
        $this->assertSame('<div class="danger"><h3>Warning</h3>Something failed.</div>', $rendered);
    }
}
