<?php
require_once('common/collection/PrioritizedMultiMap.class.php');
require_once('common/collection/Map.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * EventManager
 */
class EventManager {
    
    var $listeners;
    
    function EventManager() {
        $this->listeners = new PrioritizedMultiMap();
    }
    
    function &instance() {
        static $_eventmanager_instance;
        if (!$_eventmanager_instance) {
            $_eventmanager_instance = new EventManager();
        }
        return $_eventmanager_instance;
    }
    
    function addListener($event, $listener, $callback, $recallEvent, $priority) {
        $entry = array();
        $entry['listener']    = $listener;
        $entry['callback']    = $callback;
        $entry['recallEvent'] = $recallEvent;
        
        $ev = $this->listeners->get($event);
        // We add the listener only if it's not already registered for this event 
        if (!$ev || !$ev->contains($entry)) {
            $this->listeners->put( $event, $entry, $priority);                
        }
        
    }
    
    function processEvent($event, $params) {
        $listeners = $this->listeners->get( $event);
        if ($listeners) {
            $it = $listeners->iterator();
            while($it->valid()) {
                $entry = $it->current();
                $listener    = $entry['listener'];
                $callback    = $entry['callback'];
                $recallEvent = $entry['recallEvent'];
                if ($recallEvent) {
                    $listener->$callback($event, $params);
                } else {
                    $listener->$callback($params);
                }
                $it->next();
            }
        }
    }
}
?>