<?php

require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/builders/all.php');
require_once(dirname(__FILE__).'/../include/Tracker/Rule/Tracker_RulesManager.class.php');
Mock::generatePartial('Tracker_RulesManager', 'Tracker_RulesManagerTestVersion', array('_getTracker_RuleFactory', '_getSelectedValuesForField'));

require_once(dirname(__FILE__).'/../include/Tracker/Rule/List.class.php');
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
class Tracker_RulesManagerTest extends TuleapTestCase {

    function testForbidden() {
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');

        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

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
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

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

        $trm = partial_mock('Tracker_RulesManager', array('getAllListRulesByTrackerWithOrder'), array($tracker, $form_element_factory));
        $trm->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2));

        $trm->exportToXML($root, $array_xml_mapping);
        $this->assertEqual(count($xml->dependencies->rule), count($root->dependencies->rule));
    }








}

class Tracker_RulesManagerValidationTest extends Tracker_RulesManagerTest {
    
    public function testValidateReturnsFalseWhenTheDataIsInvalid() {

        $value_field_list = array(
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            );

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(10);

        $form_element_factory = mock('Tracker_FormElementFactory');
        $source_field         = mock('Tracker_FormElement_Field_List_OpenValue');
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
        $source_field         = mock('Tracker_FormElement_Field_List_OpenValue');
        stub($source_field)->getLabel()->returns('aaaaa');
        stub($form_element_factory)->getFormElementById()->returns($source_field);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    ),
                array($tracker, $form_element_factory));

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
                    ),
                array($tracker, $form_element_factory));

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
        $source_field         = mock('Tracker_FormElement_Field_List_OpenValue');
        stub($source_field)->getLabel()->returns('aaaaa');
        stub($form_element_factory)->getFormElementById()->returns($source_field);

        $tracker_rules_manager = partial_mock('Tracker_RulesManager',
                array(
                    'getAllListRulesByTrackerWithOrder',
                    'getAllDateRulesByTrackerId',
                    ),
                array($tracker, $form_element_factory));

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
                    ),
                array($tracker, $form_element_factory));

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
                    ),
                array($tracker, $form_element_factory)
                );

        $tracker_rules_manager->setReturnValue('getAllDateRulesByTrackerId',array());
        $tracker_rules_manager->setReturnValue(
                'getAllListRulesByTrackerWithOrder',
                array($rule1, $rule2)
                );

        $this->assertTrue($tracker_rules_manager->validate($tracker->getId(), $value_field_list));
    }
}

?>
