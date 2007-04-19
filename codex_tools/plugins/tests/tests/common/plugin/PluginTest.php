<?php
require_once('common/plugin/Plugin.class.php');
Mock::generatePartial('Plugin', 'PluginTestVersion', array('_getPluginManager'));
class TestPlugin extends Plugin {
    function addHook($hook, $callback = 'CallHook', $recallHook = true) {
        $this->_addHook($hook, $callback, $recallHook);
    }
    function removeHook(&$hook) {
        $this->_removeHook($hook);
    }
}
require_once('common/plugin/PluginManager.class.php');
Mock::generate('PluginManager');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Tests the class Plugin
 */
class PluginTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PluginTest($name = 'Plugin test') {
        $this->UnitTestCase($name);
    }

    function testId() {
        $p =& new Plugin();
        $this->assertEqual($p->getId(), -1);
        $p =& new Plugin(123);
        $this->assertEqual($p->getId(), 123);
    }
    
    function testPluginInfo() {
        $p =& new Plugin();
        $this->assertIsA($p->getPluginInfo(), 'PluginInfo');
    }
    
    function testHooks() {
        $p =& new TestPlugin();
        $col =& $p->getHooks();
        $this->assertTrue($col->isEmpty());
        
        $hook1 = 'hook 1';
        $p->addHook($hook1);
        $col =& $p->getHooks();
        $this->assertFalse($col->isEmpty());
        $this->assertTrue($col->contains($hook1));
        
        $hook2 = 'hook 2';
        $p->addHook($hook2);
        $col =& $p->getHooks();
        $this->assertTrue($col->contains($hook1));
        $this->assertTrue($col->contains($hook2));
        
        $p->removeHook($hook1);
        $col =& $p->getHooks();
        $this->assertFalse($col->contains($hook1));
        $this->assertTrue($col->contains($hook2));
        
        $p->removeHook($hook2);
        $col =& $p->getHooks();
        $this->assertTrue($col->isEmpty());
    }
    
    function testDefaultCallback() {
        $p =& new TestPlugin();
        $col =& $p->getHooksAndCallbacks();
        $this->assertTrue($col->isEmpty());
        
        $hook = 'name_of_hook';
        $p->addHook($hook);
        $col =& $p->getHooksAndCallbacks();
        $it =& $col->iterator();
        $current_hook =& $it->current();
        $this->assertEqual($current_hook['hook'],       $hook);
        $this->assertEqual($current_hook['callback'],   'CallHook');
        $this->assertTrue($current_hook['recallHook']);
    }
    function testSpecialCallback() {
        $p =& new TestPlugin();
        
        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $p->addHook($hook, $callback);
        $col =& $p->getHooksAndCallbacks();
        $it =& $col->iterator();
        $current_hook =& $it->current();
        $this->assertEqual($current_hook['hook'],       $hook);
        $this->assertEqual($current_hook['callback'],   $callback);
        $this->assertTrue($current_hook['recallHook']);
    }
    function testAnotherSpecialCallback() {
        $p =& new TestPlugin();
        
        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $recall   = false;
        $p->addHook($hook, $callback, $recall);
        $col =& $p->getHooksAndCallbacks();
        $it =& $col->iterator();
        $current_hook =& $it->current();
        $this->assertEqual($current_hook['hook'],       $hook);
        $this->assertEqual($current_hook['callback'],   $callback);
        $this->assertEqual($current_hook['recallHook'], $recall);
    }
    function testScope() {
        $p =& new Plugin();
        $this->assertIdentical($p->getScope(), $p->SCOPE_SYSTEM);
        $this->assertNotEqual($p->getScope(), $p->SCOPE_PROJECT);
        $this->assertNotEqual($p->getScope(), $p->SCOPE_USER);
    }
    function testGetPluginEtcRoot() {
        $GLOBALS['sys_custompluginsroot'] = dirname(__FILE__).'/test/custom/';
        $shortname = 'shortname';
        $pm =& new MockPluginManager($this);
        $pm->setReturnValue('getNameForPlugin', $shortname);
        $p =& new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);
        $p->Plugin();
        
        $this->assertEqual($p->getPluginEtcRoot(), $GLOBALS['sys_custompluginsroot'].'/'.$shortname.'/etc');        
     }
    function testGetPluginPath() {
        $GLOBALS['sys_pluginspath']       = '/plugins';
        $GLOBALS['sys_custompluginspath'] = '/customplugins';
        $shortname = 'shortname';
        $pm =& new MockPluginManager($this);
        $pm->setReturnValue('pluginIsCustom', true);
        $pm->setReturnValueAt(0, 'pluginIsCustom', false);
        $pm->setReturnValue('getNameForPlugin', $shortname);
        $p =& new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);
        $p->Plugin();
        
        $this->assertEqual($p->_getPluginPath(), $GLOBALS['sys_pluginspath'].'/'.$shortname);
        $this->assertEqual($p->_getPluginPath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname);
    }
    function testGetThemePath() {
        $GLOBALS['sys_user_theme']        = 'current_theme';
        $GLOBALS['sys_pluginspath']       = '/plugins';
        $GLOBALS['sys_custompluginspath'] = '/customplugins';
        $GLOBALS['sys_pluginsroot']       = dirname(__FILE__).'/test/plugins/';
        $GLOBALS['sys_custompluginsroot'] = dirname(__FILE__).'/test/custom/';
        mkdir(dirname($GLOBALS['sys_pluginsroot']));
        
        $shortname     = 'shortname';
        $pm =& new MockPluginManager($this);
        $pm->setReturnValue('pluginIsCustom', false);
        $pm->setReturnValueAt(4, 'pluginIsCustom', true);
        $pm->setReturnValueAt(5, 'pluginIsCustom', true);
        $pm->setReturnValue('getNameForPlugin', $shortname);
        $p =& new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);
        $p->Plugin();
        
        //Plugin is official
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        $this->assertEqual($p->_getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_pluginsroot']);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        $this->assertEqual($p->_getThemePath(), $GLOBALS['sys_pluginspath'].'/'.$shortname.'/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname);
        rmdir($GLOBALS['sys_pluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        $this->assertEqual($p->_getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_pluginsroot']);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/default');
        $this->assertEqual($p->_getThemePath(), $GLOBALS['sys_pluginspath'].'/'.$shortname.'/themes/default');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/default');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname);
        rmdir($GLOBALS['sys_pluginsroot']);
        
        
        //Now plugin is custom
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        $this->assertEqual($p->_getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        $this->assertEqual($p->_getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        
        rmdir(dirname($GLOBALS['sys_custompluginsroot']));
    }
}
?>
