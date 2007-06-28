<?php
require_once('common/event/EventManager.class.php');
require_once('common/event/EventListener.class.php');
require_once('common/collection/Collection.class.php');
require_once('common/collection/LinkedList.class.php');
Mock::generate('EventListener', 'Event1MockEventListener', array('doSomething'));
Mock::generate('EventListener', 'Event2MockEventListener', array('CallHook'));
class TestEventManager extends EventManager {
    function &getListenersForEvent($event) {
        return $this->listeners->get(new String($event));
    }
}
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Test the class EventManager
 */
class EventManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function EventManagerTest($name = 'EventManager test') {
        $this->UnitTestCase($name);
    }

    function testSingleton() {
        $this->assertReference(
                EventManager::instance(),
                EventManager::instance());
        $this->assertIsA(EventManager::instance(), 'EventManager');
    }
    
    function testProcessEvent1() {
        //The listeners
        $l1 =& new Event1MockEventListener($this);
        $l1->expectOnce('doSomething');
        $l2 =& new Event1MockEventListener($this);
        $l2->expectOnce('doSomething');
        $l3 =& new Event2MockEventListener($this);
        $l3->expectNever('CallHook');
        
        //The events
        $e1 = 'event1';
        $e2 = 'event2';
        
        //The event Manager
        $m =& new EventManager();
        
        //We register the listeners for the type
        $m->addListener($e1, $l1, 'doSomething', false, 0);
        $m->addListener($e1, $l2, 'doSomething', false, 0);
        $m->addListener($e2, $l3, 'CallHook', true, 0);
        
        //We process event
        $params = array();
        $m->processEvent($e1, $params);
        
        $l1->tally();
        $l2->tally();
        $l3->tally();
    }
    
    function testProcessEvent2() {
        //The listeners
        $l1 =& new Event1MockEventListener($this);
        $l1->expectNever('doSomething');
        $l2 =& new Event1MockEventListener($this);
        $l2->expectNever('doSomething');
        $l3 =& new Event2MockEventListener($this);
        $l3->expectOnce('CallHook');
        
        //The events
        $e1 = 'event1';
        $e2 = 'event2';
        
        //The event Manager
        $m =& new EventManager();
        
        //We register the listeners for the type
        $m->addListener($e1, $l1, 'doSomething', false, 0);
        $m->addListener($e1, $l2, 'doSomething', false, 0);
        $m->addListener($e2, $l3, 'CallHook', true, 0);
        
        //We process event
        $params = array();
        $m->processEvent($e2, $params);
        
        $l1->tally();
        $l2->tally();
        $l3->tally();
    }
    
    function testPriority() {
        //The listeners
        $l1 =& new Event1MockEventListener($this);
        $l2 =& new Event1MockEventListener($this);
        $l3 =& new Event1MockEventListener($this);
        
        //The event
        $e = 'event1';
        
        //The event Manager
        $m =& new TestEventManager();
        
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
?>
