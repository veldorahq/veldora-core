<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Auth\SessionGuard;
use Veldora\Framework\Session\Session;
use Veldora\Framework\Session\ArrayDriver;
use Veldora\Framework\Http\CookieJar;
use Veldora\Framework\Database\Model;

class AuthTest extends TestCase
{
    private Session $session;
    private CookieJar $cookieJar;
    private SessionGuard $guard;
    private AuthTestUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->session = new Session(new ArrayDriver());
        $this->session->start();
        
        $this->cookieJar = new CookieJar();
        
        $this->user = new AuthTestUser();
        $this->user->id = 42;
        $this->user->is_admin = 1;

        // Create a guard targeting our AuthTestUser class
        $this->guard = new SessionGuard($this->session, AuthTestUser::class, $this->cookieJar);
    }

    public function test_it_can_login_user_and_retrieve_from_session(): void
    {
        $this->assertFalse($this->guard->check());
        $this->assertTrue($this->guard->guest());

        // Simulate static lookup resolver callback within guard
        $reflector = new \ReflectionClass(SessionGuard::class);
        $property = $reflector->getProperty('user');
        $property->setAccessible(true);
        $property->setValue($this->guard, $this->user);

        $this->guard->login($this->user);

        $this->assertTrue($this->guard->check());
        $this->assertSame(42, $this->guard->id());
        $this->assertSame($this->user, $this->guard->user());
    }

    public function test_it_verifies_admin_support(): void
    {
        $reflector = new \ReflectionClass(SessionGuard::class);
        $property = $reflector->getProperty('user');
        $property->setAccessible(true);
        $property->setValue($this->guard, $this->user);

        $this->assertTrue($this->guard->isAdmin());

        $this->user->is_admin = 0;
        $this->assertFalse($this->guard->isAdmin());
    }

    public function test_it_queues_remember_cookie_on_login(): void
    {
        $this->guard->login($this->user, true);
        
        // Assert remember token was updated on user
        $this->assertNotNull($this->user->remember_token);
        $this->assertSame(60, strlen($this->user->remember_token));

        // Assert cookie was queued
        $queued = $this->cookieJar->flushQueuedCookies();
        $this->assertCount(1, $queued);
        $this->assertSame('remember_web', $queued[0]['name']);
        $this->assertTrue($queued[0]['signed']);
    }

    public function test_it_can_logout_user(): void
    {
        $reflector = new \ReflectionClass(SessionGuard::class);
        $property = $reflector->getProperty('user');
        $property->setAccessible(true);
        $property->setValue($this->guard, $this->user);

        $this->guard->logout();

        $this->assertFalse($this->guard->check());
        $this->assertNull($this->guard->user());
        $this->assertNull($this->session->get('_auth_user_id'));
    }
}

class AuthTestUser extends Model
{
    public mixed $id = null;
    public ?string $remember_token = null;
    public int $is_admin = 0;

    public function save(): bool
    {
        return true;
    }

    public static function find(mixed $id): ?static
    {
        $user = new static();
        $user->id = $id;
        $user->is_admin = 1;
        return $user;
    }
}
