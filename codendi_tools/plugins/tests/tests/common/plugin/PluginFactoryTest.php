<?php
require_once('common/plugin/PluginFactory.class.php');
Mock::generatePartial('PluginFactory', 'PluginFactoryTestVersion', array('_getClassNameForPluginName'));
require_once('common/dao/PluginDao.class.php');
Mock::generate('PluginDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/plugin/Plugin.class.php');
Mock::generate('Plugin');
require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);

class officialPlugin extends Plugin {
}
class customPlugin extends Plugin {
}
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class PluginFactory
 */
class PluginFactoryTest extends UnitTestCase {

    function testGetPluginById() {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('name' => 'plugin 123', 'available' => 1));
        $access_result->setReturnValueAt(1, 'getRow', false);
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $plugin = $pf->getPluginById(123);
        $this->assertIsA($plugin, 'Plugin');
        
        $plugin = $pf->getPluginById(123);
        $this->assertnoErrors();

        $this->assertFalse($pf->getPluginById(124));
    }
    
    function testGetPluginByName() {
        $plugin_dao    = new MockPluginDao($this);

        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByName', $access_result);
        $access_result->setReturnValue('getRow', false);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => 1));

        $by_id = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $by_id);
        $by_id->setReturnValue('getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => 1));

        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $plugin_1 = $pf->getPluginByName('plugin 123');
        $this->assertIsA($plugin_1, 'Plugin');
        
        
        $plugin_2 = $pf->getPluginByName('plugin 123');
        $this->assertnoErrors();
        $this->assertReference($plugin_1, $plugin_2);

        $this->assertIdentical(false, $pf->getPluginByName('plugin 124'));
    }
    
    function testCreatePlugin() {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByName', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => 123, 'name' => 'plugin 123', 'available' => '1')); //existing plugin
        $access_result->setReturnValueAt(1, 'getRow', false); //new plugin
        $plugin_dao->setReturnValueAt(0, 'create', 125); //its id
        $plugin_dao->setReturnValueAt(0, 'create', false); //error
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $this->assertFalse($pf->createPlugin('existing plugin'));
        $plugin = $pf->createPlugin('new plugin');
        $this->assertEqual($plugin->getId(), 125);
        $this->assertFalse($pf->createPlugin('error plugin creation'));
    }
    
    function testGetAvailableplugins() {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByAvailable', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '1'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $col = $pf->getAvailablePlugins();
        $this->assertEqual(count($col), 2);
    }
    
    function testGetUnavailableplugins() {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByAvailable', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '0'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '0'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $col = $pf->getUnavailablePlugins();
        $this->assertEqual(count($col), 2);
    }
    
    function testGetAllPlugins() {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchALL', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '0'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        $col = $pf->getAllPlugins();
        $this->assertEqual(count($col), 2);
    }
    /**/
    function testIsPluginAvailable() {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1')); 
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '0'));
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        
        $p_1 = $pf->getPluginById(123);
        $this->assertTrue($pf->isPluginAvailable($p_1));
        
        $p_2 = $pf->getPluginById(124);
        $this->assertFalse($pf->isPluginAvailable($p_2));
    }
    
    function testEnablePlugin() {
        $plugin_dao = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '0')); 
        $access_result->setReturnValueAt(1, 'getRow', false);
        $plugin_dao->expectOnce('updateAvailableByPluginId');
        $plugin_dao->expectArguments('updateAvailableByPluginId', array('1', 123));
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        
        $p = $pf->getPluginById(123);
        $pf->availablePlugin($p);
        $plugin_dao->tally();
    }
    
    function testDisablePlugin() {
        $plugin_dao = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1')); //enabled = 1
        $access_result->setReturnValueAt(1, 'getRow', false);
        $plugin_dao->expectOnce('updateAvailableByPluginId');
        $plugin_dao->expectArguments('updateAvailableByPluginId', array('0', 123));
        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        
        $p = $pf->getPluginById(123);
        $pf->unavailablePlugin($p);
        $plugin_dao->tally();
    }
    
    function testPluginIsCustom() {
        $plugin_dao    = new MockPluginDao($this);

        $access_result_custom = new MockDataAccessResult($this);
        $access_result_custom->setReturnValue('getRow', false);
        $access_result_custom->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'custom', 'available' => 1));
        $plugin_dao->setReturnReferenceAt(0, 'searchByName', $access_result_custom);

        $access_result_official = new MockDataAccessResult($this);
        $access_result_official->setReturnValue('getRow', false);
        $access_result_official->setReturnValueAt(0, 'getRow', array('id' => '124', 'name' => 'official', 'available' => 1));
        $plugin_dao->setReturnReferenceAt(1, 'searchByName', $access_result_official);

        $pf = new PluginFactoryTestVersion($this);
        $pf->setReturnValueAt(0, '_getClassNameForPluginName', array('class' => 'customPlugin', 'custom' => true));
        $pf->setReturnValueAt(1, '_getClassNameForPluginName', array('class' => 'officialPlugin', 'custom' => false));
        $pf->PluginFactory($plugin_dao); //Only for test. You should use singleton instead
        
        $plugin_custom = $pf->getPluginByName('custom');
        $this->assertIsA($plugin_custom, 'Plugin');
        $this->assertTrue($pf->pluginIsCustom($plugin_custom));
        
        $plugin_official = $pf->getPluginByName('official');
        $this->assertIsA($plugin_official, 'Plugin');
        $this->assertFalse($pf->pluginIsCustom($plugin_official));
    }
}
?>
