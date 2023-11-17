<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

class EventManagerTest extends TestCase // phpcs:ignore
{
    /**
     * @test
     */
    public function itCallsAClosure(): void
    {
        $event_manager = new EventManager();

        $test = $this;
        $event_manager->addClosureOnEvent(
            'foo',
            function ($event, $params) use ($test) {
                $test->assertEquals('bar', $params['stuff']);
            }
        );

        $event_manager->processEvent('foo', ['stuff' => 'bar']);
    }

    public function testSingleton(): void
    {
        self::assertEquals(
            EventManager::instance(),
            EventManager::instance()
        );
        self::assertInstanceOf(EventManager::class, EventManager::instance());
    }

    public function testProcessEvent1(): void
    {
        $params = ['foo' => 'bar'];

        //The listeners
        $l1 = new class {
            public ?array $params = null;

            public function doSomething(array $params): void
            {
                $this->params = $params;
            }
        };
        $l2 = new class {
            public ?array $params = null;

            public function doSomething(array $params): void
            {
                $this->params = $params;
            }
        };
        $l3 = new class {
            public ?string $event = null;
            public ?array $params = null;

            public function doSomethingElse(string $event, array $params): void
            {
                $this->event  = $event;
                $this->params = $params;
            }
        };

        //The events
        $e1 = 'event1';
        $e2 = 'event2';

        //The event Manager
        $m = new EventManager();

        //We register the listeners for the type
        $m->addListener($e1, $l1, 'doSomething', false);
        $m->addListener($e1, $l2, 'doSomething', false);
        $m->addListener($e2, $l3, 'doSomethingElse', true);

        //We process event
        $m->processEvent($e1, $params);
        self::assertEqualsCanonicalizing($params, $l1->params);
        self::assertEqualsCanonicalizing($params, $l2->params);
        self::assertNull($l3->event);
        self::assertNull($l3->params);
    }

    public function testProcessEvent2(): void
    {
        $params = ['foo' => 'bar'];

        //The listeners
        $l1 = new class {
            public ?array $params = null;

            public function doSomething(array $params): void
            {
                $this->params = $params;
            }
        };
        $l2 = new class {
            public ?array $params = null;

            public function doSomething(array $params): void
            {
                $this->params = $params;
            }
        };
        $l3 = new class {
            public ?string $event = null;
            public ?array $params = null;

            public function doSomethingElse(string $event, array $params): void
            {
                $this->event  = $event;
                $this->params = $params;
            }
        };

        //The events
        $e1 = 'event1';
        $e2 = 'event2';

        //The event Manager
        $m = new EventManager();

        //We register the listeners for the type
        $m->addListener($e1, $l1, 'doSomething', false);
        $m->addListener($e1, $l2, 'doSomething', false);
        $m->addListener($e2, $l3, 'doSomethingElse', true);

        //We process event
        $m->processEvent($e2, $params);
        self::assertNull($l1->params);
        self::assertNull($l2->params);
        self::assertSame($e2, $l3->event);
        self::assertEqualsCanonicalizing($params, $l3->params);
    }

    public function testItCanSendAnEventObjectWithNameInsteadOfStringPlusParams(): void
    {
        $event = new class {
            public const NAME = 'doSomething';
        };

        $listener = new class {
            public ?object $event = null;

            public function doSomething(object $event): void
            {
                $this->event = $event;
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener($event::NAME, $listener, 'doSomething', false);

        $event_manager->processEvent($event);
        self::assertSame($event, $listener->event);
    }

    public function testItCanSendAnEventObjectWithoutName(): void
    {
        $event = new class {
        };

        $listener = new class {
            public ?object $event = null;

            public function doSomething(object $event): void
            {
                $this->event = $event;
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener($event::class, $listener, 'doSomething', false);

        $event_manager->processEvent($event);
        self::assertSame($event, $listener->event);
    }

    public function testItStopsEventPropagation(): void
    {
        $stoppable_event = new class implements StoppableEventInterface {
            public const NAME = 'foo';

            public bool $stop = false;

            public function isPropagationStopped(): bool
            {
                return $this->stop;
            }
        };

        $listener_1 = new class {
            public bool $was_called = false;

            public function handleFoo(object $event): void
            {
                $this->was_called = true;
            }
        };

        $listener_2 = new class {
            public bool $was_called = false;

            public function handleFoo(object $event): void
            {
                $this->was_called = true;
                $event->stop      = true;
            }
        };

        $listener_3 = new class {
            public bool $was_called = false;

            public function handleFoo(object $event): void
            {
                $this->was_called = true;
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener('foo', $listener_1, 'handleFoo', false);
        $event_manager->addListener('foo', $listener_2, 'handleFoo', false);
        $event_manager->addListener('foo', $listener_3, 'handleFoo', false);

        $event_manager->dispatch($stoppable_event);

        self::assertTrue($listener_1->was_called);
        self::assertTrue($listener_2->was_called);
        self::assertFalse($listener_3->was_called);
    }

    public function testItDispatchWithoutName(): void
    {
        $event_manager = new EventManager();

        $unnamed_event = new class {
            public int $id = 0;
        };

        $listener = new class {
            public function handleEvent(object $event): void
            {
                $event->id = 12;
            }
        };

        $event_manager->addListener($unnamed_event::class, $listener, 'handleEvent', false);

        $unnamed_event = $event_manager->dispatch($unnamed_event);

        self::assertEquals(12, $unnamed_event->id);
    }
}
