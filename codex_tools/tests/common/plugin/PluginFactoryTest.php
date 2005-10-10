<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/plugin/PluginFactory.class');
Mock::generatePartial('PluginFactory', 'PluginFactoryTestVersion', array('_getClassNameForPluginName'));
require_once('common/dao/PluginDao.class');
Mock::generate('PluginDao');
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');
require_once('common/plugin/Plugin.class');
Mock::generate('Plugin');
require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginFactoryTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
 *
 * Tests the class PluginFactory
 */
class PluginFactoryTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PluginFactoryTest($name = 'PluginFactory test') {
        $this->UnitTestCase($name);
    }

    function testSingleton() {
        $this->assertReference(
                PluginFactory::instance(),
                PluginFactory::instance());
        $this->assertIsA(PluginFactory::instance(), 'PluginFactory');
    }
    
    function testGetPluginById() {
        $plugin_dao    =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('name' => 'plugin 123', 'enabled' => '1'));
        $access_result->setReturnValueAt(1, 'getRow', false);
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $plugin =& $pf->getPluginById(123);
        $this->assertIsA($plugin, 'Plugin');
        
        $plugin =& $pf->getPluginById(123);
        $this->assertnoErrors();

        $this->assertFalse($pf->getPluginById(124));
    }
    
    function testCreatePlugin() {
        $plugin_dao    =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByName', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => 123, 'enabled' => '1')); //existing plugin
        $access_result->setReturnValueAt(1, 'getRow', false); //new plugin
        $plugin_dao->setReturnValueAt(0, 'create', 125); //its id
        $plugin_dao->setReturnValueAt(0, 'create', false); //error
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $this->assertFalse($pf->createPlugin('existing plugin'));
        $plugin =& $pf->createPlugin('new plugin');
        $this->assertEqual($plugin->getId(), 125);
        $this->assertFalse($pf->createPlugin('error plugin creation'));
    }
    
    function testGetEnabledplugins() {
        $plugin_dao    =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByEnabled', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $col =& $pf->getEnabledPlugins();
        $this->assertEqual($col->size(), 2);
    }
    function testGetDisabledplugins() {
        $plugin_dao    =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByEnabled', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $col =& $pf->getDisabledPlugins();
        $this->assertEqual($col->size(), 2);
    }
    function testGetAllPlugins() {
        $plugin_dao    =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchALL', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $col =& $pf->getAllPlugins();
        $this->assertEqual($col->size(), 2);
    }
    function testIsPluginEnabled() {
        $p_1           =& new MockPlugin($this);
        $p_2           =& new MockPlugin($this);
        $plugin_dao    =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $p_1->setReturnValue('getId', 123);
        $p_2->setReturnValue('getId', 124);
        $plugin_dao->setReturnReference('searchByEnabled', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123')); //enabled = 1
        $access_result->setReturnValueAt(1, 'getRow', false);
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $this->assertTrue($pf->isPluginEnabled($p_1));
        $this->assertFalse($pf->isPluginEnabled($p_2));
    }
    function testEnablePlugin() {
        $p          =& new MockPlugin($this);
        $plugin_dao =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByEnabled', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '66', 'name' => 'plugin 123')); //this is not 123 !
        $access_result->setReturnValueAt(1, 'getRow', false);
        $p->setReturnValue('getid', 123);
        $plugin_dao->expectOnce('updateEnabledByPluginId');
        $plugin_dao->expectArguments('updateEnabledByPluginId', array('1', 123));
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $pf->enablePlugin($p);
        $plugin_dao->tally();
    }
    function testDisablePlugin() {
        $p          =& new MockPlugin($this);
        $plugin_dao =& new MockPluginDao($this);
        $access_result =& new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByEnabled', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123')); //enabled = 1
        $access_result->setReturnValueAt(1, 'getRow', false);
        $p->setReturnValue('getid', 123);
        $plugin_dao->expectOnce('updateEnabledByPluginId');
        $plugin_dao->expectArguments('updateEnabledByPluginId', array('0', 123));
        $pf =& new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', 'Plugin');
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $pf->disablePlugin($p);
        $plugin_dao->tally();
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new PluginFactoryTest();
    $test->run(new CodexReporter());
 }
?>
