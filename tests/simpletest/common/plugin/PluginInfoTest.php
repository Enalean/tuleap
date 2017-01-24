<?php
require_once('common/plugin/PluginInfo.class.php');
require_once('common/collection/Collection.class.php');
require_once('common/plugin/Plugin.class.php');
Mock::generate('Plugin');
require_once('common/include/PropertyDescriptor.class.php');
Mock::generate('PropertyDescriptor');
class TestPluginInfo extends PluginInfo {
    function addPropertyDescriptor(&$desc) {
        $this->_addPropertyDescriptor($desc);
    }
    function removePropertyDescriptor(&$desc) {
        $this->_removePropertyDescriptor($desc);
    }
}
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class PluginInfo
 */
class PluginInfoTest extends TuleapTestCase {

    function testPluginDescriptor() {
        $p  = new MockPlugin($this);
        $pi = new PluginInfo($p);
        $pd = $pi->getPluginDescriptor();
        $this->assertIsA($pd, 'PluginDescriptor');
        $this->assertEqual($pd->getFullName(), '');
        $this->assertEqual($pd->getVersion(), '');
        $this->assertEqual($pd->getDescription(), '');
        $pi->setPluginDescriptor(new PluginDescriptor('TestPlugin', 'v1.0', 'A simple plugin, just for unit testing'));
        
        $pd = $pi->getPluginDescriptor();
        $this->assertEqual($pd->getFullName(), 'TestPlugin');
        $this->assertEqual($pd->getVersion(), 'v1.0');
        $this->assertEqual($pd->getDescription(), 'A simple plugin, just for unit testing');
        
    }
    
    function testPropertyDescriptor() {
        $name_d1 = 'd1';
        $name_d2 = 'd2';
        $p  = new MockPlugin($this);
        $pi = new TestPluginInfo($p);
        $d1 = new MockPropertyDescriptor($this);
        $d1->setReturnReference('getName', $name_d1);
        $d2 = new MockPropertyDescriptor($this);
        $d2->setReturnReference('getName', $name_d2);
        $d3 = new MockPropertyDescriptor($this);
        $d3->setReturnReference('getName', $name_d1);
        $pi->addPropertyDescriptor($d1);
        $pi->addPropertyDescriptor($d2);
        $pi->addPropertyDescriptor($d3);
        $expected = new Map();
        $expected->put($name_d2, $d2);
        $expected->put($name_d1, $d3);
        $descriptors = $pi->getpropertyDescriptors();
        $this->assertTrue($expected->equals($descriptors));
        
        $pi->removePropertyDescriptor($d3);
        $descriptors = $pi->getpropertyDescriptors();
        $this->assertFalse($expected->equals($descriptors));
        $this->assertEqual($descriptors->size(), 1);
    }
}
?>
