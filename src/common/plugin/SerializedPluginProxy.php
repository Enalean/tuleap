<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Plugin;

class SerializedPluginProxy
{
    /**
     * @var EventPluginCacheInfo[][]
     */
    private $event_plugin_map;
    /**
     * @var PluginCacheInfo[]
     */
    private $plugin_map;
    /**
     * @var \Plugin[]
     */
    private $plugins = [];

    public function __construct(EventPluginCache $cache)
    {
        $this->plugin_map       = $cache->plugin_map;
        $this->event_plugin_map = $cache->event_plugin_map;
    }

    public function getSerializablePluginCache()
    {
        return new EventPluginCache($this->plugin_map, $this->event_plugin_map);
    }

    public function isPluginAvailableById($plugin_id)
    {
        return isset($this->plugin_map[$plugin_id]);
    }

    /**
     * @return EventPluginCacheInfo[][]
     */
    public function getEvents()
    {
        return $this->event_plugin_map;
    }

    public function addListener(\PluginFactory $plugin_factory, $event_name, \Plugin $plugin, array $hook)
    {
        $this->plugin_map[$plugin->getId()] = new PluginCacheInfo(
            $plugin->getId(),
            $plugin->getName(),
            $plugin->isRestricted(),
            $plugin_factory->getClassName($plugin->getName()),
            $plugin_factory->getClassPath($plugin->getName())
        );
        $this->event_plugin_map[$event_name][] = new EventPluginCacheInfo(
            $plugin->getId(),
            $hook['hook'],
            $hook['callback'],
            $hook['recallHook']
        );
    }

    private function loadPluginFiles($path)
    {
        if (file_exists($path)) {
            include_once $path;
        }
        $autoload = dirname($path) . '/autoload.php';
        if (file_exists($autoload)) {
            include_once $autoload;
        }
    }

    /**
     * @param $id
     * @return \Plugin
     */
    public function getPlugin($id)
    {
        if (! isset($this->plugins[$id])) {
            $this->loadPluginFiles($this->plugin_map[$id]->path);
            $this->plugins[$id] = $this->instantiatePlugin($this->plugin_map[$id]->class, $id);
            $this->plugins[$id]->setName($this->plugin_map[$id]->name);
            $this->plugins[$id]->setIsRestricted($this->plugin_map[$id]->is_restricted);
        }
        return $this->plugins[$id];
    }

    /**
     * @param $class
     * @param $id
     * @return \Plugin
     */
    private function instantiatePlugin($class, $id)
    {
        return new $class($id);
    }
}
