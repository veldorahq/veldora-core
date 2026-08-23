<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use Veldora\Framework\Http\Response;
use Veldora\Framework\Http\Router;
use Veldora\Framework\Testing\TestCase;

class TestingFrameworkTest extends TestCase
{
    public function test_get_and_json_assertions(): void
    {
        /** @var Router $router */
        $router = $this->app->get(Router::class);
        $router->get('/api/ping', function () {
            return new Response(json_encode(['status' => 'pong', 'version' => '1.0.0']), 200, ['Content-Type' => 'application/json']);
        });

        $response = $this->get('/api/ping');

        $response->assertOk()
            ->assertStatus(200)
            ->assertJson(['status' => 'pong'])
            ->assertJsonFragment(['version' => '1.0.0'])
            ->assertSee('pong');

        $this->assertSame('pong', $response->json('status'));
    }

    public function test_post_and_redirect_assertions(): void
    {
        /** @var Router $router */
        $router = $this->app->get(Router::class);
        $router->post('/submit', function () {
            return new Response('', 302, ['Location' => '/dashboard']);
        });

        $response = $this->post('/submit', ['name' => 'John']);

        $response->assertRedirect('/dashboard');
    }
}
