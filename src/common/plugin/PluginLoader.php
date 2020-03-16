<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use Psr\Log\LoggerInterface;
use Symfony\Component\VarExporter\VarExporter;
use Webimpress\SafeWriter\Exception\ExceptionInterface;
use Webimpress\SafeWriter\FileWriter;

class PluginLoader
{
    public const HOOK_CACHE_KEY = 'tuleap_hooks_cache';

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var \PluginFactory
     */
    private $plugin_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EventManager $event_manager, \PluginFactory $plugin_factory, LoggerInterface $logger)
    {
        $this->event_manager  = $event_manager;
        $this->plugin_factory = $plugin_factory;
        $this->logger = $logger;
    }

    public function loadPlugins()
    {
        $proxy = $this->getFromCache();
        if ($proxy === null) {
            $proxy = $this->getHooksOfAvailablePlugins();
            $this->storeInCache($proxy);
        }
        foreach ($proxy->getEvents() as $event_name => $listeners) {
            foreach ($listeners as $listener) {
                /** @var EventPluginCacheInfo $listener */
                $this->event_manager->addClosureOnEvent(
                    $event_name,
                    function ($event, $params) use ($proxy, $listener) {
                        $plugin = $proxy->getPlugin($listener->plugin_id);
                        $this->event_manager->eventManagerDispatch($listener->event, $plugin, $listener->callback, $listener->recall_event, $params);
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

    private function getFromCache() : ?SerializedPluginProxy
    {
        if (! file_exists(self::getHooksCacheFile())) {
            return null;
        }
        ob_start();
        $cache = include self::getHooksCacheFile();
        ob_end_clean();
        if (! $cache instanceof EventPluginCache) {
            return null;
        }
        return new SerializedPluginProxy($cache);
    }

    private function storeInCache(SerializedPluginProxy $proxy) : void
    {
        self::invalidateCache();
        $this->serializeInFile(self::getHooksCacheFile(), $proxy->getSerializablePluginCache());
    }

    private function serializeInFile(string $path, EventPluginCache $var) : void
    {
        $content = '<?php' . PHP_EOL . 'return ' . VarExporter::export($var) . ';';
        try {
            FileWriter::writeFile($path, $content);
        } catch (ExceptionInterface $exception) {
            $this->logger->error("Unable to store tuleap hooks content:" . $exception->getMessage());
        }
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

    public static function restoreOwnershipOnCacheFile(LoggerInterface $logger, Backend $backend)
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
