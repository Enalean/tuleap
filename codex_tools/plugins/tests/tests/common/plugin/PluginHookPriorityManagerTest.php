<?php
require_once('common/plugin/PluginHookPriorityManager.class.php');
Mock::generatePartial('PluginHookPriorityManager', 'PluginHookPriorityManagerTestVersion', array('_getPriorityPluginHookDao'));
require_once('common/dao/PriorityPluginHookDao.class.php');
Mock::generate('PriorityPluginHookDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/plugin/Plugin.class.php');
Mock::generate('Plugin');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginHookPriorityManagerTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class PluginHookPriorityManager
 */
class PluginHookPriorityManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PluginHookPriorityManagerTest($name = 'PluginHookPriorityManager test') {
        $this->UnitTestCase($name);
    }

    function testGetPriorityForPluginHook() {
        $plugin       =& new MockPlugin($this);
        $plugin->setReturnValue('getId', 123);
        
        $priority_dao =& new MockPriorityPluginHookDao($this);
        $priority_dar =& new MockDataAccessResult($this);
        $priority_dao->setReturnReference('searchByHook_PluginId', $priority_dar);
        $priority_dao->expectCallCount('searchByHook_PluginId', 1);
        $priority_dao->expectArguments('searchByHook_PluginId', array('hook', 123));
        $priority_dar->setReturnValue('getRow', array('priority' => '10'));

        $phgm =& new PluginHookPriorityManagerTestVersion($this);
        $phgm->setReturnReference('_getPriorityPluginHookDao', $priority_dao);

        $this->assertEqual(10, $phgm->getPriorityForPluginHook($plugin, 'hook'));
        
        $priority_dao->tally();
    }
    
    function testSetPriorityForPluginHook() {
        $plugin       =& new MockPlugin($this);
        $plugin->setReturnValue('getId', 123);
        
        $priority_dao =& new MockPriorityPluginHookDao($this);
        $priority_dao->expectCallCount('setPriorityForHook_PluginId', 1);
        $priority_dao->expectArguments('setPriorityForHook_PluginId', array('hook', 123, 15));
        $priority_dao->setReturnValue('setPriorityForHook_PluginId', true);
        
        $phgm =& new PluginHookPriorityManagerTestVersion($this);
        $phgm->setReturnReference('_getPriorityPluginHookDao', $priority_dao);

        $this->assertTrue($phgm->setPriorityForPluginHook($plugin, 'hook', 15));
        
        $priority_dao->tally();
    }
    
    function testRemovePlugin() {
        $plugin       =& new MockPlugin($this);
        $plugin->setReturnValue('getId', 123);
        
        $priority_dao =& new MockPriorityPluginHookDao($this);
        $priority_dao->expectCallCount('deleteByPluginId', 1);
        $priority_dao->expectArguments('deleteByPluginId', array(123));
        $priority_dao->setReturnValue('deleteByPluginId', true);
        
        $phgm =& new PluginHookPriorityManagerTestVersion($this);
        $phgm->setReturnReference('_getPriorityPluginHookDao', $priority_dao);

        $this->assertTrue($phgm->removePlugin($plugin));
        
        $priority_dao->tally();
    }
}
?>
