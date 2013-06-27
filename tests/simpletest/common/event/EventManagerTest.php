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


require_once('common/event/EventManager.class.php');
require_once('common/event/EventListener.class.php');
require_once('common/collection/Collection.class.php');
require_once('common/collection/LinkedList.class.php');
Mock::generate('EventListener', 'Event1MockEventListener', array('doSomething'));
Mock::generate('EventListener', 'Event2MockEventListener', array('CallHook'));


class EventManagerTest extends UnitTestCase {

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
        $m->addListener($e1, $l1, 'doSomething', false, 0);
        $m->addListener($e1, $l2, 'doSomething', false, 0);
        $m->addListener($e2, $l3, 'CallHook', true, 0);
        
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
        $m->addListener($e1, $l1, 'doSomething', false, 0);
        $m->addListener($e1, $l2, 'doSomething', false, 0);
        $m->addListener($e2, $l3, 'CallHook', true, 0);
        
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
        $em->addListener($event, $listener, 'doSomething', false, 0);

        $em->processEvent($event, $params);
    }
}

class  EventManager_PriorityTest extends UnitTestCase {

    function testPriority() {
        //The listeners
        $l1 = new Event1MockEventListener($this);
        $l2 = new Event1MockEventListener($this);
        $l3 = new Event1MockEventListener($this);
        
        //The event
        $e = 'event1';
        
        //The event Manager
        $m = new TestEventManager();
        
        //We register the listeners for the type
        $m->addListener($e, $l1, 'doSomething', false, 0);
        $m->addListener($e, $l2, 'doSomething', false, 0);
        $m->addListener($e, $l3, 'doSomething', false, 10);
        
        $listeners =& $m->getListenersForEvent($e);
        $it =& $listeners->iterator();
        $entry =& $it->current();
        $this->assertReference($l3, $entry['listener']);
        $it->next();
        $entry =& $it->current();
        $this->assertReference($l1, $entry['listener']);
        $it->next();
        $entry =& $it->current();
        $this->assertReference($l2, $entry['listener']);
    }
}

class TestEventManager extends EventManager {
    function getListenersForEvent($event) {
        return $this->listeners->get($event);
    }
}
?>
