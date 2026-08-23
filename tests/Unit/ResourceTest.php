<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Database\Paginator;
use Veldora\Framework\Http\Request;
use Veldora\Framework\Http\Resources\JsonResource;
use Veldora\Framework\Http\Resources\ResourceCollection;

class UserApiResource extends JsonResource
{
    public function toArray(?Request $request = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

class ResourceTest extends TestCase
{
    public function test_single_resource_transformation(): void
    {
        $data = ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'secret' => 'hide_me'];
        $resource = new UserApiResource($data);

        $resolved = $resource->resolve();
        $this->assertArrayHasKey('data', $resolved);
        $this->assertSame(1, $resolved['data']['id']);
        $this->assertSame('Alice', $resolved['data']['name']);
        $this->assertArrayNotHasKey('secret', $resolved['data']);

        // Property access
        $this->assertSame('Alice', $resource->name);
    }

    public function test_resource_additional_metadata(): void
    {
        $data = ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'];
        $resource = (new UserApiResource($data))->additional(['status' => 'success', 'code' => 200]);

        $resolved = $resource->resolve();
        $this->assertSame('success', $resolved['status']);
        $this->assertSame(200, $resolved['code']);
        $this->assertSame('Bob', $resolved['data']['name']);
    }

    public function test_resource_collection_and_paginator(): void
    {
        $users = [
            ['id' => 1, 'name' => 'User 1', 'email' => 'u1@example.com'],
            ['id' => 2, 'name' => 'User 2', 'email' => 'u2@example.com'],
        ];

        $collection = UserApiResource::collection($users);
        $resolved = $collection->resolve();

        $this->assertCount(2, $resolved['data']);
        $this->assertSame('User 1', $resolved['data'][0]['name']);
        $this->assertSame('User 2', $resolved['data'][1]['name']);

        // With Paginator
        $paginator = new Paginator($users, 50, 10, 1, '/api/users');
        $paginatedCollection = UserApiResource::collection($paginator);
        $paginatedResolved = $paginatedCollection->resolve();

        $this->assertArrayHasKey('links', $paginatedResolved);
        $this->assertArrayHasKey('meta', $paginatedResolved);
        $this->assertSame(50, $paginatedResolved['meta']['total']);
        $this->assertSame(5, $paginatedResolved['meta']['last_page']);
    }
}
