<?php
require_once('common/plugin/PluginManager.class');
Mock::generatePartial('PluginManager', 'PluginManagerTestVersion', array('_getPluginFactory', '_getEventManager', '_getPluginHookPriorityManager'));
require_once('common/plugin/PluginHookPriorityManager.class');
Mock::generate('PluginHookPriorityManager');
require_once('common/plugin/PluginFactory.class');
Mock::generate('PluginFactory');
require_once('common/plugin/Plugin.class');
Mock::generate('Plugin');
require_once('common/collection/Collection.class');
Mock::generate('Collection');
require_once('common/collection/Iterator.class');
Mock::generate('Iterator');
require_once('common/event/EventManager.class');
Mock::generate('EventManager');
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');
require(getenv('CODEX_LOCAL_INC'));
require($GLOBALS['db_config_file']);

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Tests the class PluginManager
 */
class PluginManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PluginManagerTest($name = 'PluginManager test') {
        $this->UnitTestCase($name);
    }

    function testLoadPlugins() {
        //The hooks
        $hook_A = array('hook' => 'hook_A', 'callback' => 'CallHook', 'recallHook' => true);
        $hook_B = array('hook' => 'hook_B', 'callback' => 'CallHook', 'recallHook' => true);
        $it_hook_p1     =& new MockIterator($this);
        $it_hook_p1->setReturnValue('valid', true);
        $it_hook_p1->setReturnValueAt(2, 'valid', false);
        $it_hook_p1->setReturnReferenceAt(0, 'current', $hook_A);
        $it_hook_p1->setReturnReferenceAt(1, 'current', $hook_B);
        $hooks_p1       =& new MockCollection($this);
        $hooks_p1->setReturnReference('iterator', $it_hook_p1);
        
        $it_hook_p2     =& new MockIterator($this);
        $it_hook_p2->setReturnValue('valid', true);
        $it_hook_p2->setReturnValueAt(1, 'valid', false);
        $it_hook_p2->setReturnReferenceAt(0, 'current', $hook_A);
        $hooks_p2       =& new MockCollection($this);
        $hooks_p2->setReturnReference('iterator', $it_hook_p2);

        //A plugin (available)        --listen A & B
        $plugin_1       =& new MockPlugin($this);
        $plugin_1->expectCallCount('getHooksAndCallbacks', 1);
        $plugin_1->setReturnValue('getId', 123);
        $plugin_1->setReturnReference('getHooksAndCallbacks', $hooks_p1);

        //Another Plugin (available)  --listen only A
        $plugin_2       =& new MockPlugin($this);
        $plugin_2->expectCallCount('getHooksAndCallbacks', 1);
        $plugin_2->setReturnValue('getId', 124);
        $plugin_2->setReturnReference('getHooksAndCallbacks', $hooks_p2);

        //The iterator for available plugins
        $it_available     =& new MockIterator($this);
        $it_available->setReturnValue('valid', true);
        $it_available->setReturnValueAt(2, 'valid', false);
        $it_available->setReturnReferenceAt(0, 'current', $plugin_1);
        $it_available->setReturnReferenceAt(1, 'current', $plugin_2);

        //The available plugins
        $available        =& new MockCollection($this);
        $available->setReturnReference('iterator', $it_available);

        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->setReturnReference('getAvailablePlugins', $available);
        
        //The event manager
        $em             =& new MockEventManager($this);
        $em->expectCallCount('addListener', 3); // 2*A+1*B hooks
        $args_0 = array();
        $args_0[] = $hook_A['hook'];
        $args_0[] =& $plugin_1;
        $args_0[] = $hook_A['callback'];
        $args_0[] = $hook_A['recallHook'];
        $args_0[] = 0;       
        $args_1 = array();
        $args_1[] = $hook_B['hook'];
        $args_1[] =& $plugin_1;
        $args_1[] = $hook_B['callback'];
        $args_1[] = $hook_B['recallHook'];
        $args_1[] = 0;       
        $args_2 = array();
        $args_2[] = $hook_A['hook'];
        $args_2[] =& $plugin_2;
        $args_2[] = $hook_A['callback'];
        $args_2[] = $hook_A['recallHook'];
        $args_2[] = 10;
        $em->expectArgumentsAt(0, 'addListener', $args_0);
        $em->expectArgumentsAt(1, 'addListener', $args_1);
        $em->expectArgumentsAt(2, 'addListener', $args_2);

        //The priorities
        
        $phgm =& new MockPluginHookPriorityManager($this);
        $phgm->expectCallCount('getPriorityForPluginHook', 3);
        $args_phgm_0 = array();
        $args_phgm_0[] =& $plugin_1;
        $args_phgm_0[] = $hook_A['hook'];
        $args_phgm_1 = array();
        $args_phgm_1[] =& $plugin_1;
        $args_phgm_1[] = $hook_B['hook'];
        $args_phgm_2 = array();
        $args_phgm_2[] =& $plugin_2;
        $args_phgm_2[] = $hook_A['hook'];
        
        $phgm->expectArgumentsAt(0, 'getPriorityForPluginHook', $args_phgm_0);
        $phgm->expectArgumentsAt(1, 'getPriorityForPluginHook', $args_phgm_1);
        $phgm->expectArgumentsAt(2, 'getPriorityForPluginHook', $args_phgm_2);
        $phgm->setReturnValue('getPriorityForPluginHook', 0);
        $phgm->setReturnValueAt(2, 'getPriorityForPluginHook', 10);//124|hook_A
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        $pm->setReturnReference('_getEventManager', $em);
        $pm->setReturnReference('_getPluginHookPriorityManager', $phgm);
        $pm->PluginManager();
        $this->assertFalse($pm->isPluginsLoaded());
        $pm->loadPlugins();
        $this->assertTrue($pm->isPluginsLoaded());
        
        $em->tally();
        $plugin_1->tally();
        $plugin_2->tally();
        $phgm->tally();
    }
    
    function testSingleton() {
        $this->assertReference(
                PluginManager::instance(),
                PluginManager::instance());
        $this->assertIsA(PluginManager::instance(), 'PluginManager');
    }
    
    function testGetAllPlugins() {
        
        //The plugins
        $plugins        =& new MockCollection($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->setReturnReference('getAllPlugins', $plugins);
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $this->assertReference($pm->getAllPlugins(), $plugins);
    }
    
    function testIsPluginAvailable() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->setReturnValueAt(0, 'isPluginAvailable', true);
        $plugin_factory->setReturnValueAt(1, 'isPluginAvailable', false);
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $this->assertTrue($pm->isPluginAvailable($plugin));
        $this->assertFalse($pm->isPluginAvailable($plugin));
    }
    
    function testEnablePlugin() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('availablePlugin');
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $pm->availablePlugin($plugin);
        
        $plugin_factory->tally();
    }
    function testDisablePlugin() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('unavailablePlugin');
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $pm->unavailablePlugin($plugin);
        
        $plugin_factory->tally();
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
        $GLOBALS['sys_custompluginsroot'] = dirname(__FILE__).'/test/custom/';
        mkdir(dirname(__FILE__).'/test');
        mkdir(dirname(__FILE__).'/test/custom');
        
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('createPlugin');
        $plugin_factory->expectArguments('createPlugin', array('New_Plugin'));
        $plugin_factory->setReturnReference('createPlugin', $plugin);
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);

        $this->assertReference($pm->installPlugin('New_Plugin'), $plugin);
        $this->_remove_directory(dirname(__FILE__).'/test');
    }
    function testIsNameValide() {
        $pm =& new PluginManager();
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
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('getPluginByName');
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $pm->getPluginByName('plugin_name');
        
        $plugin_factory->tally();
    }
}
?>
