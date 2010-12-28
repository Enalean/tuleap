<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/collection/PrioritizedMultiMap.class.php');
require_once('common/collection/Map.class.php');
require_once('Event.class.php');

/**
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
        $this->listeners->put( $event, $entry, $priority);
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