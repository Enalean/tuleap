<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

Mock::generate('EventListener', 'Event1MockEventListener', array('doSomething'));
Mock::generate('EventListener', 'Event2MockEventListener', array('doSomethingElse'));

class EventManagerTest extends TuleapTestCase {

    function testSingleton() {
        $this->assertEqual(
                EventManager::instance(),
                EventManager::instance());
        $this->assertIsA(EventManager::instance(), 'EventManager');
    }
}

class EventManager_ProcessEvent_EventForTestingPurpose
{
    const NAME = 'whatever';
}

class EventManager_ProcessEventTest extends TuleapTestCase {

    function testProcessEvent1() {
        //The listeners
        $l1 = new Event1MockEventListener($this);
        $l1->expectOnce('doSomething');
        $l2 = new Event1MockEventListener($this);
        $l2->expectOnce('doSomething');
        $l3 = new Event2MockEventListener($this);
        $l3->expectNever('doSomethingElse');

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
        $params = array();
        $m->processEvent($e1, $params);
    }

    function testProcessEvent2() {
        //The listeners
        $l1 = new Event1MockEventListener($this);
        $l1->expectNever('doSomething');
        $l2 = new Event1MockEventListener($this);
        $l2->expectNever('doSomething');
        $l3 = new Event2MockEventListener($this);
        $l3->expectOnce('doSomethingElse');

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
        $params = array();
        $m->processEvent($e2, $params);
    }

    public function itCanSendAnEventObjectInsteadOfStringPlusParams()
    {
        $event         = new EventManager_ProcessEvent_EventForTestingPurpose();
        $listener      = new Event1MockEventListener($this);
        $event_manager = new EventManager();
        $event_manager->addListener($event::NAME, $listener, 'doSomething', false);

        expect($listener)->doSomething($event)->once();

        $event_manager->processEvent($event);
    }
}
