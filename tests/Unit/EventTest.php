<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Events\Event;
use Veldora\Framework\Events\EventDispatcher;
use Veldora\Framework\Events\EventSubscriberInterface;
use Veldora\Framework\Events\Listener;
use Veldora\Framework\Events\HasEvents;
use Veldora\Framework\Foundation\Application;

class TestUserRegistered extends Event
{
    public function __construct(public string $email)
    {
    }
}

class TestSendWelcomeEmail implements Listener
{
    public static int $handledCount = 0;
    public static ?string $lastEmail = null;

    public function handle(Event $event): void
    {
        if ($event instanceof TestUserRegistered) {
            self::$handledCount++;
            self::$lastEmail = $event->email;
        }
    }
}

class TestStopPropagationEvent extends Event
{
}

class TestSubscriber implements EventSubscriberInterface
{
    public static int $event1Count = 0;
    public static int $event2Count = 0;

    public function subscribe(EventDispatcher $events): array
    {
        return [
            'test.event1' => 'onEvent1',
            'test.event2' => ['onEvent2', 10],
        ];
    }

    public function onEvent1(): void
    {
        self::$event1Count++;
    }

    public function onEvent2(): void
    {
        self::$event2Count++;
    }
}

class TestModelWithEvents
{
    use HasEvents;

    public function trigger(string $email): array
    {
        return $this->fireEvent(new TestUserRegistered($email));
    }
}

class EventTest extends TestCase
{
    protected Application $app;
    protected EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->dispatcher = new EventDispatcher($this->app);
        $this->app->singleton(EventDispatcher::class, fn () => $this->dispatcher);
        TestSendWelcomeEmail::$handledCount = 0;
        TestSendWelcomeEmail::$lastEmail = null;
        TestSubscriber::$event1Count = 0;
        TestSubscriber::$event2Count = 0;
    }

    public function test_it_dispatches_event_to_closure_listener(): void
    {
        $called = false;
        $receivedData = null;

        $this->dispatcher->listen('user.created', function ($data) use (&$called, &$receivedData) {
            $called = true;
            $receivedData = $data;
            return 'listener_result';
        });

        $results = $this->dispatcher->dispatch('user.created', ['id' => 42]);

        $this->assertTrue($called);
        $this->assertSame(['id' => 42], $receivedData);
        $this->assertSame(['listener_result'], $results);
    }

    public function test_it_dispatches_event_object_to_listener_class(): void
    {
        $this->dispatcher->listen(TestUserRegistered::class, TestSendWelcomeEmail::class);

        $event = new TestUserRegistered('john@example.com');
        $this->dispatcher->dispatch($event);

        $this->assertSame(1, TestSendWelcomeEmail::$handledCount);
        $this->assertSame('john@example.com', TestSendWelcomeEmail::$lastEmail);
    }

    public function test_it_dispatches_via_event_static_method(): void
    {
        $this->dispatcher->listen(TestUserRegistered::class, TestSendWelcomeEmail::class);

        TestUserRegistered::dispatch('alice@example.com');

        $this->assertSame(1, TestSendWelcomeEmail::$handledCount);
        $this->assertSame('alice@example.com', TestSendWelcomeEmail::$lastEmail);
    }

    public function test_it_respects_listener_priority(): void
    {
        $order = [];

        $this->dispatcher->listen('order.test', function () use (&$order) {
            $order[] = 'low';
        }, 0);

        $this->dispatcher->listen('order.test', function () use (&$order) {
            $order[] = 'high';
        }, 100);

        $this->dispatcher->listen('order.test', function () use (&$order) {
            $order[] = 'medium';
        }, 50);

        $this->dispatcher->dispatch('order.test');

        $this->assertSame(['high', 'medium', 'low'], $order);
    }

    public function test_it_handles_wildcard_listeners(): void
    {
        $caught = [];

        $this->dispatcher->listen('user.*', function ($event, $payload) use (&$caught) {
            $caught[] = $event;
        });

        $this->dispatcher->dispatch('user.created');
        $this->dispatcher->dispatch('user.updated');
        $this->dispatcher->dispatch('order.created');

        $this->assertSame(['user.created', 'user.updated'], $caught);
    }

    public function test_it_stops_propagation(): void
    {
        $step1 = false;
        $step2 = false;

        $this->dispatcher->listen(TestStopPropagationEvent::class, function (TestStopPropagationEvent $event) use (&$step1) {
            $step1 = true;
            $event->stopPropagation();
        }, 10);

        $this->dispatcher->listen(TestStopPropagationEvent::class, function () use (&$step2) {
            $step2 = true;
        }, 5);

        $event = new TestStopPropagationEvent();
        $this->dispatcher->dispatch($event);

        $this->assertTrue($step1);
        $this->assertFalse($step2);
    }

    public function test_it_supports_subscribers(): void
    {
        $this->dispatcher->subscribe(new TestSubscriber());

        $this->dispatcher->dispatch('test.event1');
        $this->dispatcher->dispatch('test.event2');

        $this->assertSame(1, TestSubscriber::$event1Count);
        $this->assertSame(1, TestSubscriber::$event2Count);
    }

    public function test_has_events_trait(): void
    {
        $this->dispatcher->listen(TestUserRegistered::class, TestSendWelcomeEmail::class);

        $model = new TestModelWithEvents();
        $model->trigger('bob@example.com');

        $this->assertSame(1, TestSendWelcomeEmail::$handledCount);
        $this->assertSame('bob@example.com', TestSendWelcomeEmail::$lastEmail);
    }

    public function test_global_event_helper(): void
    {
        $received = null;
        $this->dispatcher->listen('global.test', function ($msg) use (&$received) {
            $received = $msg;
        });

        event('global.test', 'hello_world');

        $this->assertSame('hello_world', $received);
    }
}
