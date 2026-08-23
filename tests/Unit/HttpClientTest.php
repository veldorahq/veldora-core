<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Http\Client\Http;
use Veldora\Framework\Http\Client\Response;

class HttpClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Http::resetFakes();
        parent::tearDown();
    }

    public function test_fake_responses_and_matching(): void
    {
        Http::fake([
            'https://api.github.com/users/*' => Http::response([
                'login' => 'octocat',
                'id' => 1,
                'name' => 'The Octocat',
            ], 200, ['X-RateLimit-Limit' => '60']),
            'https://api.test/error' => Http::response(['error' => 'Not Found'], 404),
        ]);

        // GET request
        $response = Http::withToken('secret123')
            ->acceptJson()
            ->get('https://api.github.com/users/octocat');

        $this->assertTrue($response->successful());
        $this->assertTrue($response->ok());
        $this->assertFalse($response->failed());
        $this->assertSame(200, $response->status());
        $this->assertSame('The Octocat', $response->json('name'));
        $this->assertSame('60', $response->header('X-RateLimit-Limit'));
        $this->assertSame('octocat', $response['login']);

        // 404 request
        $errorRes = Http::get('https://api.test/error');
        $this->assertTrue($errorRes->failed());
        $this->assertTrue($errorRes->clientError());
        $this->assertSame(404, $errorRes->status());
        $this->assertSame('Not Found', $errorRes->json('error'));
    }

    public function test_fluent_methods_and_headers(): void
    {
        $capturedUrl = null;
        $capturedMethod = null;

        Http::fake(function (string $url, string $method) use (&$capturedUrl, &$capturedMethod) {
            $capturedUrl = $url;
            $capturedMethod = $method;
            return Http::response(['created' => true], 201);
        });

        $res = Http::baseUrl('https://api.example.com/v1')
            ->withBasicAuth('admin', 'password')
            ->withHeaders(['X-Custom' => 'HeaderVal'])
            ->post('posts', ['title' => 'Veldora Framework']);

        $this->assertSame(201, $res->status());
        $this->assertTrue($res->successful());
        $this->assertTrue($res['created']);
        $this->assertSame('https://api.example.com/v1/posts', $capturedUrl);
        $this->assertSame('POST', $capturedMethod);
    }
}
