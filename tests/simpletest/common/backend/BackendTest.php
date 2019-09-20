<?php
/*
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

//Create a fake backend class
class BackendTest_FakeBackend
{
}

//Create a fake backend class which simulate an override of BackendSVN by a plugin
class BackendTest_BackendSVN_overriden_by_plugin extends BackendSVN
{
}
class BackendTest_BackendSVN_overriden_by_plugin_and_has_setUp extends BackendSVN
{
    public $a_variable_for_tests = -25;
    public function setUp($a, $b, $c)
    {
        $this->a_variable_for_tests = ($a + $b) * $c;
    }
}

//Simulate a plugin
class BackendTest_Plugin
{
    public function get_backend($params)
    {
        $params['base'] = 'BackendTest_BackendSVN_overriden_by_plugin';
    }
}
//Simulate a plugin
class BackendTest_Plugin_With_SetUp
{
    public function get_backend($params)
    {
        $params['base'] = 'BackendTest_BackendSVN_overriden_by_plugin_and_has_setUp';
    }
}
//Simulate a plugin
class BackendTest_Plugin_With_SetUp_And_Params
{
    public function get_backend($params)
    {
        $params['base']  = 'BackendTest_BackendSVN_overriden_by_plugin_and_has_setUp';
        $params['setup'] = array(1, 2, 3);
    }
}

class BackendTest extends TuleapTestCase
{

    function __construct($name = 'BackendSystem test')
    {
        parent::__construct($name);
    }

    public function tearDown()
    {
        //clear the cache between each tests
        Backend::clearInstances();
        EventManager::clearInstance();
        parent::tearDown();
    }

    function testFactory_core()
    {
        // Core backends
        $this->assertIsA(Backend::instance(Backend::SVN), 'BackendSVN');
        $this->assertIsA(Backend::instance(Backend::CVS), 'BackendCVS');
        $this->assertIsA(Backend::instance(Backend::MAILINGLIST), 'BackendMailingList');
        $this->assertIsA(Backend::instance(Backend::BACKEND), 'Backend');
        $this->assertIsA(Backend::instance(Backend::SYSTEM), 'BackendSystem');
        $this->assertIsA(Backend::instance(Backend::ALIASES), 'BackendAliases');
    }

    function testFactory_plugin()
    {
        //Plugin backends. Give the base classname to build the backend
        $this->assertIsA(Backend::instance('plugin_fake', 'BackendTest_FakeBackend'), 'BackendTest_FakeBackend'); //like plugins !
    }

    function testFactory_plugin_bad()
    {
        //The base classname is mandatory for unkown (by core) backends
        // else it search for Backend . $type
        $this->expectException('RuntimeException');
        $nop = Backend::instance('plugin_fake');
    }

    function testFactory_override()
    {
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            new BackendTest_Plugin(),
            'get_backend',
            false
        );
        $this->assertIsA(Backend::instance(Backend::SVN), 'BackendTest_BackendSVN_overriden_by_plugin');
    }

    function testFactory_override_without_parameters()
    {
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            new BackendTest_Plugin_With_SetUp(),
            'get_backend',
            false
        );
        $b = Backend::instance(Backend::SVN);
        $this->assertEqual($b->a_variable_for_tests, -25);
    }

    function testFactory_override_with_parameters()
    {
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            new BackendTest_Plugin_With_SetUp(),
            'get_backend',
            false
        );
        $b = Backend::instance(Backend::SVN, null, array(1, 2, 3));
        $this->assertEqual($b->a_variable_for_tests, 9);
    }

    function testFactory_override_with_parameters_defined_in_plugin()
    {
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            new BackendTest_Plugin_With_SetUp_And_Params(),
            'get_backend',
            false
        );
        $b = Backend::instance(Backend::SVN);
        $this->assertEqual($b->a_variable_for_tests, 9);
    }

    function testFactory_override_with_parameters_but_no_setUp()
    {
        //Plugins can override default backends.
        // For example, plugin_ldap can override the backend define in plugin_svn
        EventManager::instance()->addListener(
            Event::BACKEND_FACTORY_GET_SVN,
            new BackendTest_Plugin(),         //no setup !!!
            'get_backend',
            false
        );
        $this->expectException();
        $b = Backend::instance(Backend::SVN, null, array(1, 2, 3));
    }

    function testrecurseDeleteInDir()
    {
        $test_dir =  $this->getTmpDir();

        // Create dummy dirs and files
        mkdir($test_dir."/test1");
        mkdir($test_dir."/test1/A");
        mkdir($test_dir."/test1/B");
        mkdir($test_dir."/test2");
        mkdir($test_dir."/test2/A");
        mkdir($test_dir."/test3");

        // Run tested method
        Backend::instance()->recurseDeleteInDir($test_dir);

        // Check result

        // Direcory should not be removed
        $this->assertTrue(is_dir($test_dir), "Directory $test_dir should still exist");
        // And should be empty
        $d = opendir($test_dir);
        while (($file = readdir($d)) !== false) {
            $this->assertTrue($file == "." || $file == "..", "Directory should be empty");
        }
        closedir($d);
        rmdir($test_dir);
    }
}
