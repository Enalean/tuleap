<?php

require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/builders/all.php');
require_once(dirname(__FILE__).'/../include/Tracker/Rule/Tracker_RulesManager.class.php');
Mock::generatePartial('Tracker_RulesManager', 'Tracker_RulesManagerTestVersion', array('_getTracker_RuleFactory', '_getSelectedValuesForField'));

require_once(dirname(__FILE__).'/../include/Tracker/Rule/List/List.class.php');
Mock::generate('Tracker_Rule_List');

require_once(dirname(__FILE__).'/../include/Tracker/Rule/Tracker_RuleFactory.class.php');
Mock::generate('Tracker_RuleFactory');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElementFactory.class.php');
Mock::generate('Tracker_FormElementFactory');

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_Selectbox.class.php');
Mock::generate('Tracker_FormElement_Field_Selectbox');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_List_Bind_Static.class.php');
Mock::generate('Tracker_FormElement_Field_List_Bind_Static');

require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_List_BindFactory.class.php');
Mock::generate('Tracker_FormElement_Field_List_BindFactory');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class Tracker_RulesManager
 */
class Tracker_RulesManagerTest extends UnitTestCase {

    function _testValidate() {
        
        // Fields:
        // F1(A1, A2)
        // F2(B1, B2, B3)
        // F3(C1, C2)
        // F4(D1, D2)
        // 
        // Rules:
        // F1(A1) => F2(B1, B3) The resource A1 can be used in rooms B1 and B3
        // F1(A2) => F2(B2, B3) The resource A2 can be used in rooms B2 and B3
        // F2(B1) => F3(C1) The person C1 can access to rooms B1 and B3
        // F2(B2) => F3(C2)     The person C2 can access to room B2 only
        // F2(B3) => F3(C1)     The person C1 can access to rooms B1 and B3
        // 
        // /!\ those rules are not right since a field can be a target for only one field.
        // 
        // Scenarios:
        // S1 => A2, B3, C1, D1 should be valid (C2 can use A2 in B3)
        // S2 => A2, B3, C2, D1 should *not* be valid (C2 cannot access to B3)
        // S3 => (A1, A2), B3, C1, D1 should be valid
        // S4 => (A1, A2), B2, C2, D1 should be valid (even if A1 cannot access to B2)
        // S5 => A1, (B1, B3), C1, D1 should be valid
        // S6 => A1, (B1, B2), C1, D1 should *not* be valid (A1 or C1 cannot access to B2)
        
        $r1 = new Tracker_Rule_List(1, 1, 'F1', 'A1', 'F2', 'B1');
        $r2 = new Tracker_Rule_List(2, 1, 'F1', 'A1', 'F2', 'B3');
        $r3 = new Tracker_Rule_List(3, 1, 'F1', 'A2', 'F2', 'B2');
        $r4 = new Tracker_Rule_List(4, 1, 'F1', 'A2', 'F2', 'B3');
        $r5 = new Tracker_Rule_List(5, 1, 'F2', 'B1', 'F3', 'C1');
        $r6 = new Tracker_Rule_List(6, 1, 'F2', 'B2', 'F3', 'C2');
        $r7 = new Tracker_Rule_List(7, 1, 'F2', 'B3', 'F3', 'C1');
        /*
        $id = null, $tracker_id = null, $source_field = null, $source_value = null, 
                $target_field = null, $target_value = null
        */
        
        
        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2, $r3, $r4, $r5, $r6, $r7));
        
        /*$bind = new MockTracker_FormElement_Field_List_Bind_Static($this);
        $bind->setReturnValue('formatArtifactValue', 'A1', array('A1'));
        $bind->setReturnValue('formatArtifactValue', 'A2', array('A2'));
        $bind->setReturnValue('formatArtifactValue', 'B1', array('B1'));
        $bind->setReturnValue('formatArtifactValue', 'B2', array('B2'));
        $bind->setReturnValue('formatArtifactValue', 'B3', array('B3'));
        $bind->setReturnValue('formatArtifactValue', 'C1', array('C1'));
        $bind->setReturnValue('formatArtifactValue', 'C2', array('C2'));
        $bind->setReturnValue('formatArtifactValue', 'D1', array('D1'));
        $bind->setReturnValue('formatArtifactValue', 'D2', array('D2'));*/
        
        $f1 = new MockTracker_FormElement_Field_Selectbox($this);
        $f1->setReturnReference('getBind', $bind);
        $f1->setReturnValue('getID', 'F1');
        $f1->setReturnValue('getLabel', 'f_1');
        $f1->setReturnValue('getAllValues', null);

        $f2 = new MockTracker_FormElement_Field_Selectbox($this);
        $f2->setReturnReference('getBind', $bind);
        $f2->setReturnValue('getID', 'F2');
        $f2->setReturnValue('getLabel', 'f_2');
        $f2->setReturnValue('getAllValues', null);
        
        $f3 = new MockTracker_FormElement_Field_Selectbox($this);
        $f3->setReturnReference('getBind', $bind);
        $f3->setReturnValue('getID', 'F3');
        $f3->setReturnValue('getLabel', 'f_3');
        $f3->setReturnValue('getAllValues', null);
        
        $f4 = new MockTracker_FormElement_Field_Selectbox($this);
        $f4->setReturnReference('getBind', $bind);
        $f4->setReturnValue('getID', 'F4');
        $f4->setReturnValue('getLabel', 'f_4');
        $f4->setReturnValue('getAllValues', null);
        
        $aff = new MockTracker_FormElementFactory($this);
        $aff->setReturnReference('getFormElementById', $f1, array('F1'));
        $aff->setReturnReference('getFormElementById', $f2, array('F2'));
        $aff->setReturnReference('getFormElementById', $f3, array('F3'));
        $aff->setReturnReference('getFormElementById', $f4, array('F4'));
        
        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('_getTracker_RuleFactory', $arf);
        $arm->setReturnValue('_getSelectedValuesForField', array('a_1'), array($f1, 'A1'));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_2'), array($f1, 'A2'));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1'), array($f2, 'B1'));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_2'), array($f2, 'B2'));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_3'), array($f2, 'B3'));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_1'), array($f3, 'C1'));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_2'), array($f3, 'C2'));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_1'), array($f1, array('A1')));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_2'), array($f1, array('A2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1'), array($f2, array('B1')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_2'), array($f2, array('B2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_3'), array($f2, array('B3')));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_1'), array($f3, array('C1')));
        $arm->setReturnValue('_getSelectedValuesForField', array('c_2'), array($f3, array('C2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('a_1', 'a_2'), array($f1, array('A1', 'A2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1', 'b_3'), array($f2, array('B1', 'B3')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_1', 'b_2'), array($f2, array('B1', 'B2')));
        $arm->setReturnValue('_getSelectedValuesForField', array('b_2', 'b_3'), array($f2, array('B2', 'B3')));
        
        //S1
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'F1' => 'A2',
                    'F2' => 'B3',
                    'F3' => 'C1',
                    'F4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'], '');
        
        //S2
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', 'f_2(b_3) -> f_3(c_2)'));
        $this->assertFalse(
            $arm->validate(
                1, 
                array(
                    'F1' => 'A2',
                    'F2' => 'B3',
                    'F3' => 'C2', //C2 cannot access to B3 !
                    'F4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  'f_3(c_2) -> f_2(b_3) : ');
        
        //S3
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'F1' => array('A1', 'A2'),
                    'F2' => 'B3',
                    'F3' => 'C1',
                    'F4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
        
        //S4
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'F1' => array('A1', 'A2'),
                    'F2' => 'B2',
                    'F3' => 'C2', 
                    'F4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
        
        //S5
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $arm->validate(
                1, 
                array(
                    'F1' => 'A1',
                    'F2' => array('B1', 'B3'),
                    'F3' => 'C1',
                    'F4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
        
        //S6
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', 'f_1(a_1) -> f_2(b_2)'));
        $this->assertFalse(
            $arm->validate(
                1, 
                array(
                    'F1' => 'A1', 
                    'F2' => array('B1', 'B2'), //A1 cannot access to B2 ! 
                    'F3' => 'C1', 
                    'F4' => 'D1'
                ),
                $aff
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  'f_1(a_1) -> f_2(b_2)');
    }
    
    function testForbidden() {
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2, $r3));
        
        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('_getTracker_RuleFactory', $arf);
        
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
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2, $r3));
        
        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('_getTracker_RuleFactory', $arf);

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
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2, $r3));
        
        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('_getTracker_RuleFactory', $arf);
        
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
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2, $r3));
        
        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('_getTracker_RuleFactory', $arf);
        
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
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');
        
        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2, $r3));
        
        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('_getTracker_RuleFactory', $arf);

        //value has source or target
        $this->assertTrue($arm->valueHasSource(1, 'B', 2, 'A'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 2, 'C'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 3, 'C'));
        $this->assertTrue($arm->valueHasTarget(1, 'B', 3, 'C'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 3, 'A'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 2, 'A'));
        
    }

    function testExport() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerRulesTest.xml');

        $tracker = aTracker()->withId(666)->build();

        $f1 = stub('Tracker_FormElement_Field_List')->getId()->returns(102);
        $f2 = stub('Tracker_FormElement_Field_List')->getId()->returns(103);

        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getFormElementById(102)->returns($f1);
        stub($form_element_factory)->getFormElementById(103)->returns($f2);

        $bind_f1 = mock('Tracker_FormElement_Field_List_Bind');
        $bind_f2 = mock('Tracker_FormElement_Field_List_Bind');

        stub($f1)->getBind()->returns($bind_f1);
        stub($f2)->getBind()->returns($bind_f2);

        $bf = new MockTracker_FormElement_Field_List_BindFactory($this);
        $bf->setReturnValue('getType', 'static', array($bind_f1));
        $bf->setReturnValue('getType', 'static', array($bind_f2));

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker xmlns="http://codendi.org/tracker" />');
        $array_xml_mapping = array('F25' => 102,
                                   'F28' => 103,
                                   'values' => array(
                                       'F25-V1' => 801,
                                       'F25-V2' => 802,
                                       'F25-V3' => 803,
                                       'F25-V4' => 804,
                                       'F28-V1' => 806,
                                       'F28-V2' => 807,
                                       'F28-V3' => 808,
                                       'F28-V4' => 809,
                                   ));


        $r1 = new Tracker_Rule_List(1, 101, 103, 806, 102, 803);
        $r2 = new Tracker_Rule_List(1, 101, 103, 806, 102, 804);

        $trm = partial_mock('Tracker_RulesManager', array('getAllRulesByTrackerWithOrder'), array($tracker, $form_element_factory));
        $trm->setReturnValue('getAllRulesByTrackerWithOrder', array($r1, $r2));

        $trm->exportToXML($root, $array_xml_mapping);
        $this->assertEqual(count($xml->dependencies->rule), count($root->dependencies->rule));
    }
}
?>
