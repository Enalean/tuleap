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
    
    /**
     * @const string The callback to call if everything else fail
     */
    const DEFAULT_CALLBACK = 'CallHook';

    var $listeners;
    
    public function EventManager() {
        $this->listeners = new PrioritizedMultiMap();
    }
    
    /**
     * Holds an instance of the class
     * @var EventManager
     */
    private static $instance;
    
    /**
     * Allows clear instance for test. DO NOT USE IT IN PRODUCTION CODE!
     */
    public static function clearInstance() {
        self::$instance = null;
    }

    /**
     * Set current instance of singleton.  DO NOT USE IT IN PRODUCTION CODE!
     * @param EventManager $instance
     */
    public static function setInstance(EventManager $instance) {
        self::$instance = $instance;
    }

    /**
     * The singleton method
     * 
     * @return EventManager
     */
    public static function instance() {
        if (! self::$instance) {
            self::$instance = new EventManager();
        }
        return self::$instance;
    }
    
    public function addListener($event, $listener, $callback, $recallEvent, $priority) {
        $entry = array();
        $entry['listener']    = $listener;
        $entry['callback']    = $callback;
        $entry['recallEvent'] = $recallEvent;
        $this->listeners->put( $event, $entry, $priority);
    }

    public function processEvent($event, $params) {
        $listeners = $this->listeners->get($event);
        if ($listeners) {
            $it = $listeners->iterator();
            while($it->valid()) {
                $this->processEventOnListener($event, $params, $it->current());
                $it->next();
            }
        }
    }

    private function processEventOnListener($event, $params, array $entry) {
        $listener = $entry['listener'];
        $callback = $entry['callback'];
        $recallEvent = $entry['recallEvent'];
        if (!method_exists($listener, $callback)) {
            $callback    = self::DEFAULT_CALLBACK;
            $recallEvent = true;
        }
        if ($recallEvent) {
            $listener->$callback($event, $params);
        } else {
            $listener->$callback($params);
        }
    }
}
?>