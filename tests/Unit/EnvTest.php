<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Config\Env;

class EnvTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Env::reset();
    }

    public function test_it_parses_simple_key_values_and_comments(): void
    {
        $content = <<<ENV
# Application Configuration
APP_NAME=Veldora
APP_ENV=local
# Comment here
APP_DEBUG=true
DB_PORT=3306
ENV;

        // Use reflection to run private parser for unit tests
        $reflector = new \ReflectionClass(Env::class);
        $method = $reflector->getMethod('parse');
        $method->setAccessible(true);
        $method->invoke(null, $content);

        $this->assertSame('Veldora', Env::get('APP_NAME'));
        $this->assertSame('local', Env::get('APP_ENV'));
        $this->assertTrue(Env::get('APP_DEBUG'));
        $this->assertSame(3306, Env::get('DB_PORT'));
    }

    public function test_it_resolves_interpolation(): void
    {
        $content = <<<ENV
APP_HOST=localhost
APP_URL=http://\${APP_HOST}:8000
ENV;

        $reflector = new \ReflectionClass(Env::class);
        $method = $reflector->getMethod('parse');
        $method->setAccessible(true);
        $method->invoke(null, $content);

        $this->assertSame('localhost', Env::get('APP_HOST'));
        $this->assertSame('http://localhost:8000', Env::get('APP_URL'));
    }

    public function test_it_supports_multiline_triple_quotes(): void
    {
        $content = <<<ENV
APP_CERT="""
LINE 1
LINE 2
"""
ENV;

        $reflector = new \ReflectionClass(Env::class);
        $method = $reflector->getMethod('parse');
        $method->setAccessible(true);
        $method->invoke(null, $content);

        // Value was stored replacing newline with \n placeholder inside parser
        $this->assertSame("LINE 1\nLINE 2", Env::get('APP_CERT'));
    }
}
