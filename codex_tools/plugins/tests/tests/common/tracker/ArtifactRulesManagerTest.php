<?php
require_once('common/tracker/ArtifactRulesManager.class.php');
Mock::generatePartial('ArtifactRulesManager', 'ArtifactRulesManagerTestVersion', array('_getArtifactRuleFactory', '_getSelectedValuesForField'));

require_once('common/tracker/ArtifactRuleValue.class.php');
Mock::generate('ArtifactRuleValue');

require_once('common/tracker/ArtifactRuleFactory.class.php');
Mock::generate('ArtifactRuleFactory');

//We cannot mock ArtifactField ($Language is undefined)
//require_once('common/tracker/ArtifactFieldFactory.class.php');
class ArtifactRulesManagerTest_ArtifactFieldFactory {
    function getFieldFromName() {}
}
Mock::generate('ArtifactRulesManagerTest_ArtifactFieldFactory','MockArtifactFieldFactory');

require_once('common/include/Response.class.php');
Mock::generate('Response');

//We cannot mock ArtifactField ($Language is undefined)
//require_once('common/tracker/ArtifactField.class.php');
class ArtifactRulesManagerTest_ArtifactField {
    function getID() {}
    function getFieldPredefinedValues() {}
    function getLabel() {}
}
Mock::generate('ArtifactRulesManagerTest_ArtifactField','MockArtifactField');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
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
        $r1 = new ArtifactRuleValue(1, 1, 'F1', 'A1', 'F2', 'B1');
        $r2 = new ArtifactRuleValue(2, 1, 'F1', 'A1', 'F2', 'B3');
        $r3 = new ArtifactRuleValue(3, 1, 'F1', 'A2', 'F2', 'B2');
        $r4 = new ArtifactRuleValue(4, 1, 'F1', 'A2', 'F2', 'B3');
        $r5 = new ArtifactRuleValue(5, 1, 'F3', 'C1', 'F2', 'B1');
        $r6 = new ArtifactRuleValue(6, 1, 'F3', 'C1', 'F2', 'B3');
        $r7 = new ArtifactRuleValue(7, 1, 'F3', 'C2', 'F2', 'B2');
        
        
        $arf = new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array(&$r1, &$r2, &$r3, &$r4, &$r5, &$r6, &$r7));
        
        $f1 = new MockArtifactField($this);
        $f1->setReturnValue('getID', 'F1');
        $f1->setReturnValue('getLabel', 'f_1');
        $f1->setReturnValue('getFieldPredefinedValues', null);

        $f2 = new MockArtifactField($this);
        $f2->setReturnValue('getID', 'F2');
        $f2->setReturnValue('getLabel', 'f_2');
        $f2->setReturnValue('getFieldPredefinedValues', null);
        
        $f3 = new MockArtifactField($this);
        $f3->setReturnValue('getID', 'F3');
        $f3->setReturnValue('getLabel', 'f_3');
        $f3->setReturnValue('getFieldPredefinedValues', null);
        
        $f4 = new MockArtifactField($this);
        $f4->setReturnValue('getID', 'F4');
        $f4->setReturnValue('getLabel', 'f_4');
        $f4->setReturnValue('getFieldPredefinedValues', null);
        
        $aff = new MockArtifactFieldFactory($this);
        $aff->setReturnReference('getFieldFromName', $f1, array('f_1'));
        $aff->setReturnReference('getFieldFromName', $f2, array('f_2'));
        $aff->setReturnReference('getFieldFromName', $f3, array('f_3'));
        $aff->setReturnReference('getFieldFromName', $f4, array('f_4'));
        
        $arm = new ArtifactRulesManagerTestVersion($this);
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
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
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
        //$this->assertEqual($GLOBALS['feedback'], '');
        /**/
        //S2
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', 'f_3(c_2) -> f_2(b_3)'));
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
        //$this->assertEqual($GLOBALS['feedback'],  'f_3(c_2) -> f_2(b_3) : ');
        /**/
        //S3
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
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
        //$this->assertEqual($GLOBALS['feedback'],  '');
        /**/
        //S4
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
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
        //$this->assertEqual($GLOBALS['feedback'],  '');
        /**/
        //S5
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
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
        //$this->assertEqual($GLOBALS['feedback'],  '');
        /**/
        //S6
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', 'f_1(a_1) -> f_2(b_2)'));
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
        //$this->assertEqual($GLOBALS['feedback'],  'f_1(a_1) -> f_2(b_2)');
        /**/
    }
    
    function testForbidden() {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array($r1, $r2, $r3));
        
        $arm = new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);
        
        //Forbidden sources
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'A', 'A'), "Field A cannot be the source of field A");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'B', 'A'), "Field B cannot be the source of field A because A->B->A is cyclic");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'A'), "Field C cannot be the source of field A because A->B->C->A is cyclic");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'D', 'A'), "Field D can be the source of field A");

        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'A', 'B'), "Field A is the source of field B");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'B', 'B'), "Field B cannot be the source of field B");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'B'), "Field C cannot be the source of field B because B is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'B'), "Field D cannot be the source of field B because B is already a target");

        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'A', 'C'), "Field A cannot be the source of field C because C is already a target");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'B', 'C'), "Field B is the source of field C");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'C', 'C'), "Field C cannot be the source of field C");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'C'), "Field D cannot be the source of field C because C is already a target");

        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'A', 'D'), "Field A can be the source of field D");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'B', 'D'), "Field B can be the source of field D");
        $this->assertFalse($arm->fieldIsAForbiddenSource(1, 'C', 'D'), "Field C can be the source of field D");
        $this->assertTrue($arm->fieldIsAForbiddenSource(1, 'D', 'D'), "Field D cannot be the source of field D");
        
        //Forbidden targets                                                            
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'A'), "Field A cannot be the target of field A");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'B', 'A'), "Field B is the target of field A");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'A'), "Field C cannot be the target of field A because C is already a target");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'A'), "Field D can be the target of field A");

        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'B'), "Field A cannot be the target of field B because A->B->A is cyclic");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'B'), "Field B cannot be the target of field B");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'C', 'B'), "Field C is the target of field B");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'B'), "Field D can be the target of field B");

        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'A', 'C'), "Field A cannot be the target of field C because A->B->C->A is cyclic");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'C'), "Field B cannot be the target of field C because B is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'C'), "Field C cannot be the target of field C");
        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'D', 'C'), "Field D can be the target of field C");

        $this->assertFalse($arm->fieldIsAForbiddenTarget(1, 'A', 'D'), "Field A can be the target of field D");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'B', 'D'), "Field B cannot be the target of field D because B is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'C', 'D'), "Field C cannot be the target of field D because C is already a target");
        $this->assertTrue($arm->fieldIsAForbiddenTarget(1, 'D', 'D'), "Field D cannot be the target of field D");
    }
    
    function testFieldHasSourceTarget() {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array($r1, $r2, $r3));
        
        $arm = new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);

        $this->assertFalse($arm->fieldHasSource(1, 'A'));
        $this->assertTrue($arm->fieldHasSource(1, 'B'));
        $this->assertTrue($arm->fieldHasSource(1, 'C'));
        $this->assertFalse($arm->fieldHasSource(1, 'D'));
        $this->assertTrue($arm->fieldHasSource(1, 'E'));
        $this->assertFalse($arm->fieldHasSource(1, 'F'));
        
        $this->assertTrue($arm->fieldHasTarget(1, 'A'));
        $this->assertTrue($arm->fieldHasTarget(1, 'B'));
        $this->assertFalse($arm->fieldHasTarget(1, 'C'));
        $this->assertTrue($arm->fieldHasTarget(1, 'D'));
        $this->assertFalse($arm->fieldHasTarget(1, 'E'));
        $this->assertFalse($arm->fieldHasTarget(1, 'F'));
        
    }
    function testIsCyclic() {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array($r1, $r2, $r3));
        
        $arm = new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);
        
        $this->assertTrue($arm->isCyclic(1, 'A', 'A'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'C'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'A', 'E'));
        
        $this->assertTrue($arm->isCyclic(1, 'B', 'A'));
        $this->assertTrue($arm->isCyclic(1, 'B', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'B', 'C'));
        $this->assertFalse($arm->isCyclic(1, 'B', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'B', 'E'));
        
        $this->assertTrue($arm->isCyclic(1, 'C', 'A'));
        $this->assertTrue($arm->isCyclic(1, 'C', 'B'));
        $this->assertTrue($arm->isCyclic(1, 'C', 'C'));
        $this->assertFalse($arm->isCyclic(1, 'C', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'C', 'E'));
        
        $this->assertFalse($arm->isCyclic(1, 'D', 'A'));
        $this->assertFalse($arm->isCyclic(1, 'D', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'D', 'C'));
        $this->assertTrue($arm->isCyclic(1, 'D', 'D'));
        $this->assertFalse($arm->isCyclic(1, 'D', 'E'));
        
        $this->assertFalse($arm->isCyclic(1, 'E', 'A'));
        $this->assertFalse($arm->isCyclic(1, 'E', 'B'));
        $this->assertFalse($arm->isCyclic(1, 'E', 'C'));
        $this->assertTrue($arm->isCyclic(1, 'E', 'D'));
        $this->assertTrue($arm->isCyclic(1, 'E', 'E'));
    }
    
    function testRuleExists() {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array($r1, $r2, $r3));
        
        $arm = new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);
        
        //Rule exists
        $this->assertFalse($arm->ruleExists(1, 'A', 'A'));
        $this->assertTrue($arm->ruleExists(1, 'A', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'A', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'A', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'A', 'E'));
        
        $this->assertFalse($arm->ruleExists(1, 'B', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'B', 'B'));
        $this->assertTrue($arm->ruleExists(1, 'B', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'B', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'B', 'E'));
        
        $this->assertFalse($arm->ruleExists(1, 'C', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'C', 'E'));
        
        $this->assertFalse($arm->ruleExists(1, 'D', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'D', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'D', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'D', 'D'));
        $this->assertTrue($arm->ruleExists(1, 'D', 'E'));
        
        $this->assertFalse($arm->ruleExists(1, 'E', 'A'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'B'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'C'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'D'));
        $this->assertFalse($arm->ruleExists(1, 'E', 'E'));
        
    }
    function testValueHasSourceTarget() {
        $r1 = new ArtifactRuleValue(1, 1, 'A', '1', 'B', '2');
        $r2 = new ArtifactRuleValue(2, 1, 'B', '3', 'C', '4');
        $r3 = new ArtifactRuleValue(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockArtifactRuleFactory($this);
        $arf->setReturnValue('getAllRulesByArtifactTypeWithOrder', array($r1, $r2, $r3));
        
        $arm = new ArtifactRulesManagerTestVersion($this);
        $arm->setReturnReference('_getArtifactRuleFactory', $arf);

        //value has source or target
        $this->assertTrue($arm->valueHasSource(1, 'B', 2, 'A'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 2, 'C'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 3, 'C'));
        $this->assertTrue($arm->valueHasTarget(1, 'B', 3, 'C'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 3, 'A'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 2, 'A'));
        
    }
}
?>
