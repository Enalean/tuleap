<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('common/tracker/ArtifactRulesManager.class');
Mock::generatePartial('ArtifactRulesManager', 'ArtifactRulesManagerTestVersion', array('_getArtifactRuleFactory', '_getSelectedValuesForField'));

require_once('common/tracker/ArtifactRuleValue.class');
Mock::generate('ArtifactRuleValue');

require_once('common/tracker/ArtifactRuleFactory.class');
Mock::generate('ArtifactRuleFactory');

//We cannot mock ArtifactField ($Language is undefined)
//require_once('common/tracker/ArtifactFieldFactory.class');
class ArtifactRulesManagerTest_ArtifactFieldFactory {
    function getFieldFromName() {}
}
Mock::generate('ArtifactRulesManagerTest_ArtifactFieldFactory','MockArtifactFieldFactory');

//We cannot mock ArtifactField ($Language is undefined)
//require_once('common/tracker/ArtifactField.class');
class ArtifactRulesManagerTest_ArtifactField {
    function getID() {}
    function getFieldPredefinedValues() {}
    function getLabel() {}
}
Mock::generate('ArtifactRulesManagerTest_ArtifactField','MockArtifactField');

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
        
        /!\ those rules are not right since a field can be a target for only one field.
        
        Scenarios:
        S1 => A2, B3, C1, D1 should be valid (C1 can use A2 in B3)
        S2 => A2, B3, C2, D1 should *not* be valid (C2 cannot access to B3)
        S3 => (A1, A2), B3, C1, D1 should be valid
        S4 => (A1, A2), B2, C2, D1 should be valid (even if A1 cannot access to B2)
        S5 => A1, (B1, B3), C1, D1 should be valid
        S6 => A1, (B1, B2), C1, D1 should *not* be valid (A1 or C1 cannot access to B2)
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
        $f1->setReturnValue('getLabel', 'f_1');
        $f1->setReturnValue('getFieldPredefinedValues', null);

        $f2 =& new MockArtifactField($this);
        $f2->setReturnValue('getID', 'F2');
        $f2->setReturnValue('getLabel', 'f_2');
        $f2->setReturnValue('getFieldPredefinedValues', null);
        
        $f3 =& new MockArtifactField($this);
        $f3->setReturnValue('getID', 'F3');
        $f3->setReturnValue('getLabel', 'f_3');
        $f3->setReturnValue('getFieldPredefinedValues', null);
        
        $f4 =& new MockArtifactField($this);
        $f4->setReturnValue('getID', 'F4');
        $f4->setReturnValue('getLabel', 'f_4');
        $f4->setReturnValue('getFieldPredefinedValues', null);
        
        $aff =& new MockArtifactFieldFactory($this);
        $aff->setReturnReference('getFieldFromName', $f1, array('f_1'));
        $aff->setReturnReference('getFieldFromName', $f2, array('f_2'));
        $aff->setReturnReference('getFieldFromName', $f3, array('f_3'));
        $aff->setReturnReference('getFieldFromName', $f4, array('f_4'));
        
        $arm =& new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);
        $arm->setReturnValue('_getSelectedValuesForField', array('a_1'), array(null, 'F1', 'A1'));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_2'), array(null, 'F1', 'A2'));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1'), array(null, 'F2', 'B1'));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_2'), array(null, 'F2', 'B2'));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_3'), array(null, 'F2', 'B3'));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_1'), array(null, 'F3', 'C1'));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_2'), array(null, 'F3', 'C2'));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_1'), array(null, 'F1', array('A1')));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_2'), array(null, 'F1', array('A2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1'), array(null, 'F2', array('B1')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_2'), array(null, 'F2', array('B2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_3'), array(null, 'F2', array('B3')));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_1'), array(null, 'F3', array('C1')));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_2'), array(null, 'F3', array('C2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_1', 'a_2'), array(null, 'F3', array('A1', 'A2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1', 'b_3'), array(null, 'F3', array('B1', 'B3')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1', 'b_2'), array(null, 'F3', array('B1', 'B2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_2', 'b_3'), array(null, 'F3', array('B2', 'B3')));
        
        /**/
        //S1
        $GLOBALS['feedback'] = '';
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
        $this->assertEqual($GLOBALS['feedback'], '');
        /**/
        //S2
        $GLOBALS['feedback'] = '';
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
        $this->assertEqual($GLOBALS['feedback'],  'f_3(c_2) -> f_2(b_3) : ');
        /**/
        //S3
        $GLOBALS['feedback'] = '';
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'f_1' => array('A1', 'A2'),
                    'f_2' => 'B3',
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        $this->assertEqual($GLOBALS['feedback'],  '');
        /**/
        //S4
        $GLOBALS['feedback'] = '';
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'f_1' => array('A1', 'A2'),
                    'f_2' => 'B2',
                    'f_3' => 'C2', 
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        $this->assertEqual($GLOBALS['feedback'],  '');
        /**/
        //S5
        $GLOBALS['feedback'] = '';
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'f_1' => 'A1',
                    'f_2' => array('B1', 'B3'),
                    'f_3' => 'C1',
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        $this->assertEqual($GLOBALS['feedback'],  '');
        /**/
        //S6
        $GLOBALS['feedback'] = '';
        $this->assertFalse(
            $arm->validate(
                1, 
                array(
                    'f_1' => 'A1', 
                    'f_2' => array('B1', 'B2'), //A1 cannot access to B2 ! 
                    'f_3' => 'C1', 
                    'f_4' => 'D1'
                ),
                $aff
            )
        );
        $this->assertEqual($GLOBALS['feedback'],  'f_1(a_1) -> f_2(b_2) : ');
        /**/
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new ArtifactRulesManagerTest();
    $test->run(new CodexReporter());
 }
?>
