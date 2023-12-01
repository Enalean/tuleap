<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Plugin;

use EventManager;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PluginFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;

final class PluginLoaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private string $hooks_cache_file_path;
    private EventManager&MockObject $event_manager;
    private PluginFactory&MockObject $plugin_factory;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $tuleap_cache_directory = $this->getTmpDir();
        ForgeConfig::set('codendi_cache_dir', $tuleap_cache_directory);
        $this->hooks_cache_file_path = $tuleap_cache_directory . DIRECTORY_SEPARATOR . PluginLoader::HOOK_CACHE_KEY;

        $this->event_manager  = $this->createMock(EventManager::class);
        $this->plugin_factory = $this->createMock(PluginFactory::class);
        $this->logger         = new NullLogger();
    }

    public function testMissingHooksFileCacheIsCreated(): void
    {
        $plugin_loader = new PluginLoader($this->event_manager, $this->plugin_factory, $this->logger);

        $this->plugin_factory->method('getEnabledPlugins')->willReturn([]);

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        self::assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }

    public function testEmptyHooksFileCacheIsPopulated(): void
    {
        $plugin_loader = new PluginLoader($this->event_manager, $this->plugin_factory, $this->logger);

        $this->plugin_factory->method('getEnabledPlugins')->willReturn([]);

        touch($this->hooks_cache_file_path);

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        self::assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }

    public function testReadOnlyEmptyHooksFileCacheIsPopulatedWithoutWarning(): void
    {
        $plugin_loader = new PluginLoader($this->event_manager, $this->plugin_factory, $this->logger);

        $this->plugin_factory->method('getEnabledPlugins')->willReturn([]);

        touch($this->hooks_cache_file_path);
        chmod($this->hooks_cache_file_path, 0400);

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        self::assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }

    public function testHooksFileCacheWithGarbageDataIsOverwritten(): void
    {
        $plugin_loader = new PluginLoader($this->event_manager, $this->plugin_factory, $this->logger);

        $this->plugin_factory->method('getEnabledPlugins')->willReturn([]);

        file_put_contents($this->hooks_cache_file_path, 'InvalidData');

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        self::assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }
}
