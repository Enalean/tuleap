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
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @test
     */
    public function itCallsAClosure()
    {
        $event_manager = new EventManager();

        $test = $this;
        $event_manager->addClosureOnEvent(
            'foo',
            function ($event, $params) use ($test) {
                $test->assertEquals($params['stuff'], 'bar');
            }
        );

        $event_manager->processEvent('foo', ['stuff' => 'bar']);
    }

    public function testSingleton()
    {
        $this->assertEquals(
            EventManager::instance(),
            EventManager::instance()
        );
        $this->assertInstanceOf(EventManager::class, EventManager::instance());
    }

    public function testProcessEvent1()
    {
        $params = ['foo' => 'bar'];

        //The listeners
        $l1 = Mockery::mock();
        $l1->shouldReceive('doSomething')->with($params)->once();
        $l2 = Mockery::mock();
        $l2->shouldReceive('doSomething')->with($params)->once();
        $l3 = Mockery::mock();
        $l3->shouldNotReceive('doSomethingElse');

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
    }

    public function testProcessEvent2()
    {
        $params = ['foo' => 'bar'];

        //The listeners
        $l1 = Mockery::mock();
        $l1->shouldNotReceive('doSomething');
        $l2 = Mockery::mock();
        $l2->shouldNotReceive('doSomething');
        $l3 = Mockery::mock();
        $l3->shouldReceive('doSomethingElse')->with('event2', $params)->once();

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
    }

    public function testItCanSendAnEventObjectInsteadOfStringPlusParams()
    {
        $event = new class
        {
            public const NAME = 'doSomething';
        };

        $listener = Mockery::mock();
        $listener->shouldReceive('doSomething')->with($event)->once();

        $event_manager = new EventManager();
        $event_manager->addListener($event::NAME, $listener, 'doSomething', false);

        $event_manager->processEvent($event);
    }

    public function testItStopsEventPropagation() : void
    {
        $stoppable_event = new class implements StoppableEventInterface {
            public const NAME = 'foo';

            public $stop = false;

            public function isPropagationStopped(): bool
            {
                return $this->stop;
            }
        };

        $listener_1 = new class {
            public $was_called = false;

            public function handleFoo(object $event)
            {
                $this->was_called = true;
            }
        };

        $listener_2 = new class {
            public $was_called = false;

            public function handleFoo(object $event)
            {
                $this->was_called = true;
                $event->stop = true;
            }
        };

        $listener_3 = new class {
            public $was_called = false;

            public function handleFoo(object $event)
            {
                $this->was_called = true;
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener('foo', $listener_1, 'handleFoo', false);
        $event_manager->addListener('foo', $listener_2, 'handleFoo', false);
        $event_manager->addListener('foo', $listener_3, 'handleFoo', false);

        $event_manager->dispatch($stoppable_event);

        $this->assertEquals(true, $listener_1->was_called);
        $this->assertEquals(true, $listener_2->was_called);
        $this->assertEquals(false, $listener_3->was_called);
    }

    public function testItDispatchWithoutName()
    {
        $event_manager = new EventManager();

        $unnamed_event = new class {
            public $id = 0;
        };

        $listener = new class {
            public function handleEvent(object $event)
            {
                $event->id = 12;
            }
        };

        $event_manager->addListener(get_class($unnamed_event), $listener, 'handleEvent', false);

        $unnamed_event = $event_manager->dispatch($unnamed_event);

        $this->assertEquals(12, $unnamed_event->id);
    }
}
