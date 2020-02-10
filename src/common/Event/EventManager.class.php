<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventManager implements EventDispatcherInterface // phpcs:ignore
{
    private $listeners = [];

    /**
     * @var EventManager
     */
    private static $instance;

    /**
     * Allows clear instance for test. DO NOT USE IT IN PRODUCTION CODE!
     */
    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * Set current instance of singleton.  DO NOT USE IT IN PRODUCTION CODE!
     */
    public static function setInstance(EventManager $instance)
    {
        self::$instance = $instance;
    }

    /**
     * The singleton method
     *
     * @return EventManager
     */
    public static function instance()
    {
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

    public function addListener($event, $listener, $callback, $recallEvent)
    {
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
     * @param Tuleap\Event\Dispatchable|string $event
     * @param array $params
     */
    public function processEvent($event, $params = array())
    {
        if (is_object($event)) {
            $this->dispatch($event);
            return;
        }

        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $hook) {
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
            $this->eventManagerDispatch($event, $listener, $callback, $recall_event, $params);
        }
    }

    public function eventManagerDispatch($event, $listener, $callback, $recall_event, $params)
    {
        if ($recall_event) {
            $listener->$callback($event, $params);
        } else {
            $listener->$callback($params);
        }
    }

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event
     *   The object to process.
     *
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event)
    {
        foreach ($this->getListenersForEvent($event) as $hook) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
            $this->processEventOnListener($event, $event, $hook);
        }

        return $event;
    }

    private function getListenersForEvent(object $event): iterable
    {
        $class_name = get_class($event);
        try {
            $constant_reflex = new \ReflectionClassConstant($class_name, 'NAME');
            $event_name = $constant_reflex->getValue();
            return $this->listeners[$event_name] ?? [];
        } catch (\ReflectionException $e) {
            return $this->listeners[$class_name] ?? [];
        }
    }
}
