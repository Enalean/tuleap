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

declare(strict_types=1);

use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Workflow_Trigger_RulesManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $manager;
    protected $dao;
    protected $target_value_id;
    protected $formelement_factory;
    protected $rules_processor;
    /**
     * @var int
     */
    private $trigger_value_id_1;
    /**
     * @var int
     */
    private $trigger_value_id_2;
    /**
     * @var Tracker_Workflow_Trigger_TriggerRule
     */
    private $rule;
    /**
     * @var int
     */
    private $rule_id;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var int
     */
    private $target_field_id;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $target_field_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $target_field;
    /**
     * @var int
     */
    private $trigger_field_id_1;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $trigger_field_value_1;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $trigger_field_1;
    /**
     * @var SimpleXMLElement
     */
    private $xml;
    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field_1685;
    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field_1741;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $value_2118;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $value_2060;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $value_2061;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $value_2117;
    /**
     * @var array
     */
    private $xmlFieldMapping;

    protected function setUp(): void
    {
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

        $this->trigger_value_id_1 = 369;
        $this->trigger_value_id_2 = 258;
        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            null,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->buildSelectBoxField(12),
                $this->buildStaticValue($this->target_value_id)
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->buildSelectBoxField(23),
                    $this->buildStaticValue($this->trigger_value_id_1)
                ),
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->buildSelectBoxField(25),
                    $this->buildStaticValue($this->trigger_value_id_2)
                ),
            ]
        );
    }

    public function testItDuplicatesTriggerRulesFromOldTracker(): void
    {
        $workflow_logger = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);

        $this->manager = \Mockery::mock(
            \Tracker_Workflow_Trigger_RulesManager::class,
            [
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                $workflow_logger,
                \Mockery::spy(\Tracker_Workflow_Trigger_RulesBuilderFactory::class),
                new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $template_tracker  = Mockery::spy(Tracker::class)->shouldReceive('getId')->andReturn(101)->getMock();
        $new_field_01      = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(502)->getMock();
        $new_field_01->shouldReceive('getTracker')->andReturn($template_tracker);
        $new_field_02      = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(503)->getMock();
        $new_field_02->shouldReceive('getTracker')->andReturn($template_tracker);
        $new_field_03      = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(501)->getMock();
        $new_field_03->shouldReceive('getTracker')->andReturn($template_tracker);

        $new_field_01->shouldReceive('getAllValues')->andReturn([
                                                                    $this->buildStaticValue(601),
                                                                    $this->buildStaticValue(602)
                                                                ]);

        $new_field_02->shouldReceive('getAllValues')->andReturn([
                                                                    $this->buildStaticValue(701),
                                                                    $this->buildStaticValue(702),
                                                                    $this->buildStaticValue(703),
                                                                    $this->buildStaticValue(704)
                                                                ]);

        $new_field_03->shouldReceive('getAllValues')->andReturn([
                                                                    $this->buildStaticValue(801),
                                                                    $this->buildStaticValue(802),
                                                                ]);

        $this->formelement_factory->shouldReceive('getFieldById')->with(502)->andReturns($new_field_01);
        $this->formelement_factory->shouldReceive('getFieldById')->with(503)->andReturns($new_field_02);
        $this->formelement_factory->shouldReceive('getFieldById')->with(501)->andReturns($new_field_03);

        $trigger_01 = new Tracker_Workflow_Trigger_FieldValue(
            $this->buildSelectBoxField(102),
            $this->buildStaticValue(101)
        );

        $trigger_02 = new Tracker_Workflow_Trigger_FieldValue(
            $this->buildSelectBoxField(103),
            $this->buildStaticValue(104)
        );

        $rule_01 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->buildSelectBoxField(101),
                $this->buildStaticValue(101)
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            [
                $trigger_01
            ]
        );

        $rule_02 = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->buildSelectBoxField(101),
                $this->buildStaticValue(102)
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            [
                $trigger_02
            ]
        );

        $this->manager->shouldReceive('getForTargetTracker')->andReturns([$rule_01, $rule_02]);

        $template_trackers = [
            $template_tracker,
        ];

        $field_mapping = [
            0 => [
                'from'   => 102,
                'to'     => 502,
                'values' => [
                    101 => 601,
                    102 => 602
                ]
            ],
            1 => [
                'from'   => 103,
                'to'     => 503,
                'values' => [
                    101 => 701,
                    102 => 702,
                    103 => 703,
                    104 => 704,
                ]
            ],
            2 => [
                'from'   => 101,
                'to'     => 501,
                'values' => [
                    101 => 801,
                    102 => 802,
                ]
            ]
        ];

        $this->manager->shouldReceive('add')->times(2);

        $this->manager->duplicate($template_trackers, $field_mapping);
    }

    private function buildStaticValue(int $id): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return new Tracker_FormElement_Field_List_Bind_StaticValue($id, 'label', 'description', 0, false);
    }

    private function buildSelectBoxField(int $id): Tracker_FormElement_Field_Selectbox
    {
        return new Tracker_FormElement_Field_Selectbox($id, 1, 0, 'name', 'label', 'desc', true, 'S', false, false, 0);
    }

    public function testItAddsTargetFieldAndCondition(): void
    {
        $this->dao->shouldReceive('addTarget')->with($this->target_value_id, Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE)->once();

        $this->manager->add($this->rule);
    }

    public function testItAddsTriggeringFields(): void
    {
        $rule_id = 4587;
        $this->dao->shouldReceive('addTarget')->andReturns($rule_id);

        $this->dao->shouldReceive('addTriggeringField')->times(2);
        $this->dao->shouldReceive('addTriggeringField')->with($rule_id, $this->trigger_value_id_1);
        $this->dao->shouldReceive('addTriggeringField')->with($rule_id, $this->trigger_value_id_2);

        $this->manager->add($this->rule);
    }

    public function testItUpdateRuleWithNewId(): void
    {
        $rule_id = 4587;
        $this->dao->shouldReceive('addTarget')->andReturns($rule_id);

        $this->manager->add($this->rule);

        $this->assertEquals($rule_id, $this->rule->getId());
    }

    public function testItUsesTransactionToKeepConsistency(): void
    {
        $this->dao->shouldReceive('enableExceptionsOnError')->once();
        $this->dao->shouldReceive('startTransaction')->once();
        $this->dao->shouldReceive('commit')->once();
        $this->manager->add($this->rule);
    }

    private function setUpGetFromTrackerTests(): void
    {
        $this->rule_id = 6347;

        $this->tracker_id = 4656;
        $this->tracker = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);

        $this->target_field_id = 12;
        $this->target_field_value = $this->buildStaticValue($this->target_value_id);
        $this->target_field = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->target_field->shouldReceive('getAllValues')->andReturns([
                                                                           $this->buildStaticValue(9998),
                                                                           $this->target_field_value,
                                                                           $this->buildStaticValue(9999),
                                                                       ]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with($this->target_field_id)->andReturns($this->target_field);

        $this->trigger_field_id_1 = 369;
        $this->trigger_value_id_1 = 852;
        $this->trigger_field_value_1 = $this->buildStaticValue($this->trigger_value_id_1);
        $this->trigger_field_1 = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field_1->shouldReceive('getId')->andReturn($this->trigger_field_id_1);
        $this->trigger_field_1->shouldReceive('getAllValues')->andReturns([
                                                                              $this->trigger_field_value_1,
                                                                          ]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with($this->trigger_field_id_1)->andReturns($this->trigger_field_1);
    }

    public function testItFetchesDataFromDb(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->dao->shouldReceive('searchForTargetTracker')
            ->with($this->tracker_id)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->manager->getForTargetTracker($this->tracker);
    }

    public function testItHasNoRules(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->dao->shouldReceive('searchForTargetTracker')->andReturns(\TestHelper::emptyDar());

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertInstanceOf(\Tracker_Workflow_Trigger_TriggerRuleCollection::class, $rule_collection);
        $this->assertCount(0, $rule_collection);
    }

    private function setUpOneRule(): void
    {
        $this->dao->shouldReceive('searchForTargetTracker')->andReturns(
            \TestHelper::arrayToDar(
                [
                    'id' => $this->rule_id,
                    'field_id' => $this->target_field_id,
                    'value_id' => $this->target_value_id,
                    'rule_condition' => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE
                ]
            )
        );
    }

    public function testItHasOneElementInCollection(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->shouldReceive('searchForTriggeringFieldByRuleId')->andReturns(\TestHelper::emptyDar());

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertCount(1, $rule_collection);
    }

    public function testItBuildsTheRuleWithId(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->shouldReceive('searchForTriggeringFieldByRuleId')->andReturns(\TestHelper::emptyDar());

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEquals($this->rule_id, $rule->getId());
    }

    public function testItBuildsTheRuleTargetField(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->shouldReceive('searchForTriggeringFieldByRuleId')->andReturns(\TestHelper::emptyDar());

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEquals($this->target_field, $rule->getTarget()->getField());
        $this->assertEquals($this->target_field_value, $rule->getTarget()->getValue());
    }

    public function testItBuildsTheRuleCondition(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->shouldReceive('searchForTriggeringFieldByRuleId')->andReturns(\TestHelper::emptyDar());

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEquals(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE, $rule->getCondition());
    }

    public function testItBuildsTheRuleWithOneTriggeringField(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();

        $this->dao->shouldReceive('searchForTriggeringFieldByRuleId')->with($this->rule_id)->andReturns(
            \TestHelper::arrayToDar(
                [
                    'field_id' => $this->trigger_field_id_1,
                    'value_id' => $this->trigger_value_id_1,
                ]
            )
        );

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertCount(1, $rule->getTriggers());
    }

    public function testItBuildsTheRuleWithTheRightTriggeringField(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();

        $this->dao->shouldReceive('searchForTriggeringFieldByRuleId')->with($this->rule_id)->andReturns(
            \TestHelper::arrayToDar(
                [
                    'field_id' => $this->trigger_field_id_1,
                    'value_id' => $this->trigger_value_id_1,
                ]
            )
        );

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $trigger = current($rule->getTriggers());
        $this->assertEquals($this->trigger_field_1, $trigger->getField());
        $this->assertEquals($this->trigger_field_value_1, $trigger->getValue());
    }

    private function setUpDeleteByRuleIdTests(): void
    {
        $this->rule_id = 777;
        $this->tracker = Mockery::spy(Tracker::class);
        $this->rule    = \Mockery::spy(\Tracker_Workflow_Trigger_TriggerRule::class);
        $this->rule->shouldReceive('getId')->andReturns($this->rule_id);
        $this->rule->shouldReceive('getTargetTracker')->andReturns($this->tracker);
    }

    public function testItDeletesTheTriggeringFields(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->dao->shouldReceive('deleteTriggeringFieldsByRuleId')->with($this->rule_id)->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function testItDeletesTheTarget(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->dao->shouldReceive('deleteTargetByRuleId')->with($this->rule_id)->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function testItUsesTransactionToKeepConsistencyWhileDeleting(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->dao->shouldReceive('enableExceptionsOnError')->once();
        $this->dao->shouldReceive('startTransaction')->once();
        $this->dao->shouldReceive('commit')->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function testItRaisesAnExceptionWhenRuleTrackerDiffersFromGivenTracker(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->expectException(\Tracker_Exception::class);

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(654);

        $this->manager->delete($tracker, $this->rule);
    }

    public function testItProcessTheInvolvedTriggerRules(): void
    {
        $workflow_logger = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);
        $manager         = \Mockery::mock(
            \Tracker_Workflow_Trigger_RulesManager::class,
            [
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                $workflow_logger,
                \Mockery::spy(\Tracker_Workflow_Trigger_RulesBuilderFactory::class),
                new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $artifact  = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

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

        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(3);
        $changeset->shouldReceive('getArtifact')->andReturns($artifact);

        $this->dao->shouldReceive('searchForInvolvedRulesIdsByChangesetId')->with(3)->andReturns(\TestHelper::arrayToDar(['rule_id' => 1], ['rule_id' => 2]));
        $manager->shouldReceive('getRuleById')->with(1)->andReturns($trigger_1);
        $manager->shouldReceive('getRuleById')->with(2)->andReturns($trigger_2);

        $this->rules_processor->shouldReceive('process')->with($artifact, $trigger_1);
        $this->rules_processor->shouldReceive('process')->with($artifact, $trigger_2);
        $manager->processTriggers($changeset);
    }

    private function setUpXMLImportTests(): void
    {
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

        $this->field_1685 = $this->buildSelectBoxField(1685);
        $this->field_1741 = $this->buildSelectBoxField(1741);
        $this->value_2060 = $this->buildStaticValue(2060);
        $this->value_2061 = $this->buildStaticValue(2061);
        $this->value_2117 = $this->buildStaticValue(2117);
        $this->value_2118 = $this->buildStaticValue(2118);

        $this->xmlFieldMapping = [
            'F1685' => $this->field_1685,
            'F1741' => $this->field_1741,
            'V2060' => $this->value_2060,
            'V2061' => $this->value_2061,
            'V2117' => $this->value_2117,
            'V2118' => $this->value_2118,
        ];

        $this->manager = \Mockery::mock(\Tracker_Workflow_Trigger_RulesManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItImportRules(): void
    {
        $this->setUpXMLImportTests();
        $trigger_rule_1 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->field_1741,
                $this->value_2117
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->field_1685,
                    $this->value_2060
                )
            ]
        );

        $trigger_rule_2 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->field_1741,
                $this->value_2118
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->field_1685,
                    $this->value_2061
                )
            ]
        );

        $this->manager->shouldReceive('add')->with(Mockery::on($this->getMatcherTriggerRule($trigger_rule_1)))->once();
        $this->manager->shouldReceive('add')->with(Mockery::on($this->getMatcherTriggerRule($trigger_rule_2)))->once();

        $this->manager->createFromXML($this->xml, $this->xmlFieldMapping);
    }

    private function getMatcherTriggerRule(Tracker_Workflow_Trigger_TriggerRule $rule)
    {
        return new class ($rule)
        {
            /**
             * @var Tracker_Workflow_Trigger_TriggerRule
             */
            private $trigger_rule;

            public function __construct(Tracker_Workflow_Trigger_TriggerRule $expected)
            {
                $this->trigger_rule = $expected;
            }

            private function isConditionEqual($candidate): bool
            {
                return $candidate == $this->trigger_rule->getCondition();
            }

            private function isTargetEqual(Tracker_Workflow_Trigger_FieldValue $candidate): bool
            {
                return $this->isFieldValueEqual($this->trigger_rule->getTarget(), $candidate);
            }

            private function areTriggersEqual(array $triggers): bool
            {
                $reference_triggers = $this->trigger_rule->getTriggers();
                if (count($triggers) !== count($reference_triggers)) {
                    return false;
                }
                foreach ($triggers as $index => $trigger) {
                    if (! $this->isFieldValueEqual($reference_triggers[$index], $trigger)) {
                        return false;
                    }
                }
                return true;
            }

            private function isFieldValueEqual(Tracker_Workflow_Trigger_FieldValue $reference, Tracker_Workflow_Trigger_FieldValue $candidate): bool
            {
                return $reference->getField()->getId() == $candidate->getField()->getId() &&
                       $reference->getValue()->getId() == $candidate->getValue()->getId();
            }

            public function __invoke(Tracker_Workflow_Trigger_TriggerRule $candidate)
            {
                return $this->isConditionEqual($candidate->getCondition()) &&
                       $this->isTargetEqual($candidate->getTarget()) &&
                       $this->areTriggersEqual($candidate->getTriggers());
            }
        };
    }
}
