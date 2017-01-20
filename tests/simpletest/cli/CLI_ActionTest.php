<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
* 
*/

require_once('cli_constants.php');

require_once(CODENDI_CLI_DIR .'/include/CodendiSOAP.class.php');
Mock::generate('CodendiSOAP');

require_once(CODENDI_CLI_DIR .'/include/CLI_Module.class.php');
Mock::generate('CLI_Module');

require_once(CODENDI_CLI_DIR .'/include/CLI_Action.class.php');
Mock::generatePartial('CLI_Action', 'CLI_ActionTestVersion', array('help', '_getCodendiSOAP'));

class CLI_ActionTest extends TuleapTestCase {
    
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
   --quiet or -q      Quiet-mode. Suppress result output.
   --project=<name>   Name of the project the item belongs to. If you specified the name of
      the working project when you logged in, this parameter is not needed.
   --username=<username> or -U <username>    Specify the user name

   --help    Show this screen

EOS;
        $this->assertEqual($action->help(), $expected_help);
    }
    
    function testExecuteHelp() {
        $codendisoap = new MockCodendiSOAP();
        
        $module =& new MockCLI_Module();
        $module->setReturnValue('getParameter', true);
        
        $action =& new CLI_ActionTestVersion();
        $action->setReturnReference('_getCodendiSOAP', $codendisoap);
        $action->setModule($module);
        $action->expectCallCount('help', 3);
        
        $action->execute(array('-h'));
        $action->execute(array('--help'));
        $action->execute(array());
    }
}
?>
