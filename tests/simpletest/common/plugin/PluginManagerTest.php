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

Mock::generate('PluginFactory');
Mock::generate('Plugin');
Mock::generate('Collection');
Mock::generate('ArrayIterator', 'MockIterator');
Mock::generate('DataAccessResult');

Mock::generatePartial('ForgeUpgradeConfig', 'ForgeUpgradeConfigTestPluginManager', array('run'));

class PluginManagerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->service_manager = mock('ServiceManager');
        ServiceManager::setInstance($this->service_manager);
    }

    public function tearDown()
    {
        ServiceManager::clearInstance();
        parent::tearDown();
    }

    function testGetAllPlugins()
    {
        //The plugins
        $plugins        = new MockCollection($this);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->setReturnReference('getAllPlugins', $plugins);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $this->assertEqual($pm->getAllPlugins(), $plugins);
    }

    function testIsPluginAvailable()
    {
        //The plugins
        $plugin = new MockPlugin($this);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->setReturnValueAt(0, 'isPluginAvailable', true);
        $plugin_factory->setReturnValueAt(1, 'isPluginAvailable', false);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $this->assertTrue($pm->isPluginAvailable($plugin));
        $this->assertFalse($pm->isPluginAvailable($plugin));
    }

    function testEnablePlugin()
    {
        //The plugins
        $plugin = new MockPlugin($this);
        $plugin->setReturnValue('canBeMadeAvailable', true);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->expectOnce('availablePlugin');

        $site_cache = mock('SiteCache');
        expect($site_cache)->invalidatePluginBasedCaches()->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $pm->availablePlugin($plugin);
    }
    function testDisablePlugin()
    {
        //The plugins
        $plugin = new MockPlugin($this);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->expectOnce('unavailablePlugin');

        $site_cache = mock('SiteCache');
        expect($site_cache)->invalidatePluginBasedCaches()->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            $site_cache,
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $pm->unavailablePlugin($plugin);
    }

    function testInstallPlugin()
    {
        $GLOBALS['sys_pluginsroot'] = $this->getTmpDir().'/test/custom/';
        $GLOBALS['sys_custompluginsroot'] = $this->getTmpDir().'/test/custom/';
        $GLOBALS['sys_pluginsroot'] = $this->getTmpDir().'/test/custom/';

        mkdir($this->getTmpDir().'/test');
        mkdir($this->getTmpDir().'/test/custom');

        //The plugins
        $plugin = new MockPlugin($this);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->expectOnce('createPlugin', array('New_Plugin'));
        $plugin_factory->setReturnReference('createPlugin', $plugin);

        stub($plugin_factory)->getAllPossiblePluginsDir()->returns(array(
            dirname(__FILE__).'/test'
        ));

        $forgeupgrade_config = mock('ForgeUpgradeConfig');
        expect($forgeupgrade_config)->addPath($GLOBALS['sys_pluginsroot'].'New_Plugin')->once();
        expect($forgeupgrade_config)->recordOnlyPath($GLOBALS['sys_pluginsroot'].'New_Plugin')->once();

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('SiteCache'),
            $forgeupgrade_config,
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $this->assertEqual($pm->installPlugin('New_Plugin'), $plugin);

        // plugin manager must call postInstall 1 time on plugin after its creation
        $plugin->expectCallCount('postInstall', 1);

        // Plugin dir was created in "/etc"
        $this->assertTrue(is_dir($this->getTmpDir().'/test/custom/New_Plugin'));
    }

    function testIsNameValide()
    {
        $pm = new PluginManager(
            mock('PluginFactory'),
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $this->assertTrue($pm->isNameValid('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_'));
        $this->assertFalse($pm->isNameValid(' '));
        $this->assertFalse($pm->isNameValid('*'));
        $this->assertFalse($pm->isNameValid('?'));
        $this->assertFalse($pm->isNameValid('/'));
        $this->assertFalse($pm->isNameValid('\\'));
        $this->assertFalse($pm->isNameValid('.'));
    }

    function testGetPluginByname()
    {
        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->expectOnce('getPluginByName');

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $pm->getPluginByName('plugin_name');
    }
}
