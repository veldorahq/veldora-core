<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Container;
use Veldora\Framework\Http\MiddlewareInterface;
use Veldora\Framework\Http\Pipeline;
use Veldora\Framework\Http\Request;
use Veldora\Framework\Http\Response;

class PipelineTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function test_it_executes_callable_pipes(): void
    {
        $request = new Request();
        $pipeline = new Pipeline($this->container);

        $response = $pipeline
            ->send($request)
            ->through([
                function (Request $req, \Closure $next) {
                    $res = $next($req);
                    $res->setHeader('X-Test-1', 'value-1');
                    return $res;
                },
                function (Request $req, \Closure $next) {
                    $res = $next($req);
                    $res->setHeader('X-Test-2', 'value-2');
                    return $res;
                }
            ])
            ->then(function (Request $req) {
                return new Response('destination');
            });

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('destination', $response->getContent());
        $this->assertSame('value-1', $response->getHeader('X-Test-1'));
        $this->assertSame('value-2', $response->getHeader('X-Test-2'));
    }

    public function test_it_resolves_and_executes_class_based_pipes(): void
    {
        $request = new Request();
        $pipeline = new Pipeline($this->container);

        // Bind our test middleware class to container
        $this->container->singleton(TestMiddleware::class);

        $response = $pipeline
            ->send($request)
            ->through([
                TestMiddleware::class
            ])
            ->then(function (Request $req) {
                return new Response('ok');
            });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('active', $response->getHeader('X-Middleware-Status'));
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);
        $response->setHeader('X-Middleware-Status', 'active');
        return $response;
    }
}
