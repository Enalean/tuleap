<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
Mock::generate('EventManager');
Mock::generate('DataAccessResult');

Mock::generatePartial('ForgeUpgradeConfig', 'ForgeUpgradeConfigTestPluginManager', array('run'));

/**
 * Tests the class PluginManager
 */

class PluginManagerTest extends TuleapTestCase {

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

    function testGetAllPlugins() {
        //The plugins
        $plugins        = new MockCollection($this);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->setReturnReference('getAllPlugins', $plugins);

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('EventManager'),
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $this->assertEqual($pm->getAllPlugins(), $plugins);
    }

    function testIsPluginAvailable() {
        //The plugins
        $plugin = new MockPlugin($this);

        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->setReturnValueAt(0, 'isPluginAvailable', true);
        $plugin_factory->setReturnValueAt(1, 'isPluginAvailable', false);


        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('EventManager'),
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $this->assertTrue($pm->isPluginAvailable($plugin));
        $this->assertFalse($pm->isPluginAvailable($plugin));
    }

    function testEnablePlugin() {
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
            mock('EventManager'),
            $site_cache,
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $pm->availablePlugin($plugin);
    }
    function testDisablePlugin() {
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
            mock('EventManager'),
            $site_cache,
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $pm->unavailablePlugin($plugin);
    }

    function testInstallPlugin() {
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
            mock('EventManager'),
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

    function testIsNameValide() {
        $pm = new PluginManager(
            mock('PluginFactory'),
            mock('EventManager'),
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

    function testGetPluginByname() {
        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->expectOnce('getPluginByName');

        //The plugins manager
        $pm = new PluginManager(
            $plugin_factory,
            mock('EventManager'),
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $pm->getPluginByName('plugin_name');
    }
}

class PluginManager_LoadPluginTest extends TuleapTestCase {
    private $plugin_factory;
    private $event_manager;
    private $plugin_manager;

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_cache_dir', '/tmp');

        $this->plugin_factory = mock('PluginFactory');
        $this->event_manager  = mock('EventManager');
        $this->plugin_manager = new PluginManager(
            $this->plugin_factory,
            $this->event_manager,
            mock('SiteCache'),
            mock('ForgeUpgradeConfig'),
            mock('Tuleap\Markdown\ContentInterpretor')
        );

        $plugin = mock('Plugin');
        stub($plugin)->getName()->returns('DatPlugin');
        $hooks_and_callback = mock('Collection');
        stub($hooks_and_callback)->iterator()->returns(array(
            array('hook' => 'this_event', 'callback' => 'myFunction', 'recallHook' => false)
        ));
        stub($plugin)->getHooksAndCallbacks()->returns($hooks_and_callback);

        stub($this->plugin_factory)->getAvailablePlugins()->returns(
            array($plugin)
        );
        stub($this->plugin_factory)->getAvailablePluginsWithoutOrder()->returns([
            [ 'id' => 1, 'name' => 'dat-plugin', 'prj_restricted' => 0 ]
        ]);
        stub($this->plugin_factory)->getClassName()->returns('DatPlugin');
        stub($this->plugin_factory)->getClassPath()->returns(dirname(__FILE__).'/_fixtures/DatPlugin.php');
    }

    public function tearDown() {
        unlink('/tmp/hooks.json');
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itGenerateCacheOfHookDefinitions() {
        $this->plugin_manager->loadPlugins();

        $json_cache = json_decode(file_get_contents('/tmp/hooks.json'), true);
        $this->assertCount($json_cache['DatPlugin']['hooks'], 1);
        $this->assertEqual($json_cache['DatPlugin']['hooks'][0]['event'], 'this_event');
    }

    public function itCachesHooksDefinitions() {
        expect($this->plugin_factory)->getAvailablePlugins()->once();

        $this->plugin_manager->loadPlugins();
        $this->plugin_manager->loadPlugins();
    }

    public function itLoadsClassesFromCacheFile() {
        $this->assertTrue(class_exists('DatPlugin'));
        $this->assertFalse(class_exists('NotLoaded'));

        $fake_json = array(
            'NotLoaded' => array(
                'id'    => 0,
                'class' => 'NotLoaded',
                'path'  => dirname(__FILE__).'/_fixtures/NotLoaded.php',
                'hooks' => array()
            )
        );
        file_put_contents('/tmp/hooks.json', json_encode($fake_json));
        $this->plugin_manager->loadPlugins();

        $this->assertTrue(class_exists('DatPlugin'));
        $this->assertTrue(class_exists('NotLoaded'));

    }
}
