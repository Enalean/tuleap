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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Trigger_RulesManagerTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var Tracker_Workflow_Trigger_RulesManager|(MockObject&Tracker_Workflow_Trigger_RulesManager)
     */
    protected $manager;
    protected Tracker_Workflow_Trigger_RulesDao&MockObject $dao;
    protected int $target_value_id;
    protected Tracker_FormElementFactory&MockObject $formelement_factory;
    protected Tracker_Workflow_Trigger_RulesProcessor&MockObject $rules_processor;
    private int $trigger_value_id_1;
    private int $trigger_value_id_2;
    private Tracker_Workflow_Trigger_TriggerRule $rule;
    private int $rule_id;
    private int $tracker_id = 4656;
    private Tracker&MockObject $tracker;
    private int $target_field_id;
    private Tracker_FormElement_Field_List_Bind_StaticValue $target_field_value;
    private Tracker_FormElement_Field_Selectbox&MockObject $target_field;
    private int $trigger_field_id_1;
    private Tracker_FormElement_Field_List_Bind_StaticValue $trigger_field_value_1;
    private Tracker_FormElement_Field_Selectbox&MockObject $trigger_field_1;
    private SimpleXMLElement $xml;
    private Tracker_FormElement_Field_Selectbox $field_1685;
    private Tracker_FormElement_Field_Selectbox $field_1741;
    private Tracker_FormElement_Field_List_Bind_StaticValue $value_2118;
    private Tracker_FormElement_Field_List_Bind_StaticValue $value_2060;
    private Tracker_FormElement_Field_List_Bind_StaticValue $value_2061;
    private Tracker_FormElement_Field_List_Bind_StaticValue $value_2117;
    private array $xmlFieldMapping;
    private Tracker_Workflow_Trigger_RulesBuilderFactory|MockObject $trigger_builder;

    protected function setUp(): void
    {
        $workflow_logger       = new WorkflowBackendLogger($this->createMock(LoggerInterface::class), LogLevel::DEBUG);
        $this->target_value_id = 789;
        $this->dao             = $this->createMock(Tracker_Workflow_Trigger_RulesDao::class);
        $this->dao->method('enableExceptionsOnError');
        $this->dao->method('startTransaction');
        $this->dao->method('commit');
        $this->dao->method('addTriggeringField');
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->rules_processor     = $this->createMock(Tracker_Workflow_Trigger_RulesProcessor::class);
        $this->trigger_builder     = $this->createMock(Tracker_Workflow_Trigger_RulesBuilderFactory::class);
        $this->manager             = new Tracker_Workflow_Trigger_RulesManager(
            $this->dao,
            $this->formelement_factory,
            $this->rules_processor,
            $workflow_logger,
            $this->trigger_builder,
            new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
        );

        $this->trigger_value_id_1 = 369;
        $this->trigger_value_id_2 = 258;
        $this->rule               = new Tracker_Workflow_Trigger_TriggerRule(
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
        $workflow_logger = new WorkflowBackendLogger($this->createMock(LoggerInterface::class), LogLevel::DEBUG);

        $this->manager = $this->getMockBuilder(Tracker_Workflow_Trigger_RulesManager::class)
            ->setConstructorArgs([
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                $workflow_logger,
                $this->createMock(Tracker_Workflow_Trigger_RulesBuilderFactory::class),
                new WorkflowRulesManagerLoopSafeGuard($workflow_logger),
            ])->onlyMethods(['getForTargetTracker', 'add'])
            ->getMock();

        $template_tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $new_field_01     = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $new_field_01->method('getId')->willReturn(502);
        $new_field_01->method('getTracker')->willReturn($template_tracker);
        $new_field_02 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $new_field_02->method('getId')->willReturn(503);
        $new_field_02->method('getTracker')->willReturn($template_tracker);
        $new_field_03 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $new_field_03->method('getId')->willReturn(501);
        $new_field_03->method('getTracker')->willReturn($template_tracker);

        $new_field_01->method('getAllValues')->willReturn([
            $this->buildStaticValue(601),
            $this->buildStaticValue(602),
        ]);

        $new_field_02->method('getAllValues')->willReturn([
            $this->buildStaticValue(701),
            $this->buildStaticValue(702),
            $this->buildStaticValue(703),
            $this->buildStaticValue(704),
        ]);

        $new_field_03->method('getAllValues')->willReturn([
            $this->buildStaticValue(801),
            $this->buildStaticValue(802),
        ]);

        $this->formelement_factory->method('getFieldById')->willReturnCallback(static fn (int $id) => match ($id) {
            $new_field_01->getId() => $new_field_01,
            $new_field_02->getId() => $new_field_02,
            $new_field_03->getId() => $new_field_03,
        });

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
                $trigger_01,
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
                $trigger_02,
            ]
        );

        $this->manager->method('getForTargetTracker')->willReturn([$rule_01, $rule_02]);

        $template_trackers = [
            $template_tracker,
        ];

        $field_mapping = [
            0 => [
                'from'   => 102,
                'to'     => 502,
                'values' => [
                    101 => 601,
                    102 => 602,
                ],
            ],
            1 => [
                'from'   => 103,
                'to'     => 503,
                'values' => [
                    101 => 701,
                    102 => 702,
                    103 => 703,
                    104 => 704,
                ],
            ],
            2 => [
                'from'   => 101,
                'to'     => 501,
                'values' => [
                    101 => 801,
                    102 => 802,
                ],
            ],
        ];

        $this->manager->expects($this->exactly(2))->method('add');

        $this->manager->duplicate($template_trackers, $field_mapping);
    }

    private function buildStaticValue(int $id): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return ListStaticValueBuilder::aStaticValue('label')->withId($id)->build();
    }

    private function buildSelectBoxField(int $id): Tracker_FormElement_Field_Selectbox
    {
        return new Tracker_FormElement_Field_Selectbox($id, 1, 0, 'name', 'label', 'desc', true, 'S', false, false, 0);
    }

    public function testItAddsTargetFieldAndCondition(): void
    {
        $this->dao->expects($this->once())->method('addTarget')->with($this->target_value_id, Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE);

        $this->manager->add($this->rule);
    }

    public function testItAddsTriggeringFields(): void
    {
        $rule_id = 4587;
        $this->dao->method('addTarget')->willReturn($rule_id);

        $this->dao->expects($this->exactly(2))
            ->method('addTriggeringField')
            ->willReturnCallback(fn (int $param_rule_id, int $param_value_id) => $param_rule_id === $this->trigger_value_id_1 || $param_rule_id === $this->trigger_value_id_2);

        $this->manager->add($this->rule);
    }

    public function testItUpdateRuleWithNewId(): void
    {
        $rule_id = 4587;
        $this->dao->method('addTarget')->willReturn($rule_id);

        $this->manager->add($this->rule);

        $this->assertEquals($rule_id, $this->rule->getId());
    }

    public function testItUsesTransactionToKeepConsistency(): void
    {
        $this->dao->expects($this->once())->method('enableExceptionsOnError');
        $this->dao->expects($this->once())->method('startTransaction');
        $this->dao->expects($this->once())->method('commit');
        $this->dao->expects($this->once())->method('addTarget');
        $this->manager->add($this->rule);
    }

    private function setUpGetFromTrackerTests(): void
    {
        $this->rule_id = 6347;

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn($this->tracker_id);

        $this->target_field_id    = 12;
        $this->target_field_value = $this->buildStaticValue($this->target_value_id);
        $this->target_field       = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->method('getId')->willReturn($this->target_field_id);
        $this->target_field->method('getTracker')->willReturn($this->tracker);
        $this->target_field->method('getAllValues')->willReturn([
            $this->buildStaticValue(9998),
            $this->target_field_value,
            $this->buildStaticValue(9999),
        ]);

        $this->trigger_field_id_1    = 369;
        $this->trigger_value_id_1    = 852;
        $this->trigger_field_value_1 = $this->buildStaticValue($this->trigger_value_id_1);
        $this->trigger_field_1       = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field_1->method('getId')->willReturn($this->trigger_field_id_1);
        $this->trigger_field_1->method('getAllValues')->willReturn([
            $this->trigger_field_value_1,
        ]);

        $this->formelement_factory->method('getUsedFormElementFieldById')->willReturnCallback(fn (int $id) => match ($id) {
            $this->target_field->getId() => $this->target_field,
            $this->trigger_field_1->getId() => $this->trigger_field_1,
        });
    }

    public function testItFetchesDataFromDb(): void
    {
        $this->setUpGetFromTrackerTests();

        $this->dao->expects($this->once())
            ->method('searchForTargetTracker')
            ->with($this->tracker_id)
            ->willReturn(new \Tuleap\FakeDataAccessResult([]));

        $this->manager->getForTargetTracker($this->tracker);
    }

    public function testItHasNoRules(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->dao->method('searchForTargetTracker')->willReturn(TestHelper::emptyDar());

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertInstanceOf(Tracker_Workflow_Trigger_TriggerRuleCollection::class, $rule_collection);
        $this->assertCount(0, $rule_collection);
    }

    private function setUpOneRule(): void
    {
        $this->dao->method('searchForTargetTracker')->willReturn(
            TestHelper::arrayToDar(
                [
                    'id' => $this->rule_id,
                    'field_id' => $this->target_field_id,
                    'value_id' => $this->target_value_id,
                    'rule_condition' => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
                ]
            )
        );
    }

    public function testItHasOneElementInCollection(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->method('searchForTriggeringFieldByRuleId')->willReturn(TestHelper::emptyDar());

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertCount(1, $rule_collection);
    }

    public function testItBuildsTheRuleWithId(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->method('searchForTriggeringFieldByRuleId')->willReturn(TestHelper::emptyDar());

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEquals($this->rule_id, $rule->getId());
    }

    public function testItBuildsTheRuleTargetField(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->method('searchForTriggeringFieldByRuleId')->willReturn(TestHelper::emptyDar());

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEquals($this->target_field, $rule->getTarget()->getField());
        $this->assertEquals($this->target_field_value, $rule->getTarget()->getValue());
    }

    public function testItBuildsTheRuleCondition(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();
        $this->dao->method('searchForTriggeringFieldByRuleId')->willReturn(TestHelper::emptyDar());

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEquals(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE, $rule->getCondition());
    }

    public function testItBuildsTheRuleWithOneTriggeringField(): void
    {
        $this->setUpGetFromTrackerTests();
        $this->setUpOneRule();

        $this->dao->method('searchForTriggeringFieldByRuleId')->with($this->rule_id)->willReturn(
            TestHelper::arrayToDar(
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

        $this->dao->method('searchForTriggeringFieldByRuleId')->with($this->rule_id)->willReturn(
            TestHelper::arrayToDar(
                [
                    'field_id' => $this->trigger_field_id_1,
                    'value_id' => $this->trigger_value_id_1,
                ]
            )
        );

        $rule    = $this->manager->getForTargetTracker($this->tracker)->current();
        $trigger = current($rule->getTriggers());
        $this->assertEquals($this->trigger_field_1, $trigger->getField());
        $this->assertEquals($this->trigger_field_value_1, $trigger->getValue());
    }

    private function setUpDeleteByRuleIdTests(): void
    {
        $this->rule_id = 777;
        $this->tracker = $this->createMock(Tracker::class);
        $this->rule    = $this->createMock(Tracker_Workflow_Trigger_TriggerRule::class);
        $this->rule->method('getId')->willReturn($this->rule_id);
        $this->rule->method('getTargetTracker')->willReturn($this->tracker);
    }

    public function testItDeletesTheTriggeringFieldsAndTheTarget(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->dao->expects($this->once())->method('deleteTriggeringFieldsByRuleId')->with($this->rule_id);
        $this->dao->expects($this->once())->method('deleteTargetByRuleId')->with($this->rule_id);

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function testItUsesTransactionToKeepConsistencyWhileDeleting(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->dao->expects($this->once())->method('enableExceptionsOnError');
        $this->dao->expects($this->once())->method('startTransaction');
        $this->dao->expects($this->once())->method('commit');
        $this->dao->method('deleteTriggeringFieldsByRuleId');
        $this->dao->method('deleteTargetByRuleId');

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function testItRaisesAnExceptionWhenRuleTrackerDiffersFromGivenTracker(): void
    {
        $this->setUpDeleteByRuleIdTests();
        $this->expectException(Tracker_Exception::class);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(654);

        $this->manager->delete($tracker, $this->rule);
    }

    public function testItProcessTheInvolvedTriggerRules(): void
    {
        $workflow_logger = new WorkflowBackendLogger(new \Psr\Log\NullLogger(), LogLevel::DEBUG);
        $manager         = $this->getMockBuilder(Tracker_Workflow_Trigger_RulesManager::class)
            ->setConstructorArgs([
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                $workflow_logger,
                $this->createMock(Tracker_Workflow_Trigger_RulesBuilderFactory::class),
                new WorkflowRulesManagerLoopSafeGuard($workflow_logger),
            ])->onlyMethods(['getRuleById'])
            ->getMock();

        $artifact = $this->createMock(Artifact::class);

        $target_1 = $this->createMock(Tracker_Workflow_Trigger_FieldValue::class);
        $target_1->method('fetchFormattedForJson');
        $trigger_1 = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            $target_1,
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            []
        );

        $target_2 = $this->createMock(Tracker_Workflow_Trigger_FieldValue::class);
        $target_2->method('fetchFormattedForJson');
        $trigger_2 = new Tracker_Workflow_Trigger_TriggerRule(
            2,
            $target_2,
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            []
        );

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getId')->willReturn(3);
        $changeset->method('getArtifact')->willReturn($artifact);

        $this->dao->method('searchForInvolvedRulesIdsByChangesetId')->with(3)->willReturn(TestHelper::arrayToDar(['rule_id' => 1], ['rule_id' => 2]));
        $manager->method('getRuleById')->willReturnCallback(static fn (int $id) => match ($id) {
            1 => $trigger_1,
            2 => $trigger_2,
        });

        $this->rules_processor->expects($this->exactly(2))
            ->method('process')
            ->willReturnCallback(
                static fn (Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule) => $rule === $trigger_1 || $rule === $trigger_2
            );

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

        $this->manager = $this->createPartialMock(Tracker_Workflow_Trigger_RulesManager::class, ['add']);
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
                ),
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
                ),
            ]
        );

        $this->manager->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(
                fn (Tracker_Workflow_Trigger_TriggerRule $rule) => $this->getMatcherTriggerRule($trigger_rule_1)($rule) || $this->getMatcherTriggerRule($trigger_rule_2)($rule)
            );

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

    public function testItReturnsFalseWhenNoTrackerIsFound(): void
    {
        $field   = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $tracker = TrackerTestBuilder::aTracker()->build();
        $field->method('getTracker')->willReturn($tracker);

        $this->trigger_builder->method('getTriggeringFieldForTracker')
            ->willReturn(new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields($tracker, new ArrayIterator([])));

        self::assertFalse($this->manager->isUsedInTrigger($field));
    }

    public function testItReturnsFalseWhenNoTriggeredFieldIsFound(): void
    {
        $field   = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $tracker = TrackerTestBuilder::aTracker()->build();
        $field->method('getTracker')->willReturn($tracker);
        $field->method('getId')->willReturn(1);

        $this->trigger_builder->method('getTriggeringFieldForTracker')
            ->willReturn(new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
                $tracker,
                new ArrayIterator(
                    [
                        new Tracker_FormElement_Field_Selectbox(
                            1,
                            1,
                            0,
                            'name',
                            'select',
                            'desc',
                            true,
                            'S',
                            false,
                            false,
                            0
                        ),
                    ]
                )
            ));

        $this->dao->method('searchTriggersByFieldId')->willReturn([['field_id' => '200'], ['field_id' => '300']]);

        self::assertFalse($this->manager->isUsedInTrigger($field));
    }

    public function testItReturnsTrueWhenFieldIsUsedInTrigger(): void
    {
        $field   = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $tracker = TrackerTestBuilder::aTracker()->build();
        $field->method('getTracker')->willReturn($tracker);
        $field->method('getId')->willReturn(1);

        $this->trigger_builder->method('getTriggeringFieldForTracker')
            ->willReturn(new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
                $tracker,
                new ArrayIterator(
                    [
                        new Tracker_FormElement_Field_Selectbox(
                            1,
                            1,
                            0,
                            'name',
                            'select',
                            'desc',
                            true,
                            'S',
                            false,
                            false,
                            0
                        ),
                    ]
                )
            ));

        $this->dao->method('searchTriggersByFieldId')->willReturn([['field_id' => '1']]);

        self::assertTrue($this->manager->isUsedInTrigger($field));
    }
}
