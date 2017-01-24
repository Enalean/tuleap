<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
Mock::generate('EventListener', 'Event2MockEventListener', array('CallHook'));

class EventManagerTest extends TuleapTestCase {

    function testSingleton() {
        $this->assertReference(
                EventManager::instance(),
                EventManager::instance());
        $this->assertIsA(EventManager::instance(), 'EventManager');
    }
}

class EventManager_ProcessEventTest extends TuleapTestCase {

    function testProcessEvent1() {
        //The listeners
        $l1 = new Event1MockEventListener($this);
        $l1->expectOnce('doSomething');
        $l2 = new Event1MockEventListener($this);
        $l2->expectOnce('doSomething');
        $l3 = new Event2MockEventListener($this);
        $l3->expectNever('CallHook');

        //The events
        $e1 = 'event1';
        $e2 = 'event2';

        //The event Manager
        $m = new EventManager();

        //We register the listeners for the type
        $m->addListener($e1, $l1, 'doSomething', false);
        $m->addListener($e1, $l2, 'doSomething', false);
        $m->addListener($e2, $l3, 'CallHook', true);

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
        $l3->expectOnce('CallHook');

        //The events
        $e1 = 'event1';
        $e2 = 'event2';

        //The event Manager
        $m = new EventManager();

        //We register the listeners for the type
        $m->addListener($e1, $l1, 'doSomething', false);
        $m->addListener($e1, $l2, 'doSomething', false);
        $m->addListener($e2, $l3, 'CallHook', true);

        //We process event
        $params = array();
        $m->processEvent($e2, $params);
    }

    public function itFallbackToCallHookIfGivenCallbackDoesNotExist() {
        $event  = 'an_event';
        $params = array('some_params');

        $listener = new Event2MockEventListener($this);
        stub($listener)->CallHook($event, $params)->once();

        $em = new EventManager();
        $em->addListener($event, $listener, 'doSomething', false);

        $em->processEvent($event, $params);
    }
}
