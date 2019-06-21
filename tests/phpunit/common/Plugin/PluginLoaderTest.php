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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PluginFactory;
use Tuleap\ForgeConfigSandbox;

final class PluginLoaderTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var string
     */
    private $hooks_cache_file_path;
    /**
     * @var EventManager|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\MockInterface|PluginFactory
     */
    private $plugin_factory;

    protected function setUp() : void
    {
        $tuleap_cache_directory = vfsStream::setup()->url();
        ForgeConfig::set('codendi_cache_dir', $tuleap_cache_directory);
        $this->hooks_cache_file_path = $tuleap_cache_directory . DIRECTORY_SEPARATOR . PluginLoader::HOOK_CACHE_KEY;

        $this->event_manager  = Mockery::mock(EventManager::class);
        $this->plugin_factory = Mockery::mock(PluginFactory::class);
    }

    public function testMissingHooksFileCacheIsCreated() : void
    {
        $plugin_loader  = new PluginLoader($this->event_manager, $this->plugin_factory);

        $this->plugin_factory->shouldReceive('getAvailablePlugins')->andReturn([]);

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        $this->assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }

    public function testEmptyHooksFileCacheIsPopulated() : void
    {
        $plugin_loader  = new PluginLoader($this->event_manager, $this->plugin_factory);

        $this->plugin_factory->shouldReceive('getAvailablePlugins')->andReturn([]);

        touch($this->hooks_cache_file_path);

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        $this->assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }

    public function testHooksFileCacheWithGarbageDataIsOverwritten() : void
    {
        $plugin_loader  = new PluginLoader($this->event_manager, $this->plugin_factory);

        $this->plugin_factory->shouldReceive('getAvailablePlugins')->andReturn([]);

        file_put_contents($this->hooks_cache_file_path, 'InvalidData');

        $plugin_loader->loadPlugins();

        $unserialized_hooks_cache = include $this->hooks_cache_file_path;
        $this->assertInstanceOf(EventPluginCache::class, $unserialized_hooks_cache);
    }
}
