<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Veldora\Framework\Database\Connection;
use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\Paginator;
use Veldora\Framework\Database\Relations\BelongsToMany;
use Veldora\Framework\Database\Relations\HasManyThrough;
use Veldora\Framework\Database\Schema\Blueprint;
use Veldora\Framework\Database\Schema\Schema;
use Veldora\Framework\Database\Seeder;
use Veldora\Framework\Foundation\Application;

class OrmCountry extends Model
{
    protected ?string $table = 'countries';
    protected array $fillable = ['id', 'name'];

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(OrmPost::class, OrmUser::class, 'country_id', 'user_id');
    }
}

class OrmUser extends Model
{
    protected ?string $table = 'users';
    protected array $fillable = ['name', 'email', 'country_id', 'is_active', 'age', 'settings'];
    protected array $casts = [
        'is_active' => 'bool',
        'age' => 'int',
        'settings' => 'array',
        'created_at' => 'datetime',
    ];
    protected array $hidden = ['password'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(OrmRole::class, 'role_user', 'user_id', 'role_id');
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', '=', 1);
    }
}

class OrmRole extends Model
{
    protected ?string $table = 'roles';
    protected array $fillable = ['id', 'name'];
}

class OrmPost extends Model
{
    protected ?string $table = 'posts';
    protected array $fillable = ['user_id', 'title', 'views'];
}

class SampleUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->table('users')->insert([
            'name' => 'Seeded User',
            'email' => 'seeded@example.com',
            'country_id' => 1,
            'is_active' => 1,
            'age' => 25,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

class SampleDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SampleUserSeeder::class);
    }
}

class ORMAdvancedTest extends TestCase
{
    protected Application $app;
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));

        $this->connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->app->instance(Connection::class, $this->connection);
        Schema::setConnection($this->connection);

        // Create test schema tables
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('country_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('age')->default(0);
            $table->text('settings')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('role_id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('title');
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    public function test_mass_assignment_and_model_create(): void
    {
        $user = OrmUser::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'is_active' => true,
            'age' => '28',
            'settings' => ['theme' => 'dark'],
            'unallowed_column' => 'hacker_val',
        ]);

        $this->assertSame('Alice', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertSame(28, $user->age);
        $this->assertSame(['theme' => 'dark'], $user->settings);
        $this->assertNull($user->unallowed_column);
    }

    public function test_attribute_casting_and_serialization(): void
    {
        $user = new OrmUser();
        $user->name = 'Bob';
        $user->email = 'bob@example.com';
        $user->age = '35';
        $user->is_active = 1;
        $user->settings = ['notifications' => true];
        $user->password = 'secret123';
        $user->save();

        $loaded = OrmUser::find($user->id);
        $this->assertSame(35, $loaded->age);
        $this->assertTrue($loaded->is_active);
        $this->assertSame(['notifications' => true], $loaded->settings);
        $this->assertInstanceOf(DateTimeImmutable::class, $loaded->created_at);

        $array = $loaded->toArray();
        $this->assertArrayNotHasKey('password', $array);
        $this->assertSame('Bob', $array['name']);

        $json = $loaded->toJson();
        $this->assertStringNotContainsString('secret123', $json);
        $this->assertStringContainsString('"notifications":true', $json);
    }

    public function test_belongs_to_many_relation_with_attach_detach_sync_toggle(): void
    {
        $user = OrmUser::create(['name' => 'Carol', 'email' => 'carol@example.com']);
        $adminRole = OrmRole::create(['id' => 1, 'name' => 'Admin']);
        $editorRole = OrmRole::create(['id' => 2, 'name' => 'Editor']);
        $viewerRole = OrmRole::create(['id' => 3, 'name' => 'Viewer']);

        // Attach
        $user->roles()->attach([1, 2]);
        $roles = $user->roles;
        $this->assertCount(2, $roles);
        $roleNames = array_map(fn ($r) => $r->name, $roles);
        $this->assertContains('Admin', $roleNames);
        $this->assertContains('Editor', $roleNames);

        // Detach
        $user->roles()->detach(1);
        unset($user->roles);
        $rolesAfterDetach = $user->roles;
        $this->assertCount(1, $rolesAfterDetach);
        $this->assertSame('Editor', $rolesAfterDetach[0]->name);

        // Sync
        $user->roles()->sync([1, 3]);
        unset($user->roles);
        $rolesAfterSync = $user->roles;
        $this->assertCount(2, $rolesAfterSync);
        $syncedNames = array_map(fn ($r) => $r->name, $rolesAfterSync);
        $this->assertContains('Admin', $syncedNames);
        $this->assertContains('Viewer', $syncedNames);
        $this->assertNotContains('Editor', $syncedNames);

        // Toggle
        $user->roles()->toggle([1, 2]); // Detaches 1, attaches 2
        unset($user->roles);
        $rolesAfterToggle = $user->roles;
        $this->assertCount(2, $rolesAfterToggle);
        $toggledNames = array_map(fn ($r) => $r->name, $rolesAfterToggle);
        $this->assertContains('Viewer', $toggledNames);
        $this->assertContains('Editor', $toggledNames);
        $this->assertNotContains('Admin', $toggledNames);
    }

    public function test_has_many_through_relation(): void
    {
        $country = OrmCountry::create(['id' => 10, 'name' => 'Bangladesh']);
        $user1 = OrmUser::create(['name' => 'Dev1', 'email' => 'dev1@example.com', 'country_id' => 10]);
        $user2 = OrmUser::create(['name' => 'Dev2', 'email' => 'dev2@example.com', 'country_id' => 10]);
        $otherUser = OrmUser::create(['name' => 'Other', 'email' => 'other@example.com', 'country_id' => 99]);

        OrmPost::create(['user_id' => $user1->id, 'title' => 'Post 1', 'views' => 100]);
        OrmPost::create(['user_id' => $user2->id, 'title' => 'Post 2', 'views' => 200]);
        OrmPost::create(['user_id' => $otherUser->id, 'title' => 'Post 3', 'views' => 300]);

        $countryPosts = $country->posts;
        $this->assertCount(2, $countryPosts);
        $postTitles = array_map(fn ($p) => $p->title, $countryPosts);
        $this->assertContains('Post 1', $postTitles);
        $this->assertContains('Post 2', $postTitles);
        $this->assertNotContains('Post 3', $postTitles);
    }

    public function test_query_builder_where_in_between_null_and_aggregations(): void
    {
        OrmPost::create(['user_id' => 1, 'title' => 'Alpha', 'views' => 10]);
        OrmPost::create(['user_id' => 1, 'title' => 'Beta', 'views' => 20]);
        OrmPost::create(['user_id' => 2, 'title' => 'Gamma', 'views' => 50]);
        OrmPost::create(['user_id' => 3, 'title' => 'Delta', 'views' => 100]);

        // whereIn
        $inPosts = $this->connection->table('posts')->whereIn('title', ['Alpha', 'Gamma'])->get();
        $this->assertCount(2, $inPosts);

        // whereBetween
        $betweenPosts = $this->connection->table('posts')->whereBetween('views', [15, 60])->get();
        $this->assertCount(2, $betweenPosts);

        // Aggregations
        $this->assertSame(4, $this->connection->table('posts')->count());
        $this->assertSame(180, (int) $this->connection->table('posts')->sum('views'));
        $this->assertSame(45.0, $this->connection->table('posts')->avg('views'));
        $this->assertSame(10, (int) $this->connection->table('posts')->min('views'));
        $this->assertSame(100, (int) $this->connection->table('posts')->max('views'));
        $this->assertTrue($this->connection->table('posts')->where('title', '=', 'Alpha')->exists());
        $this->assertFalse($this->connection->table('posts')->where('title', '=', 'Omega')->exists());
    }

    public function test_query_chunking(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            OrmPost::create(['user_id' => 1, 'title' => "Post {$i}", 'views' => $i * 5]);
        }

        $chunksProcessed = 0;
        $totalItems = 0;

        OrmPost::chunk(4, function ($posts, $page) use (&$chunksProcessed, &$totalItems) {
            $chunksProcessed++;
            $totalItems += count($posts);
            $this->assertInstanceOf(OrmPost::class, $posts[0]);
        });

        $this->assertSame(3, $chunksProcessed); // 4 + 4 + 2
        $this->assertSame(10, $totalItems);
    }

    public function test_paginator_and_links(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            OrmUser::create(['name' => "User {$i}", 'email' => "user{$i}@example.com"]);
        }

        $paginator = OrmUser::paginate(10, 2);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame(25, $paginator->total());
        $this->assertSame(10, $paginator->perPage());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame(3, $paginator->lastPage());
        $this->assertCount(10, $paginator->items());
        $this->assertInstanceOf(OrmUser::class, $paginator->items()[0]);

        $this->assertTrue($paginator->hasPages());
        $this->assertTrue($paginator->hasMorePages());
        $this->assertNotNull($paginator->nextPageUrl());
        $this->assertNotNull($paginator->previousPageUrl());

        $html = $paginator->links();
        $this->assertStringContainsString('v-pagination', $html);
        $this->assertStringContainsString('Showing', $html);
        $this->assertStringContainsString('page=1', $html);
        $this->assertStringContainsString('page=3', $html);
    }

    public function test_model_local_scopes(): void
    {
        OrmUser::create(['name' => 'Active 1', 'email' => 'a1@example.com', 'is_active' => true]);
        OrmUser::create(['name' => 'Active 2', 'email' => 'a2@example.com', 'is_active' => true]);
        OrmUser::create(['name' => 'Inactive', 'email' => 'in@example.com', 'is_active' => false]);

        $activeUsers = OrmUser::active()->get();
        $this->assertCount(2, $activeUsers);
    }

    public function test_model_lifecycle_hooks(): void
    {
        $eventsFired = [];

        OrmPost::creating(function ($post) use (&$eventsFired) {
            $eventsFired[] = 'creating';
        });

        OrmPost::created(function ($post) use (&$eventsFired) {
            $eventsFired[] = 'created';
        });

        $post = OrmPost::create(['user_id' => 1, 'title' => 'Hook Test', 'views' => 1]);

        $this->assertContains('creating', $eventsFired);
        $this->assertContains('created', $eventsFired);
    }

    public function test_database_seeder(): void
    {
        $seeder = new SampleDatabaseSeeder($this->connection);
        $seeder->run();

        $seededUser = OrmUser::where('email', '=', 'seeded@example.com')->first();
        $this->assertNotNull($seededUser);
        $this->assertSame('Seeded User', $seededUser['name']);
    }
}
