<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Foundation\Application;
use Veldora\Framework\Mail\Mailable;
use Veldora\Framework\Mail\Mailer;
use Veldora\Framework\Mail\Transport\ArrayTransport;
use Veldora\Framework\Queue\Drivers\SyncDriver;
use Veldora\Framework\Queue\QueueManager;

class WelcomeMailable extends Mailable
{
    public function __construct(public string $userName)
    {
    }

    public function build(): static
    {
        return $this
            ->subject('Welcome to Veldora!')
            ->html("<h1>Hello {$this->userName}</h1><p>Welcome aboard.</p>")
            ->text("Hello {$this->userName}, welcome aboard.");
    }
}

class MailTest extends TestCase
{
    protected Application $app;
    protected Mailer $mailer;
    protected ArrayTransport $transport;
    protected QueueManager $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));

        $this->transport = new ArrayTransport();
        $this->mailer = new Mailer($this->app);
        $this->mailer->setDefaultDriver('array');
        $this->mailer->setTransport('array', $this->transport);
        $this->mailer->setTransport('mail', $this->transport);
        $this->mailer->setTransport('smtp', $this->transport);

        $this->app->instance(Mailer::class, $this->mailer);

        $this->queue = new QueueManager($this->app);
        $this->queue->extend('sync', fn () => new SyncDriver());
        $this->app->instance(QueueManager::class, $this->queue);
    }

    public function test_can_build_and_send_mailable(): void
    {
        $mailable = new WelcomeMailable('John Doe');
        $mailable->to('john@example.com', 'John');

        $this->mailer->send($mailable);

        $this->assertSame(1, $this->transport->count());
        $sent = $this->transport->lastMessage();
        $this->assertNotNull($sent);

        $this->assertSame(['john@example.com' => 'John'], $sent->to);
        $this->assertSame('Welcome to Veldora!', $sent->subject);
        $this->assertStringContainsString('Hello John Doe', $sent->htmlBody);
        $this->assertStringContainsString('Hello John Doe, welcome aboard.', $sent->textBody);
    }

    public function test_can_send_via_pending_mail_fluent_chaining(): void
    {
        $this->mailer->to('sarah@example.com')
            ->cc('manager@example.com')
            ->send(new WelcomeMailable('Sarah'));

        $this->assertSame(1, $this->transport->count());
        $sent = $this->transport->lastMessage();
        $this->assertNotNull($sent);

        $this->assertArrayHasKey('sarah@example.com', $sent->to);
        $this->assertArrayHasKey('manager@example.com', $sent->cc);
    }

    public function test_can_send_raw_text_email(): void
    {
        $this->mailer->raw('This is plain text content', function ($message) {
            $message->to('admin@example.com')->subject('System Alert');
        });

        $this->assertSame(1, $this->transport->count());
        $sent = $this->transport->lastMessage();
        $this->assertNotNull($sent);

        $this->assertSame('System Alert', $sent->subject);
        $this->assertSame('This is plain text content', $sent->textBody);
    }

    public function test_can_send_raw_html_email(): void
    {
        $this->mailer->html('<b>HTML content</b>', function ($message) {
            $message->to('dev@example.com')->subject('Release Notes');
        });

        $this->assertSame(1, $this->transport->count());
        $sent = $this->transport->lastMessage();
        $this->assertNotNull($sent);

        $this->assertSame('Release Notes', $sent->subject);
        $this->assertSame('<b>HTML content</b>', $sent->htmlBody);
    }

    public function test_can_queue_mailable(): void
    {
        $mailable = new WelcomeMailable('Queued User');
        $mailable->to('queued@example.com');

        $this->mailer->queue($mailable);

        // Under sync queue driver, it executes immediately and sends through transport
        $this->assertSame(1, $this->transport->count());
        $sent = $this->transport->lastMessage();
        $this->assertNotNull($sent);
        $this->assertArrayHasKey('queued@example.com', $sent->to);
    }

    public function test_global_mailer_helper(): void
    {
        mailer('global@example.com')->send(new WelcomeMailable('Global'));

        $this->assertSame(1, $this->transport->count());
        $sent = $this->transport->lastMessage();
        $this->assertNotNull($sent);
        $this->assertArrayHasKey('global@example.com', $sent->to);
    }
}
