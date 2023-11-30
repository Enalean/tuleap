<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Plugin;

use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PluginManager;
use ServiceManager;

final class PluginManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    private \ServiceManager&MockObject $service_manager;

    protected function setUp(): void
    {
        $this->service_manager = $this->createMock(\ServiceManager::class);
        ServiceManager::setInstance($this->service_manager);
    }

    protected function tearDown(): void
    {
        ServiceManager::clearInstance();
    }

    public function testGetAllPlugins(): void
    {
        //The plugins
        $plugins = [];

        //The plugin factory
        $plugin_factory = $this->createMock(\PluginFactory::class);
        $plugin_factory->method('getAllPlugins')->willReturn($plugins);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $this->createMock(\SiteCache::class),
            $this->createMock(\ForgeUpgradeConfig::class),
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        self::assertEquals($plugins, $pm->getAllPlugins());
    }

    public function testIsPluginEnable(): void
    {
        //The plugins
        $plugin = $this->createMock(\Plugin::class);

        //The plugin factory
        $plugin_factory = $this->createMock(\PluginFactory::class);
        $plugin_factory
            ->expects(self::exactly(2))
            ->method('isPluginEnabled')
            ->willReturnOnConsecutiveCalls(true, false);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $this->createMock(\SiteCache::class),
            $this->createMock(\ForgeUpgradeConfig::class),
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        self::assertTrue($pm->isPluginEnabled($plugin));
        self::assertFalse($pm->isPluginEnabled($plugin));
    }

    public function testEnablePlugin(): void
    {
        //The plugins
        $plugin = $this->createMock(\Plugin::class);

        //The plugin factory
        $plugin_factory = $this->createMock(\PluginFactory::class);
        $plugin_factory->expects(self::once())->method('enablePlugin');

        $site_cache = $this->createMock(\SiteCache::class);
        $site_cache->expects(self::once())->method('invalidatePluginBasedCaches');

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            $this->createMock(\ForgeUpgradeConfig::class),
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->enablePlugin($plugin);
    }

    public function testDisablePlugin(): void
    {
        //The plugins
        $plugin = $this->createMock(\Plugin::class);

        //The plugin factory
        $plugin_factory = $this->createMock(\PluginFactory::class);
        $plugin_factory->expects(self::once())->method('disablePlugin');

        $site_cache = $this->createMock(\SiteCache::class);
        $site_cache->expects(self::once())->method('invalidatePluginBasedCaches');

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            $this->createMock(\ForgeUpgradeConfig::class),
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->disablePlugin($plugin);
    }

    public function testInstallPlugin(): void
    {
        $root = \org\bovigo\vfs\vfsStream::setup()->url();
        ForgeConfig::set('sys_pluginsroot', $root . '/usr/share/tuleap/plugins/');
        ForgeConfig::set('sys_custompluginsroot', $root . '/etc/tuleap/plugins/');

        mkdir($root . '/usr/share/tuleap/plugins/New_Plugin/db', 0755, true);
        mkdir($root . '/etc/tuleap/plugins', 0755, true);

        //The plugins
        $plugin = $this->createMock(\Plugin::class);
        $plugin->method('getDependencies')->willReturn([]);

        //The plugin factory
        $plugin_factory = $this->createMock(\PluginFactory::class);
        $plugin_factory->expects(self::once())->method('createPlugin')->with('New_Plugin')->willReturn($plugin);
        $plugin_factory->method('instantiatePlugin')->willReturn($plugin);
        $plugin_factory->method('isPluginInstalled');

        $plugin_factory->method('getAllPossiblePluginsDir')->willReturn([
            __DIR__ . '/test',
        ]);

        $forgeupgrade_config = $this->createMock(\ForgeUpgradeConfig::class);
        $forgeupgrade_config->expects(self::once())->method('recordOnlyPath')->with(ForgeConfig::get('sys_pluginsroot') . 'New_Plugin/db');

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $this->createMock(\SiteCache::class),
            $forgeupgrade_config,
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        self::assertEquals($plugin, $pm->installPlugin('New_Plugin'));

        // Plugin dir was created in "/etc"
        self::assertDirectoryExists($root . '/etc/tuleap/plugins');
    }

    public function testIsNameValid(): void
    {
        $pm = new PluginManager(
            $this->createMock(\PluginFactory::class),
            $this->createMock(\SiteCache::class),
            $this->createMock(\ForgeUpgradeConfig::class),
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        self::assertTrue($pm->isNameValid('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_'));
        self::assertFalse($pm->isNameValid(' '));
        self::assertFalse($pm->isNameValid('*'));
        self::assertFalse($pm->isNameValid('?'));
        self::assertFalse($pm->isNameValid('/'));
        self::assertFalse($pm->isNameValid('\\'));
        self::assertFalse($pm->isNameValid('.'));
    }

    public function testGetPluginByName(): void
    {
        //The plugin factory
        $plugin_factory = $this->createMock(\PluginFactory::class);
        $plugin_factory->expects(self::once())->method('getPluginByName');

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $this->createMock(\SiteCache::class),
            $this->createMock(\ForgeUpgradeConfig::class),
            $this->createMock(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->getPluginByName('plugin_name');
    }
}
