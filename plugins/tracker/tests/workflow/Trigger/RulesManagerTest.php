<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;

require_once __DIR__ . '/../../bootstrap.php';

abstract class Tracker_Workflow_Trigger_RulesManagerTest extends TuleapTestCase
{
    protected $manager;
    protected $dao;
    protected $target_value_id;
    protected $formelement_factory;
    protected $rules_processor;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $workflow_logger           = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);
        $this->target_value_id     = 789;
        $this->dao                 = \Mockery::spy(\Tracker_Workflow_Trigger_RulesDao::class);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->rules_processor     = \Mockery::spy(\Tracker_Workflow_Trigger_RulesProcessor::class);
        $this->manager             = new Tracker_Workflow_Trigger_RulesManager(
            $this->dao,
            $this->formelement_factory,
            $this->rules_processor,
            $workflow_logger,
            \Mockery::spy(\Tracker_Workflow_Trigger_RulesBuilderFactory::class),
            new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
        );
    }
}

class Tracker_Workflow_Trigger_RulesManager_duplicateTest extends Tracker_Workflow_Trigger_RulesManagerTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $workflow_logger = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);

        $this->manager = \Mockery::mock(
            \Tracker_Workflow_Trigger_RulesManager::class,
            array(
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                $workflow_logger,
                mock('Tracker_Workflow_Trigger_RulesBuilderFactory'),
                new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
            )
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function itDuplicatesTriggerRulesFromOldTracker()
    {
        $template_tracker  = mockery_stub(\Tracker::class)->getId()->returns(101);
        $new_field_01      = aMockField()->withTracker($template_tracker)->withId(502)->build();
        $new_field_02      = aMockField()->withTracker($template_tracker)->withId(503)->build();
        $new_field_03      = aMockField()->withTracker($template_tracker)->withId(501)->build();

        $new_field_01->shouldReceive('getAllValues')->andReturn(array(
            aBindStaticValue()->withId(601)->build(),
            aBindStaticValue()->withId(602)->build()
        ));

        $new_field_02->shouldReceive('getAllValues')->andReturn(array(
            aBindStaticValue()->withId(701)->build(),
            aBindStaticValue()->withId(702)->build(),
            aBindStaticValue()->withId(703)->build(),
            aBindStaticValue()->withId(704)->build()
        ));

        $new_field_03->shouldReceive('getAllValues')->andReturn(array(
            aBindStaticValue()->withId(801)->build(),
            aBindStaticValue()->withId(802)->build(),
        ));

        stub($this->formelement_factory)->getFieldById(502)->returns($new_field_01);
        stub($this->formelement_factory)->getFieldById(503)->returns($new_field_02);
        stub($this->formelement_factory)->getFieldById(501)->returns($new_field_03);

        $trigger_01 = new Tracker_Workflow_Trigger_FieldValue(
            aSelectBoxField()->withId(102)->build(),
            aBindStaticValue()->withId(101)->build()
        );

        $trigger_02 = new Tracker_Workflow_Trigger_FieldValue(
            aSelectBoxField()->withId(103)->build(),
            aBindStaticValue()->withId(104)->build()
        );

        $rule_01 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                aSelectBoxField()->withId(101)->build(),
                aBindStaticValue()->withId(101)->build()
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            array(
                $trigger_01
            )
        );

        $rule_02 = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                aSelectBoxField()->withId(101)->build(),
                aBindStaticValue()->withId(102)->build()
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            array(
                $trigger_02
            )
        );

        stub($this->manager)->getForTargetTracker()->returns(
            array($rule_01, $rule_02)
        );

        $template_trackers = array(
           $template_tracker,
        );

        $field_mapping = array(
            0 => array(
                'from'   => 102,
                'to'     => 502,
                'values' => array(
                    101 => 601,
                    102 => 602
                )
            ),
            1 => array(
                'from'   => 103,
                'to'     => 503,
                'values' => array(
                    101 => 701,
                    102 => 702,
                    103 => 703,
                    104 => 704,
                )
            ),
            2 => array(
                'from'   => 101,
                'to'     => 501,
                'values' => array(
                    101 => 801,
                    102 => 802,
                )
            )
        );

        expect($this->manager)->add()->count(2);

        $this->manager->duplicate($template_trackers, $field_mapping);
    }
}

class Tracker_Workflow_Trigger_RulesManager_addTest extends Tracker_Workflow_Trigger_RulesManagerTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->trigger_value_id_1 = 369;
        $this->trigger_value_id_2 = 258;
        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            null,
            new Tracker_Workflow_Trigger_FieldValue(
                aSelectBoxField()->withId(12)->build(),
                aBindStaticValue()->withId($this->target_value_id)->build()
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            array(
                new Tracker_Workflow_Trigger_FieldValue(
                    aSelectBoxField()->withId(23)->build(),
                    aBindStaticValue()->withId($this->trigger_value_id_1)->build()
                ),
                new Tracker_Workflow_Trigger_FieldValue(
                    aSelectBoxField()->withId(25)->build(),
                    aBindStaticValue()->withId($this->trigger_value_id_2)->build()
                ),
            )
        );
    }

    public function itAddsTargetFieldAndCondition()
    {
        expect($this->dao)->addTarget($this->target_value_id, Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE)->once();

        $this->manager->add($this->rule);
    }

    public function itAddsTriggeringFields()
    {
        $rule_id = 4587;
        stub($this->dao)->addTarget()->returns($rule_id);

        expect($this->dao)->addTriggeringField()->count(2);
        expect($this->dao)->addTriggeringField($rule_id, $this->trigger_value_id_1);
        expect($this->dao)->addTriggeringField($rule_id, $this->trigger_value_id_2);

        $this->manager->add($this->rule);
    }

    public function itUpdateRuleWithNewId()
    {
        $rule_id = 4587;
        stub($this->dao)->addTarget()->returns($rule_id);

        $this->manager->add($this->rule);

        $this->assertEqual($rule_id, $this->rule->getId());
    }

    public function itUsesTransactionToKeepConsistency()
    {
        expect($this->dao)->enableExceptionsOnError()->once();
        expect($this->dao)->startTransaction()->once();
        expect($this->dao)->commit()->once();
        $this->manager->add($this->rule);
    }
}

class Tracker_Workflow_Trigger_RulesManager_getFromTrackerTest extends Tracker_Workflow_Trigger_RulesManagerTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->rule_id = 6347;

        $this->tracker_id = 4656;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();

        $this->target_field_id = 12;
        $this->target_field_value = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->target_field = aMockField()->withTracker($this->tracker)->build();
        stub($this->target_field)->getAllValues()->returns(
            array(
                aBindStaticValue()->withId(9998)->build(),
                $this->target_field_value,
                aBindStaticValue()->withId(9999)->build(),
            )
        );
        stub($this->formelement_factory)->getUsedFormElementFieldById($this->target_field_id)->returns($this->target_field);

        $this->trigger_field_id_1 = 369;
        $this->trigger_value_id_1 = 852;
        $this->trigger_field_value_1 = aBindStaticValue()->withId($this->trigger_value_id_1)->build();
        $this->trigger_field_1 = aMockField()->withId($this->trigger_field_id_1)->build();
        stub($this->trigger_field_1)->getAllValues()->returns(
            array(
                $this->trigger_field_value_1,
            )
        );
        stub($this->formelement_factory)->getUsedFormElementFieldById($this->trigger_field_id_1)->returns($this->trigger_field_1);
    }

    public function itFetchesDataFromDb()
    {
        $this->dao->shouldReceive('searchForTargetTracker')
            ->with($this->tracker_id)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->manager->getForTargetTracker($this->tracker);
    }

    public function itHasNoRules()
    {
        stub($this->dao)->searchForTargetTracker()->returnsEmptyDar();

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertIsA($rule_collection, 'Tracker_Workflow_Trigger_TriggerRuleCollection');
        $this->assertCount($rule_collection, 0);
    }

    public function setUpOneRule()
    {
        stub($this->dao)->searchForTargetTracker()->returnsDar(array(
            'id'             => $this->rule_id,
            'field_id'       => $this->target_field_id,
            'value_id'       => $this->target_value_id,
            'rule_condition' => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE));
    }

    public function itHasOneElementInCollection()
    {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertCount($rule_collection, 1);
    }

    public function itBuildsTheRuleWithId()
    {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEqual($rule->getId(), $this->rule_id);
    }

    public function itBuildsTheRuleTargetField()
    {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEqual($rule->getTarget()->getField(), $this->target_field);
        $this->assertEqual($rule->getTarget()->getValue(), $this->target_field_value);
    }

    public function itBuildsTheRuleCondition()
    {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEqual($rule->getCondition(), Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE);
    }

    public function itBuildsTheRuleWithOneTriggeringField()
    {
        $this->setUpOneRule();

        stub($this->dao)->searchForTriggeringFieldByRuleId($this->rule_id)->returnsDar(array(
            'field_id' => $this->trigger_field_id_1,
            'value_id' => $this->trigger_value_id_1,
        ));

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertCount($rule->getTriggers(), 1);
    }

    public function itBuildsTheRuleWithTheRightTriggeringField()
    {
        $this->setUpOneRule();

        stub($this->dao)->searchForTriggeringFieldByRuleId($this->rule_id)->returnsDar(array(
            'field_id' => $this->trigger_field_id_1,
            'value_id' => $this->trigger_value_id_1,
        ));

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $trigger = current($rule->getTriggers());
        $this->assertEqual($trigger->getField(), $this->trigger_field_1);
        $this->assertEqual($trigger->getValue(), $this->trigger_field_value_1);
    }
}

class Tracker_Workflow_Trigger_RulesManager_deleteByRuleIdTest extends Tracker_Workflow_Trigger_RulesManagerTest
{

    private $tracker;
    private $rule;
    private $rule_id;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->rule_id = 777;
        $this->tracker = aTracker()->build();
        $this->rule    = \Mockery::spy(\Tracker_Workflow_Trigger_TriggerRule::class);
        stub($this->rule)->getId()->returns($this->rule_id);
        stub($this->rule)->getTargetTracker()->returns($this->tracker);
    }

    public function itDeletesTheTriggeringFields()
    {
        expect($this->dao)->deleteTriggeringFieldsByRuleId($this->rule_id)->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function itDeletesTheTarget()
    {
        expect($this->dao)->deleteTargetByRuleId($this->rule_id)->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function itUsesTransactionToKeepConsistency()
    {
        expect($this->dao)->enableExceptionsOnError()->once();
        expect($this->dao)->startTransaction()->once();
        expect($this->dao)->commit()->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function itRaisesAnExceptionWhenRuleTrackerDiffersFromGivenTracker()
    {
        $this->expectException('Tracker_Exception');

        $this->manager->delete(aTracker()->build(), $this->rule);
    }
}


class Tracker_Workflow_Trigger_RulesManager_processTriggersTest extends Tracker_Workflow_Trigger_RulesManagerTest
{

    public function itProcessTheInvolvedTriggerRules()
    {
        $workflow_logger = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);
        $manager         = \Mockery::mock(
            \Tracker_Workflow_Trigger_RulesManager::class,
            array(
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                $workflow_logger,
                mock('Tracker_Workflow_Trigger_RulesBuilderFactory'),
                new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
            )
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $artifact  = \Mockery::spy(\Tracker_Artifact::class);

        $trigger_1 = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            Mockery::spy(Tracker_Workflow_Trigger_FieldValue::class),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            []
        );

        $trigger_2 = new Tracker_Workflow_Trigger_TriggerRule(
            2,
            Mockery::spy(Tracker_Workflow_Trigger_FieldValue::class),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            []
        );

        $changeset = mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns(3);
        stub($changeset)->getArtifact()->returns($artifact);

        stub($this->dao)->searchForInvolvedRulesIdsByChangesetId(3)->returnsDar(
            array('rule_id' => 1),
            array('rule_id' => 2)
        );
        stub($manager)->getRuleById(1)->returns($trigger_1);
        stub($manager)->getRuleById(2)->returns($trigger_2);

        expect($this->rules_processor)->process($artifact, $trigger_1);
        expect($this->rules_processor)->process($artifact, $trigger_2);
        $manager->processTriggers($changeset);
    }
}


class TriggerRuleComparatorExpectaction extends SimpleExpectation
{

    /** @var Tracker_Workflow_Trigger_TriggerRule */
    private $trigger_rule;

    public function __construct(Tracker_Workflow_Trigger_TriggerRule $trigger_rule)
    {
        parent::__construct();
        $this->trigger_rule = $trigger_rule;
    }

    public function test($candidate)
    {
        if (! $candidate instanceof Tracker_Workflow_Trigger_TriggerRule) {
            throw new InvalidArgumentException('Expected ' . Tracker_Workflow_Trigger_TriggerRule::class . 'got ' . get_class($candidate));
        }
        try {
            $this->isConditionEqual($candidate->getCondition());
            $this->isTargetEqual($candidate->getTarget());
            $this->areTriggersEqual($candidate->getTriggers());
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    private function isConditionEqual($candidate)
    {
        if ($candidate == $this->trigger_rule->getCondition()) {
            return true;
        }
        throw new Exception("Condition `" . $this->trigger_rule->getCondition() . "` expected, `$candidate` given");
    }

    private function isTargetEqual(Tracker_Workflow_Trigger_FieldValue $candidate)
    {
        if ($this->isFieldValueEqual($this->trigger_rule->getTarget(), $candidate)) {
            return true;
        }
        throw new Exception("Target `" . $this->formatFieldValue($this->trigger_rule->getTarget()) . "` expected, `" . $this->formatFieldValue($candidate) . "` given");
    }

    private function formatFieldValue(Tracker_Workflow_Trigger_FieldValue $field_value)
    {
        return '(field_id :' . $field_value->getField()->getId() . ', field_value_id: ' . $field_value->getValue()->getId() . ')';
    }

    private function areTriggersEqual(array $triggers)
    {
        $reference_triggers = $this->trigger_rule->getTriggers();
        if (count($triggers) !== count($reference_triggers)) {
            throw new Exception('Triggers: ' . count($reference_triggers) . ' tiggers expected, ' . count($triggers) . ' given');
        }
        foreach ($triggers as $index => $trigger) {
            if (! $this->isFieldValueEqual($reference_triggers[$index], $trigger)) {
                throw new Exception("Trigger['" . $index . "'] `" . $this->formatFieldValue($this->trigger_rule->getTarget()) . "` expected, `" . $this->formatFieldValue($candidate) . "` given");
            }
        }
        return true;
    }

    private function isFieldValueEqual(Tracker_Workflow_Trigger_FieldValue $reference, Tracker_Workflow_Trigger_FieldValue $candidate)
    {
        return $reference->getField()->getId() == $candidate->getField()->getId() &&
               $reference->getValue()->getId() == $candidate->getValue()->getId();
    }

    public function testMessage($candidate)
    {
        if (! $candidate instanceof Tracker_Workflow_Trigger_TriggerRule) {
            throw new InvalidArgumentException('Expected ' . Tracker_Workflow_Trigger_TriggerRule::class . 'got ' . get_class($candidate));
        }
        try {
            $this->isConditionEqual($candidate->getCondition());
            $this->isTargetEqual($candidate->getTarget());
            $this->areTriggersEqual($candidate->getTriggers());
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
}

class Tracker_Workflow_Trigger_RulesManager_XMLImportTest extends Tracker_Workflow_Trigger_RulesManagerTest
{

    private $xml;
    private $xmlFieldMapping;
    private $field_1685;
    private $field_1741;
    private $value_2060;
    private $value_2061;
    private $value_2117;
    private $value_2118;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->xml = new SimpleXMLElement('
            <triggers>
                <trigger_rule>
                  <triggers>
                    <trigger>
                      <field_id REF="F1685"/>
                      <field_value_id REF="V2060"/>
                    </trigger>
                  </triggers>
                  <condition>at_least_one</condition>
                  <target>
                    <field_id REF="F1741"/>
                    <field_value_id REF="V2117"/>
                  </target>
                </trigger_rule>
                <trigger_rule>
                  <triggers>
                    <trigger>
                      <field_id REF="F1685"/>
                      <field_value_id REF="V2061"/>
                    </trigger>
                  </triggers>
                  <condition>all_of</condition>
                  <target>
                    <field_id REF="F1741"/>
                    <field_value_id REF="V2118"/>
                  </target>
                </trigger_rule>
            </triggers>');

        $this->field_1685 = mockery_stub(\Tracker_FormElement_Field_Selectbox::class)->getId()->returns(1685);
        $this->field_1741 = mockery_stub(\Tracker_FormElement_Field_Selectbox::class)->getId()->returns(1741);
        $this->value_2060 = mockery_stub(\Tracker_FormElement_Field_List_Bind_StaticValue::class)->getId()->returns(2060);
        $this->value_2061 = mockery_stub(\Tracker_FormElement_Field_List_Bind_StaticValue::class)->getId()->returns(2061);
        $this->value_2117 = mockery_stub(\Tracker_FormElement_Field_List_Bind_StaticValue::class)->getId()->returns(2117);
        $this->value_2118 = mockery_stub(\Tracker_FormElement_Field_List_Bind_StaticValue::class)->getId()->returns(2118);

        $this->xmlFieldMapping = array(
            'F1685' => $this->field_1685,
            'F1741' => $this->field_1741,
            'V2060' => $this->value_2060,
            'V2061' => $this->value_2061,
            'V2117' => $this->value_2117,
            'V2118' => $this->value_2118,
        );

        $this->manager = \Mockery::mock(\Tracker_Workflow_Trigger_RulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function itImportRules()
    {
        $trigger_rule_1 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->field_1741,
                $this->value_2117
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            array(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->field_1685,
                    $this->value_2060
                )
            )
        );

        $trigger_rule_2 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->field_1741,
                $this->value_2118
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            array(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->field_1685,
                    $this->value_2061
                )
            )
        );

        expect($this->manager)->add()->count(2);
        expect($this->manager)->add(new TriggerRuleComparatorExpectaction($trigger_rule_1));
        expect($this->manager)->add(new TriggerRuleComparatorExpectaction($trigger_rule_2));

        $this->manager->createFromXML($this->xml, $this->xmlFieldMapping);
    }
}
