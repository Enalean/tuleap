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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class PluginManagerTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ServiceManager
     */
    private $service_manager;

    protected function setUp(): void
    {
        $this->service_manager = \Mockery::spy(\ServiceManager::class);
        ServiceManager::setInstance($this->service_manager);
    }

    protected function tearDown(): void
    {
        ServiceManager::clearInstance();
        unset($GLOBALS['sys_pluginsroot'], $GLOBALS['sys_custompluginsroot']);
    }

    public function testGetAllPlugins(): void
    {
        //The plugins
        $plugins        = \Mockery::spy(\Collection::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('getAllPlugins')->andReturns($plugins);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            \Mockery::spy(\SiteCache::class),
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $this->assertEquals($plugins, $pm->getAllPlugins());
    }

    public function testIsPluginAvailable(): void
    {
        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('isPluginAvailable')->once()->andReturns(true);
        $plugin_factory->shouldReceive('isPluginAvailable')->once()->andReturns(false);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            \Mockery::spy(\SiteCache::class),
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $this->assertTrue($pm->isPluginAvailable($plugin));
        $this->assertFalse($pm->isPluginAvailable($plugin));
    }

    public function testEnablePlugin(): void
    {
        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);
        $plugin->shouldReceive('canBeMadeAvailable')->andReturns(true);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('availablePlugin')->once();

        $site_cache = \Mockery::spy(\SiteCache::class);
        $site_cache->shouldReceive('invalidatePluginBasedCaches')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->availablePlugin($plugin);
    }

    public function testDisablePlugin(): void
    {
        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('unavailablePlugin')->once();

        $site_cache = \Mockery::spy(\SiteCache::class);
        $site_cache->shouldReceive('invalidatePluginBasedCaches')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->unavailablePlugin($plugin);
    }

    public function testInstallPlugin(): void
    {
        $root = \org\bovigo\vfs\vfsStream::setup()->url();
        $GLOBALS['sys_pluginsroot']       = $root . '/test/custom/';
        $GLOBALS['sys_custompluginsroot'] = $root . '/test/custom/';

        mkdir($root . '/test');
        mkdir($root . '/test/custom');

        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('createPlugin')->with('New_Plugin')->once()->andReturns($plugin);

        $plugin_factory->shouldReceive('getAllPossiblePluginsDir')->andReturns(array(
            __DIR__ . '/test'
        ));

        $forgeupgrade_config = \Mockery::spy(\ForgeUpgradeConfig::class);
        $forgeupgrade_config->shouldReceive('addPath')->with($GLOBALS['sys_pluginsroot'] . 'New_Plugin')->once();
        $forgeupgrade_config->shouldReceive('recordOnlyPath')->with($GLOBALS['sys_pluginsroot'] . 'New_Plugin')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            \Mockery::spy(\SiteCache::class),
            $forgeupgrade_config,
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        // plugin manager must call postInstall 1 time on plugin after its creation
        $plugin->shouldReceive('postInstall')->once();

        $this->assertEquals($plugin, $pm->installPlugin('New_Plugin'));

        // Plugin dir was created in "/etc"
        $this->assertDirectoryExists($root . '/test/custom/New_Plugin');
    }

    public function testIsNameValid(): void
    {
        $pm = new PluginManager(
            \Mockery::spy(\PluginFactory::class),
            \Mockery::spy(\SiteCache::class),
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $this->assertTrue($pm->isNameValid('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_'));
        $this->assertFalse($pm->isNameValid(' '));
        $this->assertFalse($pm->isNameValid('*'));
        $this->assertFalse($pm->isNameValid('?'));
        $this->assertFalse($pm->isNameValid('/'));
        $this->assertFalse($pm->isNameValid('\\'));
        $this->assertFalse($pm->isNameValid('.'));
    }

    public function testGetPluginByName(): void
    {
        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('getPluginByName')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            \Mockery::spy(\SiteCache::class),
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->getPluginByName('plugin_name');
    }
}
