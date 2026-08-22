<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Session\Session;
use Veldora\Framework\Session\ArrayDriver;

class SessionTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new Session(new ArrayDriver());
        $this->session->start();
    }

    public function test_it_can_store_and_retrieve_values(): void
    {
        $this->session->put('key', 'value');
        $this->assertTrue($this->session->has('key'));
        $this->assertSame('value', $this->session->get('key'));

        $this->session->forget('key');
        $this->assertFalse($this->session->has('key'));
    }

    public function test_it_handles_flash_values(): void
    {
        $this->session->flash('status', 'success');
        $this->assertTrue($this->session->has('status'));
        $this->assertSame('success', $this->session->get('status'));

        // Save session (ages flash data)
        $this->session->save();

        // Restart session (simulating next request)
        $nextSession = new Session($this->session->getDriver(), $this->session->getId());
        $nextSession->start();

        // Flash status should still be available in the immediate next request
        $this->assertTrue($nextSession->has('status'));
        $this->assertSame('success', $nextSession->get('status'));

        // Save again (clears the aged flash data)
        $nextSession->save();

        // Simulating second next request
        $thirdSession = new Session($nextSession->getDriver(), $nextSession->getId());
        $thirdSession->start();

        $this->assertFalse($thirdSession->has('status'));
    }

    public function test_it_generates_csrf_token(): void
    {
        $token = $this->session->csrfToken();
        $this->assertSame(64, strlen($token));

        $this->session->regenerateToken();
        $newToken = $this->session->csrfToken();
        $this->assertNotEquals($token, $newToken);
    }
}
