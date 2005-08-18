<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
//require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/plugin/Plugin.class');

class TestPlugin extends Plugin {
    function addHook($hook, $callback = 'CallHook', $recallHook = true) {
        $this->_addHook($hook, $callback, $recallHook);
    }
    function removeHook(&$hook) {
        $this->_removeHook($hook);
    }
}

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
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
        $current_hook =& $it->next();
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
        $current_hook =& $it->next();
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
        $current_hook =& $it->next();
        $this->assertEqual($current_hook['hook'],       $hook);
        $this->assertEqual($current_hook['callback'],   $callback);
        $this->assertEqual($current_hook['recallHook'], $recall);
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new PluginTest();
    $test->run(new CodexReporter());
 }
?>
