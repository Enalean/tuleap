<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once('cli_constants.php');

require_once(CODEX_CLI_DIR .'/nusoap/nusoap.php');
require_once(CODEX_CLI_DIR .'/include/CodeXSOAP.class.php');
Mock::generate('CodeXSOAP');

require_once(CODEX_CLI_DIR .'/include/CLI_Module.class.php');
Mock::generate('CLI_Module');

require_once(CODEX_CLI_DIR .'/include/CLI_Action.class.php');
Mock::generatePartial('CLI_Action', 'CLI_ActionTestVersion', array('help', '_getCodeXSOAP'));

class CLI_ActionTest extends UnitTestCase {
    function CLI_ActionTest($name = 'CLI_Action test') {
        $this->UnitTestCase($name);
    }
    
    function testHelp() {
        $action =& new CLI_Action('name', 'description');
        $action->addParam(array(
            'name'        => 'loginname',
            'description' => '--username=<username> or -U <username>    Specify the user name',
        ));
        $expected_help = <<< EOS
name:
description

Available parameters:
   --project=<name>   Name of the project the item belongs to. If you specified the name of
      the working project when you logged in, this parameter is not needed.
   --username=<username> or -U <username>    Specify the user name

   --help    Show this screen

EOS;
        $this->assertEqual($action->help(), $expected_help);
    }
    
    function testExecuteHelp() {
        $codexsoap =& new MockCodeXSOAP();
        
        $module =& new MockCLI_Module();
        $module->setReturnValue('getParameter', true);
        
        $action =& new CLI_ActionTestVersion();
        $action->setReturnReference('_getCodeXSOAP', $codexsoap);
        $action->setModule($module);
        $action->expectCallCount('help', 3);
        
        $action->execute(array('-h'));
        $action->execute(array('--help'));
        $action->execute(array());
    }
}
?>
