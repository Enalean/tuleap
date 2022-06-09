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
final class PluginManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

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
    }

    public function testGetAllPlugins(): void
    {
        //The plugins
        $plugins = [];

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

    public function testIsPluginEnable(): void
    {
        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('isPluginEnabled')->once()->andReturns(true);
        $plugin_factory->shouldReceive('isPluginEnabled')->once()->andReturns(false);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            \Mockery::spy(\SiteCache::class),
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $this->assertTrue($pm->isPluginEnabled($plugin));
        $this->assertFalse($pm->isPluginEnabled($plugin));
    }

    public function testEnablePlugin(): void
    {
        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('enablePlugin')->once();

        $site_cache = \Mockery::spy(\SiteCache::class);
        $site_cache->shouldReceive('invalidatePluginBasedCaches')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
        );

        $pm->enablePlugin($plugin);
    }

    public function testDisablePlugin(): void
    {
        //The plugins
        $plugin = \Mockery::spy(\Plugin::class);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('disablePlugin')->once();

        $site_cache = \Mockery::spy(\SiteCache::class);
        $site_cache->shouldReceive('invalidatePluginBasedCaches')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            \Mockery::spy(\ForgeUpgradeConfig::class),
            \Mockery::spy(\Tuleap\Markdown\ContentInterpretor::class)
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
        $plugin = \Mockery::spy(\Plugin::class, ['getDependencies' => []]);

        //The plugin factory
        $plugin_factory = \Mockery::spy(\PluginFactory::class);
        $plugin_factory->shouldReceive('createPlugin')->with('New_Plugin')->once()->andReturns($plugin);
        $plugin_factory->shouldReceive('instantiatePlugin')->andReturn($plugin);

        $plugin_factory->shouldReceive('getAllPossiblePluginsDir')->andReturns([
            __DIR__ . '/test',
        ]);

        $forgeupgrade_config = \Mockery::spy(\ForgeUpgradeConfig::class);
        $forgeupgrade_config->shouldReceive('recordOnlyPath')->with(ForgeConfig::get('sys_pluginsroot') . 'New_Plugin/db')->once();

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
        $this->assertDirectoryExists($root . '/etc/tuleap/plugins');
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
