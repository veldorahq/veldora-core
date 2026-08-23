<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Database\Connection;
use Veldora\Framework\Database\Factories\Factory;
use Veldora\Framework\Database\HasFactory;
use Veldora\Framework\Database\Model;
use Veldora\Framework\Database\Schema\Blueprint;
use Veldora\Framework\Database\Schema\Schema;
use Veldora\Framework\Foundation\Application;

class FactoryTestUser extends Model
{
    use HasFactory;

    protected ?string $table = 'factory_users';
    protected array $fillable = ['name', 'email', 'status'];
}

class FactoryTestUserFactory extends Factory
{
    protected string $model = FactoryTestUser::class;

    public function definition(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ];
    }
}

class FactoryTest extends TestCase
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

        Schema::create('factory_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function test_factory_make_and_create(): void
    {
        $factory = new FactoryTestUserFactory();

        // Make (in-memory)
        $user = $factory->make(['name' => 'Custom Name']);
        $this->assertInstanceOf(FactoryTestUser::class, $user);
        $this->assertSame('Custom Name', $user->name);
        $this->assertNull($user->id);

        // Create (persisted)
        $persisted = $factory->create(['email' => 'persisted@example.com']);
        $this->assertInstanceOf(FactoryTestUser::class, $persisted);
        $this->assertNotNull($persisted->id);
        $this->assertSame('persisted@example.com', $persisted->email);

        // Count multiple
        $users = $factory->count(3)->create();
        $this->assertIsArray($users);
        $this->assertCount(3, $users);
    }
}
