<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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

class EventManager
{
    private $listeners = [];

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

    public function addClosureOnEvent($event, callable $callback)
    {
        $this->listeners[$event][] = [
            'listener'    => null,
            'callback'    => $callback,
            'recallEvent' => false,
        ];
    }

    public function addListener($event, $listener, $callback, $recallEvent) {
        $this->listeners[$event][] = array(
            'listener'    => $listener,
            'callback'    => $callback,
            'recallEvent' => $recallEvent
        );
    }

    /**
     * Notify listeners for an event
     *
     * The deprecated way was to use a string for $event and
     * an associative array for $params to pass input/output parameters.
     * This is kept to not break compatibility with existing code but you
     * should not rely on it anymore.
     *
     * $event should be an object and $params should be ignored.
     * @see \Tuleap\Widget\Event\GetPublicAreas for usage example.
     *
     * @param \Tuleap\Event\Dispatchable|string $event
     * @param mixed $params
     */
    public function processEvent($event, $params = array())
    {
        if (is_object($event)) {
            $event_name = $event::NAME;
            $params     = $event;
        } else {
            $event_name = $event;
        }

        if (isset($this->listeners[$event_name])) {
            foreach ($this->listeners[$event_name] as $hook) {
                $this->processEventOnListener($event, $params, $hook);
            }
        }
    }

    private function processEventOnListener($event, $params, array $entry)
    {
        $listener     = $entry['listener'];
        $callback     = $entry['callback'];
        $recall_event = $entry['recallEvent'];

        if ($listener === null && is_callable($callback)) {
            $callback($event, $params);
        } else {
            $this->dispatch($event, $listener, $callback, $recall_event, $params);
        }
    }

    public function dispatch($event, $listener, $callback, $recall_event, $params)
    {
        if ($recall_event) {
            $listener->$callback($event, $params);
        } else {
            $listener->$callback($params);
        }
    }
}
