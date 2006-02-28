<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('common/tracker/ArtifactRulesManager.class');
require_once('common/tracker/ArtifactRulesManager.class');
Mock::generatePartial('ArtifactRulesManager', 'ArtifactRulesManagerTestVersion', array('_getArtifactRuleFactory'));

require_once('common/tracker/ArtifactRuleValue.class');
Mock::generate('ArtifactRuleValue');

require_once('common/tracker/ArtifactRuleFactory.class');
Mock::generate('ArtifactRuleFactory');

//We cannot mock ArtifactField ($Language is undefined)
//require_once('common/tracker/ArtifactFieldFactory.class');
class ArtifactFieldFactory {
    function getFieldFromName() {}
}
Mock::generate('ArtifactFieldFactory');

//We cannot mock ArtifactField ($Language is undefined)
//require_once('common/tracker/ArtifactField.class');
class ArtifactField {
    function getID() {}
}
Mock::generate('ArtifactField');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ArtifactRulesManagerTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ArtifactRulesManager
 */
class ArtifactRulesManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRulesManagerTest($name = 'ArtifactRulesManager test') {
        $this->UnitTestCase($name);
    }

    function testValidate() {
        /*
        Fields:
        F1(A1, A2)
        F2(B1, B2, B3)
        F3(C1, C2)
        F4(D1, D2)
        
        Rules:
        F1(A1) => F2(B1, B3) The resource A1 can be used in rooms B1 and B3
        F1(A2) => F2(B2, B3) The resource A2 can be used in rooms B2 and B3
        F3(C1) => F2(B1, B3) The person C1 can access to rooms B1 and B3
        F3(C2) => F2(B2)     The person C2 can access to room B2 only
        
        Scenarios:
        S1 => A2, B3, C1, D1 should be valid (C1 can use A2 in B3)
        S2 => A2, B3, C2, D1 should not be valid (C2 cannot access to B3)
        */
        $r1 =& new ArtifactRuleValue(1, 1, 'F1', 'A1', 'F2', 'B1');
        $r2 =& new ArtifactRuleValue(2, 1, 'F1', 'A1', 'F2', 'B3');
        $r3 =& new ArtifactRuleValue(3, 1, 'F1', 'A2', 'F2', 'B2');
        $r4 =& new ArtifactRuleValue(4, 1, 'F1', 'A2', 'F2', 'B3');
        $r5 =& new ArtifactRuleValue(5, 1, 'F3', 'C1', 'F2', 'B1');
        $r6 =& new ArtifactRuleValue(6, 1, 'F3', 'C1', 'F2', 'B3');
        $r7 =& new ArtifactRuleValue(7, 1, 'F3', 'C2', 'F2', 'B2');
        
        
        $arf =& new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array(&$r1, &$r2, &$r3, &$r4, &$r5, &$r6, &$r7));
        
        $f1 =& new MockArtifactField($this);
        $f1->setReturnValue('getID', 'F1');
        $f2 =& new MockArtifactField($this);
        $f2->setReturnValue('getID', 'F2');
        $f3 =& new MockArtifactField($this);
        $f3->setReturnValue('getID', 'F3');
        $f4 =& new MockArtifactField($this);
        $f4->setReturnValue('getID', 'F4');
        
        $aff =& new MockArtifactFieldFactory($this);
        $aff->setReturnReference('getFieldFromName', $f1, array('f_1'));
        $aff->setReturnReference('getFieldFromName', $f2, array('f_2'));
        $aff->setReturnReference('getFieldFromName', $f3, array('f_3'));
        $aff->setReturnReference('getFieldFromName', $f4, array('f_4'));
        
        $arm =& new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);
        
        //S1
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'f_1' => 'A2',
                    'f_2' => 'B3',
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        //S2
        $this->assertFalse(
            $arm->validate(
                1, 
                array(
                    'f_1' => 'A2',
                    'f_2' => 'B3',
                    'f_3' => 'C2', //C2 cannot access to B3 !
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new ArtifactRulesManagerTest();
    $test->run(new CodexReporter());
 }
?>
