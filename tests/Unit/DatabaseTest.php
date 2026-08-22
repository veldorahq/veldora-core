<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Database\Connection;
use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\QueryBuilder;
use Veldora\Framework\Database\Schema\Blueprint;
use Veldora\Framework\Database\Schema\Schema;
use Veldora\Framework\Foundation\Application;

class DatabaseTest extends TestCase
{
    protected Application $app;
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app = new Application(dirname(__DIR__, 2));
        
        // Register connection for SQLite in-memory
        $this->connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:'
        ]);
        
        $this->app->singleton(Connection::class, fn() => $this->connection);
        Schema::setConnection($this->connection);

        // Run migrations/tables setup manually for test DB
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('role_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        parent::tearDown();
    }

    public function test_it_can_insert_and_select_via_query_builder(): void
    {
        $builder = new QueryBuilder($this->connection);
        $builder->table('roles');

        $inserted = $builder->insert(['name' => 'Administrator']);
        $this->assertTrue($inserted);

        $results = $builder->select(['name'])->where('name', '=', 'Administrator')->get();
        $this->assertCount(1, $results);
        $this->assertSame('Administrator', $results[0]['name']);
    }

    public function test_orm_crud_and_timestamps(): void
    {
        // 1. Create and Save
        $role = new TestRole();
        $role->name = 'Editor';
        $saved = $role->save();

        $this->assertTrue($saved);
        $this->assertNotNull($role->id);
        $this->assertNotNull($role->created_at);
        $this->assertNotNull($role->updated_at);

        // 2. Read
        $found = TestRole::find($role->id);
        $this->assertInstanceOf(TestRole::class, $found);
        $this->assertSame('Editor', $found->name);

        // 3. Update
        $found->name = 'Senior Editor';
        $updated = $found->save();
        $this->assertTrue($updated);

        $refetched = TestRole::find($role->id);
        $this->assertSame('Senior Editor', $refetched->name);

        // 4. Delete
        $deleted = $refetched->delete();
        $this->assertTrue($deleted);
        $this->assertNull(TestRole::find($role->id));
    }

    public function test_relations_has_many_and_belongs_to(): void
    {
        $role = new TestRole();
        $role->name = 'Manager';
        $role->save();

        $user1 = new TestUser();
        $user1->name = 'Alice';
        $user1->role_id = $role->id;
        $user1->save();

        $user2 = new TestUser();
        $user2->name = 'Bob';
        $user2->role_id = $role->id;
        $user2->save();

        // Check belongsTo
        $fetchedUser = TestUser::find($user1->id);
        $this->assertInstanceOf(TestUser::class, $fetchedUser);
        $this->assertInstanceOf(TestRole::class, $fetchedUser->role);
        $this->assertSame('Manager', $fetchedUser->role->name);

        // Check hasMany
        $fetchedRole = TestRole::find($role->id);
        $this->assertInstanceOf(TestRole::class, $fetchedRole);
        
        $users = $fetchedRole->users;
        $this->assertCount(2, $users);
        $this->assertSame('Alice', $users[0]->name);
        $this->assertSame('Bob', $users[1]->name);
    }

    public function test_soft_deletes(): void
    {
        $user = new TestUser();
        $user->name = 'DeletedUser';
        $user->role_id = 99;
        $user->save();

        $this->assertNull($user->deleted_at);

        $user->delete();

        // Refetch and check deleted_at is filled
        $stmt = $this->connection->getPdo()->query("SELECT deleted_at FROM users WHERE id = {$user->id}");
        $row = $stmt->fetch();
        $this->assertNotNull($row['deleted_at']);
    }
}

class TestRole extends Model
{
    protected ?string $table = 'roles';

    /**
     * @return \Veldora\Framework\Database\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(TestUser::class, 'role_id', 'id');
    }
}

class TestUser extends Model
{
    protected ?string $table = 'users';
    protected bool $softDelete = true;

    /**
     * @return \Veldora\Framework\Database\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(TestRole::class, 'role_id', 'id');
    }
}
