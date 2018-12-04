<?php
require_once('bootstrap.php');
Mock::generatePartial('Tracker_RulesManager', 'Tracker_RulesManagerTestVersion', array('getRuleFactory', 'getSelectedValuesForField'));

Mock::generate('Tracker_Rule_List');

Mock::generate('Tracker_RuleFactory');

Mock::generate('Tracker_FormElementFactory');

require_once('common/include/Response.class.php');
Mock::generate('Response');

Mock::generate('Tracker_FormElement_Field_Selectbox');

Mock::generate('Tracker_FormElement_Field_List_Bind_Static');

Mock::generate('Tracker');

Mock::generate('Tracker_FormElement_Field_List');

Mock::generate('Tracker_FormElement_Field_List_BindFactory');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class Tracker_RulesManager
 */
class Tracker_RulesManager_legacyTest extends TuleapTestCase {

    function setUp() {
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

        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3, $r4, $r5, $r6, $r7));

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

        $rule_date_factory = mock('Tracker_Rule_Date_Factory');
        stub($rule_date_factory)->searchByTrackerId()->returns(array());

        $this->arm = new Tracker_RulesManagerTestVersion($this);
        $this->arm->setTrackerFormElementFactory($aff);
        $this->arm->setRuleDateFactory($rule_date_factory);
        $this->arm->setReturnReference('getRuleFactory', $arf);
        $this->arm->setReturnValue('getSelectedValuesForField', array('a_1'), array($f1, 'A1'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('a_2'), array($f1, 'A2'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_1'), array($f2, 'B1'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_2'), array($f2, 'B2'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_3'), array($f2, 'B3'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('c_1'), array($f3, 'C1'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('c_2'), array($f3, 'C2'));
        $this->arm->setReturnValue('getSelectedValuesForField', array('a_1'), array($f1, array('A1')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('a_2'), array($f1, array('A2')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_1'), array($f2, array('B1')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_2'), array($f2, array('B2')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_3'), array($f2, array('B3')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('c_1'), array($f3, array('C1')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('c_2'), array($f3, array('C2')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('a_1', 'a_2'), array($f1, array('A1', 'A2')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_1', 'b_3'), array($f2, array('B1', 'B3')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_1', 'b_2'), array($f2, array('B1', 'B2')));
        $this->arm->setReturnValue('getSelectedValuesForField', array('b_2', 'b_3'), array($f2, array('B2', 'B3')));
    }

    public function testS1() {
        //S1
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $this->arm->validate(
                1,
                array(
                    'F1' => 'A2',
                    'F2' => 'B3',
                    'F3' => 'C1',
                    'F4' => 'D1'
                )
            )
        );
        //$this->assertEqual($GLOBALS['feedback'], '');
    }

    public function testS2() {
        //S2
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', 'f_2(b_3) -> f_3(c_2)'));
        $this->assertFalse(
            $this->arm->validate(
                1,
                array(
                    'F1' => 'A2',
                    'F2' => 'B3',
                    'F3' => 'C2', //C2 cannot access to B3 !
                    'F4' => 'D1'
                )
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  'f_3(c_2) -> f_2(b_3) : ');
    }

    public function testS3() {
        //S3
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $this->arm->validate(
                1,
                array(
                    'F1' => array('A1', 'A2'),
                    'F2' => 'B3',
                    'F3' => 'C1',
                    'F4' => 'D1'
                )
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
    }

    public function testS4() {
        //S4
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $this->arm->validate(
                1,
                array(
                    'F1' => array('A1', 'A2'),
                    'F2' => 'B2',
                    'F3' => 'C2',
                    'F4' => 'D1'
                )
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
    }

    public function testS5() {
        //S5
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertTrue(
            $this->arm->validate(
                1,
                array(
                    'F1' => 'A1',
                    'F2' => array('B1', 'B3'),
                    'F3' => 'C1',
                    'F4' => 'D1'
                )
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  '');
    }

    public function testS6() {
        //S6
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', 'f_1(a_1) -> f_2(b_2)'));
        $this->assertFalse(
            $this->arm->validate(
                1,
                array(
                    'F1' => 'A1',
                    'F2' => array('B1', 'B2'), //A1 cannot access to B2 !
                    'F3' => 'C1',
                    'F4' => 'D1'
                )
            )
        );
        //$this->assertEqual($GLOBALS['feedback'],  'f_1(a_1) -> f_2(b_2)');
    }
}

class Tracker_RulesManagerTest extends TuleapTestCase {
    function testForbidden() {
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');

        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('getRuleFactory', $arf);

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('getRuleFactory', $arf);

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('getRuleFactory', $arf);

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('getRuleFactory', $arf);

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

        $arm = new Tracker_RulesManagerTestVersion($this);
        $arm->setReturnReference('getRuleFactory', $arf);

        //value has source or target
        $this->assertTrue($arm->valueHasSource(1, 'B', 2, 'A'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 2, 'C'));
        $this->assertFalse($arm->valueHasSource(1, 'B', 3, 'C'));
        $this->assertTrue($arm->valueHasTarget(1, 'B', 3, 'C'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 3, 'A'));
        $this->assertFalse($arm->valueHasTarget(1, 'B', 2, 'A'));

    }

    function testExportToXmlCallsRuleListFactoryExport() {
        $xml_data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tracker />
XML;
        $sax_object           = new SimpleXMLElement($xml_data);
        $xmlMapping           = array();
        $tracker              = mock('Tracker');
        $form_element_factory = mock('Tracker_FormElementFactory');
        $manager              = new Tracker_RulesManager($tracker, $form_element_factory);

        stub($tracker)->getId()->returns(45);

        $date_factory = mock('Tracker_Rule_Date_Factory');
        stub($date_factory)->exportToXml($sax_object, $xmlMapping, 45)->once();

        $list_factory = mock('Tracker_Rule_List_Factory');

        stub($list_factory)->exportToXml($sax_object, $xmlMapping, $form_element_factory, 45)->once();

        $manager->setRuleDateFactory($date_factory);
        $manager->setRuleListFactory($list_factory);

        $manager->exportToXml($sax_object, $xmlMapping);
    }
}

class Tracker_RulesManagerValidationTest extends Tracker_RulesManagerTest {

    public function testValidateReturnsFalseWhenTheDateDataIsInvalid() {

        $value_field_list = array(
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            );

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');
        $source_field         = mock('Tracker_FormElement_Field_Date');
        stub($source_field)->getLabel()->returns('aaaaa');
        stub($form_element_factory)->getFormElementById()->returns($source_field);

        $tracker_rule_date  = mock('Tracker_Rule_Date');
        $tracker_rule_date2 = mock('Tracker_Rule_Date');

        stub($tracker_rule_date)->validate()->returns(true);
        stub($tracker_rule_date)->getSourceFieldId()->returns(10);
        stub($tracker_rule_date)->getTargetFieldId()->returns(11);

        stub($tracker_rule_date2)->validate()->returns(false);
        stub($tracker_rule_date2)->getSourceFieldId()->returns(12);
        stub($tracker_rule_date2)->getTargetFieldId()->returns(13);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    ),
                array($tracker, $form_element_factory));

        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',
                array($tracker_rule_date, $tracker_rule_date2));


        $tracker_rules_manager->setTrackerFormElementFactory($form_element_factory);


        $this->assertFalse($tracker_rules_manager->validate($tracker->getId(), $value_field_list));

    }

    public function testValidateReturnsTrueWhenThereAreValidDateRules() {

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $tracker_rule_date  = mock('Tracker_Rule_Date');
        $tracker_rule_date2 = mock('Tracker_Rule_Date');

        stub($tracker_rule_date)->validate()->returns(true);
        stub($tracker_rule_date)->getSourceFieldId()->returns(10);
        stub($tracker_rule_date)->getTargetFieldId()->returns(11);

        stub($tracker_rule_date2)->validate()->returns(true);
        stub($tracker_rule_date2)->getSourceFieldId()->returns(12);
        stub($tracker_rule_date2)->getTargetFieldId()->returns(13);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager', array('getAllListRulesByTrackerWithOrder', 'getAllDateRulesByTrackerId'), array($tracker, $form_element_factory));
        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array($tracker_rule_date, $tracker_rule_date2));

        $value_field_list = array(
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            );

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));

    }

    public function testValidateReturnsTrueWhenThereAreNoRules() {

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $tracker_rules_manager = partial_mock('Tracker_RulesManager', array('getAllListRulesByTrackerWithOrder', 'getAllDateRulesByTrackerId'), array($tracker, $form_element_factory));
        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());

        $value_field_list = array();

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));

    }

    public function testValidateListRulesReturnsTrueWhenSourceValueDoesNotMatchRuleSourceValue() {
        $value_field_list = array(
            123     => 456,
            789     => 101,
        );

        $rule = new Tracker_Rule_List();
        $rule->setSourceValue('not equal to 456')
                ->setTargetValue(101)
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(10)
                ->setId(5);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    ),
                array($tracker, $form_element_factory));

        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array($rule));

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }

    public function testValidateListRulesReturnsFalseWhenTargetValueDoesNotMatchRuleTargetValue() {
        $value_field_list = array(
            123     => 456,
            789     => 101,
        );

        $rule = new Tracker_Rule_List();
        $rule->setSourceValue(456)
                ->setTargetValue('not 101')
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(10)
                ->setId(5);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $source_field = mock('Tracker_FormElement_Field_Selectbox');
        $target_field = mock('Tracker_FormElement_Field_Selectbox');

        stub($source_field)->getLabel()->returns('aaaaa');
        stub($source_field)->getID()->returns(123);
        stub($target_field)->getLabel()->returns('bbbbb');
        stub($target_field)->getID()->returns(789);

        stub($form_element_factory)->getFormElementById(123)->returns($source_field);
        stub($form_element_factory)->getFormElementById(789)->returns($target_field);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    'getSelectedValuesForField',
                    ),
                array($tracker, $form_element_factory));

        stub($tracker_rules_manager)->getSelectedValuesForField()->returns(array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array($rule));
        $tracker_rules_manager->setTrackerFormElementFactory($form_element_factory);

        $this->assertFalse($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }

    public function testValidateListRulesReturnsTrueWhenAllValuesMatchRuleValues() {
        $value_field_list = array(
            123     => 456,
            789     => 586,
        );

        $rule = new Tracker_Rule_List();
        $rule->setSourceValue(456)
                ->setTargetValue(586)
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(10)
                ->setId(5);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    'getSelectedValuesForField',
                    ),
                array($tracker, $form_element_factory));

        stub($tracker_rules_manager)->getSelectedValuesForField()->returns(array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array($rule));

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }

    public function testValidateListRulesReturnsFalseWhenTrackerValueDoesNotMatchRuleValue() {
        $value_field_list = array(
            123     => 456,
            789     => 586,
        );

        $rule = new Tracker_Rule_List();
        $rule->setSourceValue(456)
                ->setTargetValue(586)
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(19)
                ->setId(5);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $source_field = mock('Tracker_FormElement_Field_Selectbox');
        $target_field = mock('Tracker_FormElement_Field_Selectbox');

        stub($source_field)->getLabel()->returns('aaaaa');
        stub($source_field)->getID()->returns(123);
        stub($target_field)->getLabel()->returns('bbbbb');
        stub($target_field)->getID()->returns(789);

        stub($form_element_factory)->getFormElementById(123)->returns($source_field);
        stub($form_element_factory)->getFormElementById(789)->returns($target_field);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    'getSelectedValuesForField',
                    ),
                array($tracker, $form_element_factory));

        stub($tracker_rules_manager)->getSelectedValuesForField()->returns(array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue('getAllListRulesByTrackerWithOrder',array($rule));
        $tracker_rules_manager->setTrackerFormElementFactory($form_element_factory);

        $this->assertFalse($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }

    public function testValidateListRulesReturnsTrueOnMultipleValidData() {
        $value_field_list = array(
            123     => 456,
            789     => 586,
            45      => 6
        );

        $rule1 = new Tracker_Rule_List();
        $rule1->setSourceValue(456)
                ->setTargetValue(586)
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(19)
                ->setId(5);

        $rule2 = new Tracker_Rule_List();
        $rule2->setSourceValue(6)
                ->setTargetValue(456)
                ->setSourceFieldId(45)
                ->setTargetFieldId(123)
                ->setTrackerId(19)
                ->setId(98);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(19);

        $form_element_factory = mock('Tracker_FormElementFactory');

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    'getSelectedValuesForField',
                    ),
                array($tracker, $form_element_factory));

        stub($tracker_rules_manager)->getSelectedValuesForField()->returns(array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue(
                'getAllListRulesByTrackerWithOrder',
                array($rule1, $rule2)
                );

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }

    public function testValidateListRulesReturnsTrueWhenOneRuleOnlyIsValidForAGivenSetting() {
        $value_field_list = array(
            123     => 456,
            789     => 586,
        );

        $rule1 = new Tracker_Rule_List();
        $rule1->setSourceValue(456)
                ->setTargetValue(586)
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(19)
                ->setId(5);

        //same rule with different target value
        $rule2 = new Tracker_Rule_List();
        $rule2->setSourceValue(456)
                ->setTargetValue(54654)
                ->setSourceFieldId(123)
                ->setTargetFieldId(789)
                ->setTrackerId(19)
                ->setId(11);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(19);

        $form_element_factory = mock('Tracker_FormElementFactory');
        $source_field         = mock('Tracker_FormElement_Field_List_OpenValue');
        stub($source_field)->getLabel()->returns('aaaaa');
        stub($form_element_factory)->getFormElementById()->returns($source_field);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    'getSelectedValuesForField',
                    ),
                array($tracker, $form_element_factory)
                );
        stub($tracker_rules_manager)->getSelectedValuesForField()->returns(array());
        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue(
                'getAllListRulesByTrackerWithOrder',
                array($rule1, $rule2)
                );

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }
}

class Tracker_RulesManager_isUsedInFieldDependencyTest extends TuleapTestCase {

    private $tracker_id = 123;

    private $source_field_list_id = 12;
    private $source_field_list;

    private $target_field_list_id = 13;
    private $target_field_list;

    private $a_field_not_used_in_rules_id = 14;
    private $a_field_not_used_in_rules;

    private $source_field_date_id = 15;
    private $source_field_date;

    private $target_field_date_id = 16;
    private $target_field_date;

    private function setUpRuleList() {
        $rule = new Tracker_Rule_List();
        $rule->setTrackerId($this->tracker_id)
            ->setSourceFieldId($this->source_field_list_id)
            ->setTargetFieldId($this->target_field_list_id)
            ->setSourceValue('A')
            ->setTargetValue('B');
        return $rule;
    }
    private function setUpRuleDate() {
        $rule = new Tracker_Rule_Date();
        $rule->setTrackerId($this->tracker_id)
            ->setSourceFieldId($this->source_field_date_id)
            ->setTargetFieldId($this->target_field_date_id)
            ->setComparator('<');
        return $rule;
    }

    public function setUp() {
        parent::setUp();

        $tracker = stub('Tracker')->getId()->returns($this->tracker_id);

        $this->a_field_not_used_in_rules = stub('Tracker_FormElement_Field_Selectbox')->getId()->returns($this->a_field_not_used_in_rules_id);
        $this->source_field_list = stub('Tracker_FormElement_Field_Selectbox')->getId()->returns($this->source_field_list_id);
        $this->target_field_list = stub('Tracker_FormElement_Field_Selectbox')->getId()->returns($this->target_field_list_id);
        $this->source_field_date = stub('Tracker_FormElement_Field_Date')->getId()->returns($this->source_field_date_id);
        $this->target_field_date = stub('Tracker_FormElement_Field_Date')->getId()->returns($this->target_field_date_id);

        $rules_list = array(
            $this->setUpRuleList()
        );
        $rule_list_factory = mock('Tracker_RuleFactory');
        stub($rule_list_factory)->getAllListRulesByTrackerWithOrder($this->tracker_id)->returns($rules_list);

        $rules_date = array(
            $this->setUpRuleDate()
        );
        $rule_date_factory = mock('Tracker_Rule_Date_Factory');
        stub($rule_date_factory)->searchByTrackerId($this->tracker_id)->returns($rules_date);

        $element_factory   = mock('Tracker_FormElementFactory');
        $this->rules_manager = partial_mock(
            'Tracker_RulesManager',
            array('getRuleFactory'),
            array($tracker, $element_factory)
        );
        stub($this->rules_manager)->getRuleFactory()->returns($rule_list_factory);
        $this->rules_manager->setRuleDateFactory($rule_date_factory);
    }

    public function itReturnsTrueIfTheFieldIsUsedInARuleList() {
        $this->assertTrue($this->rules_manager->isUsedInFieldDependency($this->source_field_list));
    }

    public function itReturnsTrueIfTheFieldIsUsedInARuleDate() {
        $this->assertTrue($this->rules_manager->isUsedInFieldDependency($this->source_field_date));
    }

    public function itReturnsFalseIfTheFieldIsNotUsedInARule() {
        $this->assertFalse($this->rules_manager->isUsedInFieldDependency($this->a_field_not_used_in_rules));
    }
}
