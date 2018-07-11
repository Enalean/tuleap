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

use Backend;
use EventManager;
use ForgeConfig;
use Logger;

class PluginLoader
{
    const HOOK_CACHE_KEY = 'tuleap_hooks_cache';

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var \PluginFactory
     */
    private $plugin_factory;

    public function __construct(EventManager $event_manager, \PluginFactory $plugin_factory)
    {
        $this->event_manager  = $event_manager;
        $this->plugin_factory = $plugin_factory;
    }

    public function loadPlugins()
    {
        $proxy = $this->getFromCache();
        if ($proxy === false) {
            $proxy = $this->getHooksOfAvailablePlugins();
            $this->storeInCache($proxy);
        }
        foreach ($proxy->getEvents() as $event_name => $listeners) {
            foreach ($listeners as $listener) {
                /* @var EventPluginCacheInfo $listener */
                $this->event_manager->addClosureOnEvent(
                    $event_name,
                    function ($event, $params) use ($proxy, $listener) {
                        $plugin = $proxy->getPlugin($listener->plugin_id);
                        $this->event_manager->dispatch($listener->event, $plugin, $listener->callback, $listener->recall_event, $params);
                    }
                );
            }
        }
    }

    public static function invalidateCache()
    {
        if (file_exists(self::getHooksCacheFile())) {
            unlink(self::getHooksCacheFile());
        }
    }

    private static function getHooksCacheFile()
    {
        return ForgeConfig::get('codendi_cache_dir') . '/' . self::HOOK_CACHE_KEY;
    }

    /**
     * @return bool|SerializedPluginProxy
     */
    private function getFromCache()
    {
        if (file_exists(self::getHooksCacheFile())) {
            $cache = include self::getHooksCacheFile();
            return new SerializedPluginProxy($cache);
        }
        return false;
    }

    private function storeInCache(SerializedPluginProxy $proxy)
    {
        $this->serializeInFile(self::getHooksCacheFile(), $proxy->getSerializablePluginCache());
    }

    private function serializeInFile($path, $var)
    {
        file_put_contents($path, '<?php'.PHP_EOL.'return '.var_export($var, true).';');
    }

    private function getHooksOfAvailablePlugins()
    {
        $proxy = new SerializedPluginProxy(new EventPluginCache());
        foreach ($this->plugin_factory->getAvailablePlugins() as $plugin) {
            foreach ($plugin->getHooksAndCallbacks()->iterator() as $hook) {
                $proxy->addListener($this->plugin_factory, $hook['hook'], $plugin, $hook);
            }
        }
        return $proxy;
    }

    public static function restoreOwnershipOnCacheFile(Logger $logger, Backend $backend)
    {
        $plugin_cache_file = self::getHooksCacheFile();
        if (! file_exists($plugin_cache_file)) {
            touch($plugin_cache_file);
        }
        $logger->debug('Restore ownership to ' . $plugin_cache_file);
        $backend->changeOwnerGroupMode(
            $plugin_cache_file,
            ForgeConfig::getApplicationUserLogin(),
            ForgeConfig::getApplicationUserLogin(),
            0400
        );
    }
}
