<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
* 
*/

require_once('cli_constants.php');

require_once(CODENDI_CLI_DIR .'/include/CLI_ModuleFactory.class.php');
require_once(CODENDI_CLI_DIR .'/include/CLI_Module.class.php');

class CLI_ModuleFactoryTest extends TuleapTestCase {
    private $fixtures;
    
    public function setUp()
    {
        parent::setUp();
        $this->fixtures = dirname(__FILE__).'/_fixtures/';
    }
    
    function testExist() {
        $mf = new CLI_ModuleFactory($this->fixtures);
        $this->assertTrue($mf->exist('module'));
        
        $mf = new CLI_ModuleFactory($this->fixtures);
        $this->assertFalse($mf->exist('bad_module'));
        
        $mf = new CLI_ModuleFactory($this->fixtures);
        $this->assertFalse($mf->exist('does_not_exist'));
    }
    
    function testGetModule() {
        $mf = new CLI_ModuleFactory($this->fixtures);
        $m = $mf->getModule('module');
        $this->assertIsA($m, 'CLI_Module');
        $this->assertIsA($m, 'CLI_Module_Module');
    }
    
    function testGetAllModules() {
        $mf = new CLI_ModuleFactory($this->fixtures);
        $modules = $mf->getAllModules();
        $this->assertEqual(count($modules), 1);
        foreach($modules as $name => $module) {
            $this->assertIsA($module, 'CLI_Module');
            $this->assertEqual($name, $module->name);
        }
    }
}
