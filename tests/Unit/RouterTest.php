<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Container;
use Veldora\Framework\Foundation\Exception\NotFoundException;
use Veldora\Framework\Http\Request;
use Veldora\Framework\Http\Response;
use Veldora\Framework\Http\Router;

class RouterTest extends TestCase
{
    private Container $container;
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    public function test_it_can_match_static_routes(): void
    {
        $this->router->get('/home', function () {
            return 'home-response';
        });

        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/home',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('home-response', $response->getContent());
    }

    public function test_it_extracts_dynamic_parameters(): void
    {
        $this->router->get('/users/{id}', function (string $id) {
            return "user-{$id}";
        });

        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/users/42',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $this->router->dispatch($request);
        $this->assertSame('user-42', $response->getContent());
    }

    public function test_it_handles_group_prefixes_and_middlewares(): void
    {
        $this->router->group(['prefix' => '/api', 'middleware' => 'fake-middleware'], function (Router $router) {
            $router->get('/v1/status', function () {
                return 'ok';
            });
        });

        $routes = $this->router->getRoutes();
        $this->assertCount(1, $routes);
        
        $route = $routes[0];
        $this->assertSame('/api/v1/status', $route->getUri());
        $this->assertSame(['fake-middleware'], $route->getMiddleware());
    }

    public function test_it_resolves_autowired_request_dependency_in_action(): void
    {
        $this->router->get('/test', function (Request $request) {
            return $request->input('name');
        });

        $request = new Request(['name' => 'Veldora'], [], [], [], [
            'REQUEST_URI' => '/test',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $this->router->dispatch($request);
        $this->assertSame('Veldora', $response->getContent());
    }

    public function test_it_throws_not_found_exception_if_no_route_matches(): void
    {
        $this->expectException(NotFoundException::class);

        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/missing-route',
            'REQUEST_METHOD' => 'GET'
        ]);

        $this->router->dispatch($request);
    }
}
