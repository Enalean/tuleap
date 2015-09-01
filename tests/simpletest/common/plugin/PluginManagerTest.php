<?php

Mock::generate('PluginFactory');
Mock::generate('Plugin');
Mock::generate('Collection');
Mock::generate('ArrayIterator', 'MockIterator');
Mock::generate('EventManager');
Mock::generate('DataAccessResult');

Mock::generatePartial('ForgeUpgradeConfig', 'ForgeUpgradeConfigTestPluginManager', array('run'));

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class PluginManager
 */
class PluginManagerTest extends TuleapTestCase {

    function setUp() {
        $this->globals = $GLOBALS;
        $this->service_manager = mock('ServiceManager');
        ServiceManager::setInstance($this->service_manager);
    }

    function tearDown() {
        $GLOBALS = $this->globals;
        ServiceManager::clearInstance();
    }

    function testGetAllPlugins() {
        //The plugins
        $plugins        = new MockCollection($this);
        
        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->setReturnReference('getAllPlugins', $plugins);
        
        //The plugins manager
        $pm = new PluginManager($plugin_factory, mock('EventManager'), mock('SiteCache'));
        
        $this->assertReference($pm->getAllPlugins(), $plugins);
    }
    
    function testIsPluginAvailable() {
        //The plugins
        $plugin = new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->setReturnValueAt(0, 'isPluginAvailable', true);
        $plugin_factory->setReturnValueAt(1, 'isPluginAvailable', false);


        //The plugins manager
        $pm = new PluginManager($plugin_factory, mock('EventManager'), mock('SiteCache'));
        
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
        $pm = new PluginManager($plugin_factory, mock('EventManager'), $site_cache);
        
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
        $pm = new PluginManager($plugin_factory, mock('EventManager'), $site_cache);
        
        $pm->unavailablePlugin($plugin);
    }

    function _remove_directory($dir) {
      if ($handle = opendir("$dir")) {
       while (false !== ($item = readdir($handle))) {
         if ($item != "." && $item != "..") {
           if (is_dir("$dir/$item")) {
             $this->_remove_directory("$dir/$item");
           } else {
             unlink("$dir/$item");
           }
         }
       }
       closedir($handle);
       rmdir($dir);
      }
    }

    function testInstallPlugin() {
        $GLOBALS['sys_pluginsroot'] = dirname(__FILE__).'/test/custom/';
        $GLOBALS['sys_custompluginsroot'] = dirname(__FILE__).'/test/custom/';
        $GLOBALS['sys_pluginsroot'] = dirname(__FILE__).'/test/custom/';
        $GLOBALS['forgeupgrade_file'] = dirname(__FILE__).'/test/forgeupgrade.ini';

        mkdir(dirname(__FILE__).'/test');
        mkdir(dirname(__FILE__).'/test/custom');
        touch($GLOBALS['forgeupgrade_file']);

        //The plugins
        $plugin = new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory = new MockPluginFactory($this);
        $plugin_factory->expectOnce('createPlugin', array('New_Plugin'));
        $plugin_factory->setReturnReference('createPlugin', $plugin);
        

        $fuc = new ForgeUpgradeConfigTestPluginManager($this);
        $fuc->expectOnce('run');
        $fuc->setReturnValue('run', true);

        //The plugins manager
        $pm = partial_mock('PluginManager', array('_getForgeUpgradeConfig'), array($plugin_factory, mock('EventManager'), mock('SiteCache')));
        stub($pm)->_getForgeUpgradeConfig()->returns($fuc);

        $this->assertReference($pm->installPlugin('New_Plugin'), $plugin);
        
        // plugin manager must call postInstall 1 time on plugin after its creation
        $plugin->expectCallCount('postInstall', 1);

        // Plugin dir was created in "/etc"
        $this->assertTrue(is_dir(dirname(__FILE__).'/test/custom/New_Plugin'));

        // Forgeupgrade config updated
        // do not use parse_ini_file to be independent of implementation
        $wantedLine = dirname(__FILE__).'/test/custom/New_Plugin';
        $lineFound  = false;
        $conf       = file($GLOBALS['forgeupgrade_file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($conf as $line) {
            if (preg_match('%path\[\]\s*=\s*"'.$wantedLine.'"%', $line)) {
                $lineFound = true;
            }
        }
        $this->assertTrue($lineFound, "Forgeupgrade configuration file must contains $wantedLine");


        $this->_remove_directory(dirname(__FILE__).'/test');
    }

    function testIsNameValide() {
        $pm = new PluginManager(mock('PluginFactory'), mock('EventManager'), mock('SiteCache'));
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
        $pm = new PluginManager($plugin_factory, mock('EventManager'), mock('SiteCache'));
        
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
        $this->plugin_manager = new PluginManager($this->plugin_factory, $this->event_manager, mock('SiteCache'));

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
        stub($this->plugin_factory)->getClassName()->returns('DatPlugin');
        stub($this->plugin_factory)->getClassPath()->returns(dirname(__FILE__).'/_fixtures/DatPlugin.php');
    }

    public function tearDown() {
        parent::tearDown();
        unlink('/tmp/hooks.json');
        ForgeConfig::restore();
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
