<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
* 
*/

require_once('cli_constants.php');

require_once(CODENDI_CLI_DIR .'/include/CLI_Action.class.php');
Mock::generate('CLI_Action');

require_once(CODENDI_CLI_DIR .'/include/CLI_Module.class.php');
Mock::generatePartial('CLI_Module', 'CLI_ModuleTestVersion', array('help'));


class CLI_ModuleTest extends TuleapTestCase {
    
    function test_get_parameter() {
        $params = explode(' ', '-v --name=john -l doe -abc --content=/tracker/?group_id=1');
        $m =& new CLI_Module('name', 'description');
        $this->assertFalse($m->getParameter($params, 'version'));
        $this->assertTrue($m->getParameter($params, 'v'));
        $this->assertTrue($m->getParameter($params, 'a'));
        $this->assertTrue($m->getParameter($params, 'b'));
        $this->assertTrue($m->getParameter($params, 'c'));
        $this->assertEqual($m->getParameter($params, array('n', 'name'), true), 'john');
        $this->assertEqual($m->getParameter($params, array('l', 'lastname'), true), 'doe');
        $this->assertEqual($m->getParameter($params, 'content', true), '/tracker/?group_id=1');
    }
    
    function testExecute() {
        $action =& new MockCLI_Action();
        $action->setReturnValue('getName', 'action_name');
        $action->expectOnce('execute');
        
        $other_action =& new MockCLI_Action();
        $other_action->setReturnValue('getName', 'other_action_name');
        $other_action->expectNever('execute');
        
        
        $params = explode(' ', $action->getName());
        $m =& new CLI_Module('name', 'description');
        $m->addAction($action);
        $m->addAction($other_action);
        $m->execute($params);
    }
    
    function testNoAction() {
        $m =& new CLI_ModuleTestVersion();
        $m->expectCallCount('help', 3);
        
        $m->execute(explode(' ', ''));               //no parameters
        $m->execute(explode(' ', '--abc'));          //no action
        $m->execute(explode(' ', 'does_not_exist')); //invalid action
    }
    function testHelp() {
        $action =& new MockCLI_Action();
        $action->setReturnValue('getName', 'action_1');
        $action->setReturnValue('getDescription', 'Description of the 1° action');
        $action->expectNever('execute');
        
        $other_action =& new MockCLI_Action();
        $other_action->setReturnValue('getName', 'action_2');
        $other_action->setReturnValue('getDescription', 'Description of the 2° action');
        $other_action->expectNever('execute');
        
        
        $m =& new CLI_Module('name', 'description');
        $m->addAction($action);
        $m->addAction($other_action);
        
        $expected_help = <<<EOS
name:
description

Available actions:
  * action_1
    Description of the 1° action
  * action_2
    Description of the 2° action


EOS;
        $this->assertEqual($m->help(), $expected_help);
    }
}
?>
