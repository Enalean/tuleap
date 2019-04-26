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
        $params = [ 'foo' => 'bar' ];

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
        $params = [ 'foo' => 'bar' ];

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
        $event = new class {
            public const NAME = 'doSomething';
        };

        $listener = Mockery::mock();
        $listener->shouldReceive('doSomething')->with($event)->once();

        $event_manager = new EventManager();
        $event_manager->addListener($event::NAME, $listener, 'doSomething', false);

        $event_manager->processEvent($event);
    }
}
