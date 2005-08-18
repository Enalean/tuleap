<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/plugin/PluginManager.class');
Mock::generatePartial('PluginManager', 'PluginManagerTestVersion', array('_getPluginFactory', '_getEventManager', '_getPriorityPluginHookDao'));
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
require_once('common/dao/PriorityPluginHookDao.class');
Mock::generate('PriorityPluginHookDao');
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginManagerTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
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
        $it_hook_p1->setReturnValue('hasNext', true);
        $it_hook_p1->setReturnValueAt(2, 'hasNext', false);
        $it_hook_p1->setReturnReferenceAt(0, 'next', $hook_A);
        $it_hook_p1->setReturnReferenceAt(1, 'next', $hook_B);
        $hooks_p1       =& new MockCollection($this);
        $hooks_p1->setReturnReference('iterator', $it_hook_p1);
        
        $it_hook_p2     =& new MockIterator($this);
        $it_hook_p2->setReturnValue('hasNext', true);
        $it_hook_p2->setReturnValueAt(1, 'hasNext', false);
        $it_hook_p2->setReturnReferenceAt(0, 'next', $hook_A);
        $hooks_p2       =& new MockCollection($this);
        $hooks_p2->setReturnReference('iterator', $it_hook_p2);

        //A plugin (enabled)        --listen A & B
        $plugin_1       =& new MockPlugin($this);
        $plugin_1->expectCallCount('getHooksAndCallbacks', 1);
        $plugin_1->setReturnValue('getId', 123);
        $plugin_1->setReturnReference('getHooksAndCallbacks', $hooks_p1);

        //Another Plugin (enabled)  --listen only A
        $plugin_2       =& new MockPlugin($this);
        $plugin_2->expectCallCount('getHooksAndCallbacks', 1);
        $plugin_2->setReturnValue('getId', 124);
        $plugin_2->setReturnReference('getHooksAndCallbacks', $hooks_p2);

        //The iterator for enabled plugins
        $it_enabled     =& new MockIterator($this);
        $it_enabled->setReturnValue('hasNext', true);
        $it_enabled->setReturnValueAt(2, 'hasNext', false);
        $it_enabled->setReturnReferenceAt(0, 'next', $plugin_1);
        $it_enabled->setReturnReferenceAt(1, 'next', $plugin_2);

        //The enabled plugins
        $enabled        =& new MockCollection($this);
        $enabled->setReturnReference('iterator', $it_enabled);

        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->setReturnReference('getEnabledPlugins', $enabled);
        
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
        $priority_dao =& new MockPriorityPluginHookDao($this);
        $priority_dar =& new MockDataAccessResult($this);
        $priority_dao->setReturnReference('searchByHook_PluginId', $priority_dar);
        $priority_dao->expectArgumentsAt(0, 'searchByHook_PluginId', array($hook_A['hook'], 123));
        $priority_dao->expectArgumentsAt(1, 'searchByHook_PluginId', array($hook_B['hook'], 123));
        $priority_dao->expectArgumentsAt(2, 'searchByHook_PluginId', array($hook_A['hook'], 124));
        $priority_dar->setReturnValue('getRow', false);
        $priority_dar->setReturnValueAt(2, 'getRow', array('priority' => '10')); //124|hook_A
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        $pm->setReturnReference('_getEventManager', $em);
        $pm->setReturnReference('_getPriorityPluginHookDao', $priority_dao);
        $pm->PluginManager();
        $this->assertFalse($pm->isPluginsLoaded());
        $pm->loadPlugins();
        $this->assertTrue($pm->isPluginsLoaded());
        
        $em->tally();
        $priority_dao->tally();
        $plugin_1->tally();
        $plugin_2->tally();
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
    
    function testIsPluginEnabled() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->setReturnValueAt(0, 'isPluginEnabled', true);
        $plugin_factory->setReturnValueAt(1, 'isPluginEnabled', false);
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $this->assertTrue($pm->isPluginEnabled($plugin));
        $this->assertFalse($pm->isPluginEnabled($plugin));
    }
    
    function testEnablePlugin() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('enablePlugin');
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $pm->enablePlugin($plugin);
        
        $plugin_factory->tally();
    }
    function testDisablePlugin() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('disablePlugin');
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);
        
        $pm->disablePlugin($plugin);
        
        $plugin_factory->tally();
    }
    function testInstallPlugin() {
        //The plugins
        $plugin =& new MockPlugin($this);
        
        //The plugin factory
        $plugin_factory =& new MockPluginFactory($this);
        $plugin_factory->expectOnce('createPlugin');
        $plugin_factory->expectArguments('createPlugin', array('New Plugin'));
        $plugin_factory->setReturnReference('createPlugin', $plugin);
        
        //The plugins manager
        $pm =& new PluginManagerTestVersion($this);
        $pm->setReturnReference('_getPluginFactory', $plugin_factory);

        $this->assertReference($pm->installPlugin('New Plugin'), $plugin);
        
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new PluginManagerTest();
    $test->run(new CodexReporter());
 }
?>
