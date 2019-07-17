<?php
/**
 * Copyright (c) Enalean, 2011-2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Tracker\Rule\TrackerRulesDateValidator;
use Tuleap\Tracker\Rule\TrackerRulesListValidator;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

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

//phpcs:ignorefile

class Tracker_RulesManagerTest extends TuleapTestCase {
    function testForbidden() {
        $r1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $r2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $r3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');

        $arf = new MockTracker_RuleFactory($this);
        $arf->setReturnValue('getAllListRulesByTrackerWithOrder', array($r1, $r2, $r3));

        $frozen_dao = Mockery::mock(FrozenFieldsDao::class);
        $frozen_dao->shouldReceive('isFieldUsedInPostAction')->with('A')->andReturn(false);
        $frozen_dao->shouldReceive('isFieldUsedInPostAction')->with('B')->andReturn(false);
        $frozen_dao->shouldReceive('isFieldUsedInPostAction')->with('C')->andReturn(false);
        $frozen_dao->shouldReceive('isFieldUsedInPostAction')->with('D')->andReturn(false);
        $frozen_dao->shouldReceive('isFieldUsedInPostAction')->with('E')->andReturn(false);

        $tracker_rules_date_validator = Mockery::mock(TrackerRulesDateValidator::class);
        $tracker_rules_date_validator->shouldReceive('validateDateRules')->andReturn(true);

        $tracker_rules_list_validator = Mockery::mock(TrackerRulesListValidator::class);
        $tracker_rules_list_validator->shouldReceive('validateListRules')->andReturn(true);

        $tracker_factory = Mockery::mock(TrackerFactory::class);

        $arm = partial_mock(
            'Tracker_RulesManager',
            array('getRuleFactory', 'getSelectedValuesForField'),
            [Mockery::mock(Tracker::class), Mockery::mock(
                Tracker_FormElementFactory::class),
                $frozen_dao,
                $tracker_rules_date_validator,
                $tracker_rules_list_validator,
                $tracker_factory
            ]
        );
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
        $frozen_dao           = Mockery::mock(FrozenFieldsDao::class);
        $tracker_rules_date_validator = Mockery::mock(TrackerRulesDateValidator::class);
        $tracker_rules_list_validator = Mockery::mock(TrackerRulesListValidator::class);
        $tracker_factory              = Mockery::mock(TrackerFactory::class);

        $manager              = new Tracker_RulesManager($tracker, $form_element_factory, $frozen_dao, $tracker_rules_date_validator, $tracker_rules_list_validator, $tracker_factory);

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
        $tracker_rules_date_validator = Mockery::mock(TrackerRulesDateValidator::class);
        $tracker_rules_list_validator = Mockery::mock(TrackerRulesListValidator::class);
        $tracker_factory  = Mockery::mock(TrackerFactory::class);
        $rule_date_factory = mock('Tracker_Rule_Date_Factory');
        stub($rule_date_factory)->searchByTrackerId($this->tracker_id)->returns($rules_date);

        $element_factory    = mock('Tracker_FormElementFactory');
        $frozen_dao          = Mockery::mock(FrozenFieldsDao::class);
        $this->rules_manager = partial_mock(
            'Tracker_RulesManager',
            ['getRuleFactory'],
            [$tracker, $element_factory, $frozen_dao, $tracker_rules_date_validator, $tracker_rules_list_validator, $tracker_factory]
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
