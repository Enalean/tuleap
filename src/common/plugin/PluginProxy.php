<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

/**
 * This class is a proxy between EventManager and actual plugin class.
 *
 * It's main purpose is to hold hooks / event informations and to instanciate
 * the actual plugin once it's actually needed
 */
class PluginProxy {

    private $id;
    private $class_name;

    /** @var Plugin */
    private $plugin = null;

    private $listener = array();

    private $recall_event = array();
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $is_restricted;

    /**
     * PluginProxy constructor.
     * @param string $class_name
     * @param int $id
     * @param \Tuleap\Plugin\PluginProxyInjectedData[] $injected_data
     */
    public function __construct($class_name, $id, array $injected_data) {
        $this->class_name = $class_name;
        $this->id = $id;
        if (isset($injected_data[$id])) {
            $this->name = $injected_data[$id]->getName();
            $this->is_restricted = $injected_data[$id]->getIsRestricted();
        }
    }

    public function addListener($event, $callback, $recall_event) {
        $this->listener[$event]     = $callback;
        $this->recall_event[$event] = $recall_event;
    }

    public function processEvent($event, $params) {
        if (is_object($event)) {
            $event_name = $event::NAME;
            $params     = $event;
        } else {
            $event_name = $event;
        }

        if (isset($this->listener[$event_name])) {
            $plugin   = $this->getPlugin();
            $callback = $this->listener[$event_name];

            if ($this->recall_event[$event_name]) {
                $plugin->$callback($event, $params);
            } else {
                $plugin->$callback($params);
            }
        }
    }

    private function getPlugin() {
        if ($this->plugin === null) {
            $this->plugin = $this->instanciatePlugin($this->class_name, $this->id);
            if ($this->name !== null) {
                $this->plugin->setName($this->name);
                $this->plugin->setIsRestricted($this->is_restricted);
            }
        }
        return $this->plugin;
    }

    /**
     * @param $class
     * @param $id
     * @return Plugin
     */
    private function instanciatePlugin($class, $id) {
        return new $class($id);
    }
}
