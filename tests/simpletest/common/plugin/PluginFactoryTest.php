<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

Mock::generatePartial('PluginFactory', 'PluginFactoryTestVersion', array('_getClassNameForPluginName'));
Mock::generate('PluginDao');
Mock::generate('DataAccessResult');
Mock::generate('Plugin');

class officialPlugin extends Plugin
{
}
class customPlugin extends Plugin
{
}
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class PluginFactory
 */
class PluginFactoryTest extends TuleapTestCase
{

    function testGetPluginById()
    {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('name' => 'plugin 123', 'available' => 1));
        $access_result->setReturnValueAt(1, 'getRow', false);
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $plugin = $pf->getPluginById(123);
        $this->assertIsA($plugin, 'Plugin');

        $plugin = $pf->getPluginById(123);

        $this->assertFalse($pf->getPluginById(124));
    }

    function testGetPluginByName()
    {
        $plugin_dao    = new MockPluginDao($this);

        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByName', $access_result);
        $access_result->setReturnValue('getRow', false);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => 1));

        $by_id = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $by_id);
        $by_id->setReturnValue('getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => 1));

        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $plugin_1 = $pf->getPluginByName('plugin 123');
        $this->assertIsA($plugin_1, 'Plugin');

        $plugin_2 = $pf->getPluginByName('plugin 123');

        $this->assertReference($plugin_1, $plugin_2);

        $this->assertIdentical(false, $pf->getPluginByName('plugin 124'));
    }

    function testCreatePlugin()
    {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByName', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => 123, 'name' => 'plugin 123', 'available' => '1')); //existing plugin
        $access_result->setReturnValueAt(1, 'getRow', false); //new plugin
        $plugin_dao->setReturnValueAt(0, 'create', 125); //its id
        $plugin_dao->setReturnValueAt(0, 'create', false); //error
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $this->assertFalse($pf->createPlugin('existing plugin'));
        $plugin = $pf->createPlugin('new plugin');
        $this->assertEqual($plugin->getId(), 125);
        $this->assertFalse($pf->createPlugin('error plugin creation'));
    }

    function testGetAvailableplugins()
    {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByAvailable', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '1'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $col = $pf->getAvailablePlugins();
        $this->assertEqual(count($col), 2);
    }

    function testGetUnavailableplugins()
    {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchByAvailable', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '0'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '0'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $col = $pf->getUnavailablePlugins();
        $this->assertEqual(count($col), 2);
    }

    function testGetAllPlugins()
    {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchALL', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '0'));
        $access_result->setReturnValueAt(2, 'getRow', false);
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));
        $col = $pf->getAllPlugins();
        $this->assertEqual(count($col), 2);
    }
    function testIsPluginAvailable()
    {
        $plugin_dao    = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1'));
        $access_result->setReturnValueAt(1, 'getRow', array('id' => '124', 'name' => 'plugin 124', 'available' => '0'));
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));

        $p_1 = $pf->getPluginById(123);
        $this->assertTrue($pf->isPluginAvailable($p_1));

        $p_2 = $pf->getPluginById(124);
        $this->assertFalse($pf->isPluginAvailable($p_2));
    }

    function testEnablePlugin()
    {
        $plugin_dao = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '0'));
        $access_result->setReturnValueAt(1, 'getRow', false);
        $plugin_dao->expectOnce('updateAvailableByPluginId', array('1', 123));
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));

        $p = $pf->getPluginById(123);
        $pf->availablePlugin($p);
    }

    function testDisablePlugin()
    {
        $plugin_dao = new MockPluginDao($this);
        $access_result = new MockDataAccessResult($this);
        $plugin_dao->setReturnReference('searchById', $access_result);
        $access_result->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'plugin 123', 'available' => '1')); //enabled = 1
        $access_result->setReturnValueAt(1, 'getRow', false);
        $plugin_dao->expectOnce('updateAvailableByPluginId', array('0', 123));
        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValue('_getClassNameForPluginName', array('class' => 'Plugin', 'custom' => false));

        $p = $pf->getPluginById(123);
        $pf->unavailablePlugin($p);
    }

    function testPluginIsCustom()
    {
        $plugin_dao    = new MockPluginDao($this);

        $access_result_custom = new MockDataAccessResult($this);
        $access_result_custom->setReturnValue('getRow', false);
        $access_result_custom->setReturnValueAt(0, 'getRow', array('id' => '123', 'name' => 'custom', 'available' => 1));
        $plugin_dao->setReturnReferenceAt(0, 'searchByName', $access_result_custom);

        $access_result_official = new MockDataAccessResult($this);
        $access_result_official->setReturnValue('getRow', false);
        $access_result_official->setReturnValueAt(0, 'getRow', array('id' => '124', 'name' => 'official', 'available' => 1));
        $plugin_dao->setReturnReferenceAt(1, 'searchByName', $access_result_official);

        $restrictor = mock('PluginResourceRestrictor');
        $pf = partial_mock('PluginFactory', array('_getClassNameForPluginName'), array($plugin_dao, $restrictor));
        $pf->setReturnValueAt(0, '_getClassNameForPluginName', array('class' => 'customPlugin', 'custom' => true));
        $pf->setReturnValueAt(1, '_getClassNameForPluginName', array('class' => 'officialPlugin', 'custom' => false));

        $plugin_custom = $pf->getPluginByName('custom');
        $this->assertIsA($plugin_custom, 'Plugin');
        $this->assertTrue($pf->pluginIsCustom($plugin_custom));

        $plugin_official = $pf->getPluginByName('official');
        $this->assertIsA($plugin_official, 'Plugin');
        $this->assertFalse($pf->pluginIsCustom($plugin_official));
    }
}
