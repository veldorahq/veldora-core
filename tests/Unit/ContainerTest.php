<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Container;
use Veldora\Framework\Foundation\Exception\ContainerException;
use Veldora\Framework\Foundation\Exception\NotFoundException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function test_it_can_resolve_a_simple_class(): void
    {
        $resolved = $this->container->get(SimpleService::class);
        $this->assertInstanceOf(SimpleService::class, $resolved);
    }

    public function test_it_can_resolve_singletons(): void
    {
        $this->container->singleton(SimpleService::class);

        $first = $this->container->get(SimpleService::class);
        $second = $this->container->get(SimpleService::class);

        $this->assertSame($first, $second);
    }

    public function test_it_resolves_interface_bindings(): void
    {
        $this->container->singleton(SomeInterface::class, SimpleService::class);

        $resolved = $this->container->get(SomeInterface::class);
        $this->assertInstanceOf(SimpleService::class, $resolved);
    }

    public function test_it_resolves_nested_dependencies_via_autowiring(): void
    {
        $resolved = $this->container->get(DependentService::class);

        $this->assertInstanceOf(DependentService::class, $resolved);
        $this->assertInstanceOf(SimpleService::class, $resolved->service);
    }

    public function test_it_uses_primitive_default_values_when_available(): void
    {
        $resolved = $this->container->get(PrimitiveDefaultService::class);

        $this->assertInstanceOf(PrimitiveDefaultService::class, $resolved);
        $this->assertSame('default_value', $resolved->param);
    }

    public function test_it_throws_not_found_exception_for_unknown_binding(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('non-existent-key');
    }

    public function test_it_throws_container_exception_for_unresolvable_primitives(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->get(UnresolvableService::class);
    }
}

interface SomeInterface {}

class SimpleService implements SomeInterface {}

class DependentService
{
    public function __construct(public SimpleService $service) {}
}

class PrimitiveDefaultService
{
    public function __construct(public string $param = 'default_value') {}
}

class UnresolvableService
{
    public function __construct(public string $param) {}
}
