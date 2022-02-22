<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tracker_Rule_List;
use Tracker_RulesManager;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BindValueIdCollectionStub;
use Workflow;

final class FirstPossibleValueInListRetrieverTest extends TestCase
{
    private const FIRST_VALUE_ID                  = 101;
    private const SECOND_VALUE_ID                 = 102;
    private const THIRD_VALUE_ID                  = 103;
    private const FOURTH_VALUE_ID                 = 104;
    private const ORIGINAL_FIELD_CHANGED_VALUE_ID = 108;

    private \Tracker_FormElementFactory|Stub $form_element_factory;
    private Stub|Artifact $artifact;
    private FirstPossibleValueInListRetriever $first_possible_value_retriever;
    private Stub|Workflow $workflow;
    private Stub|Tracker_RulesManager $tracker_rules_manager;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_1;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_2;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $value_from_artifact;
    private Tracker_Rule_List $rule_1;
    private Tracker_Rule_List $rule_2;
    private Tracker_Rule_List $rule_3;
    private Tracker_Rule_List $rule_4;
    private Tracker_FormElement_Field_Selectbox|Stub $field_changed;
    private Tracker_FormElement_Field_Selectbox|Stub $field_not_changed_1;
    private Tracker_FormElement_Field_Selectbox|Stub $field_not_changed_2;
    private BindValueIdCollectionStub $values_collection;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_3;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_4;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createStub(Tracker_FormElementFactory::class);
        $this->field_changed        = $this->createStub(Tracker_FormElement_Field_Selectbox::class);
        $this->field_not_changed_1  = $this->createStub(Tracker_FormElement_Field_Selectbox::class);
        $this->field_not_changed_2  = $this->createStub(Tracker_FormElement_Field_Selectbox::class);

        $this->field_changed->method('getId')->willReturn(201);

        $tracker        = TrackerTestBuilder::aTracker()->withId(112)->build();
        $this->artifact = $this->createStub(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($tracker);

        $this->tracker_rules_manager = $this->createStub(Tracker_RulesManager::class);

        $this->workflow = $this->createStub(Workflow::class);

        $this->first_possible_value_retriever = new FirstPossibleValueInListRetriever(
            new FirstValidValueAccordingToDependenciesRetriever($this->form_element_factory)
        );

        $this->values_collection = BindValueIdCollectionStub::withValues(
            self::FIRST_VALUE_ID,
            self::SECOND_VALUE_ID,
            self::THIRD_VALUE_ID
        );

        $this->setUpTestValues();
        $this->setUpRules($tracker);


        $changeset_value_field_changed       = $this->createStub(Tracker_Artifact_ChangesetValue::class);
        $changeset_value_field_not_changed_1 = $this->createStub(Tracker_Artifact_ChangesetValue::class);
        $changeset_value_field_not_changed_2 = $this->createStub(Tracker_Artifact_ChangesetValue::class);

        $this->artifact->method('getValue')->withConsecutive(
            [$this->field_changed],
            [$this->field_not_changed_2],
            [$this->field_not_changed_2]
        )->willReturnOnConsecutiveCalls(
            $changeset_value_field_changed,
            $changeset_value_field_not_changed_1,
            $changeset_value_field_not_changed_2
        );

        $changeset_value_field_changed->method('getValue')->willReturn([self::ORIGINAL_FIELD_CHANGED_VALUE_ID]);
        $changeset_value_field_not_changed_1->method('getValue')->willReturn([127]);
        $changeset_value_field_not_changed_2->method('getValue')->willReturn([109]);
    }

    public function testItReturnFirstValueWhenNoWorkflow(): void
    {
        $this->artifact->method('getWorkflow')->willReturn(null);
        $this->assertSame(
            self::FIRST_VALUE_ID,
            $this->first_possible_value_retriever->getFirstPossibleValue(
                $this->artifact,
                $this->field_changed,
                BindValueIdCollectionStub::withValues(self::FIRST_VALUE_ID, self::SECOND_VALUE_ID)
            )
        );
    }

    public function testItReturnFirstValueWithValidTransitionIfTheirIsNoDependencyRuleForTracker(): void
    {
        $changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue::class);
        $this->artifact->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('isUsed')->willReturn(true);

        $this->artifact->method('getValue')->with($this->field_changed)->willReturn($changeset_value);
        $changeset_value->method('getValue')->willReturn([self::ORIGINAL_FIELD_CHANGED_VALUE_ID]);

        $this->field_changed->method('getListValueById')->withConsecutive(
            [self::ORIGINAL_FIELD_CHANGED_VALUE_ID],
            [self::FIRST_VALUE_ID],
            [self::SECOND_VALUE_ID],
            [self::THIRD_VALUE_ID]
        )->willReturnOnConsecutiveCalls(
            $this->value_from_artifact,
            $this->test_value_1,
            $this->test_value_2,
            $this->test_value_3
        );

        $this->workflow->method('isTransitionExist')->withConsecutive(
            [$this->value_from_artifact, $this->test_value_1],
            [$this->value_from_artifact, $this->test_value_2],
            [$this->value_from_artifact, $this->test_value_3]
        )->willReturnOnConsecutiveCalls(false, true, true);

        $this->workflow->method('getGlobalRulesManager')->willReturn($this->tracker_rules_manager);
        $this->tracker_rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn([]);

        $this->assertSame(
            self::SECOND_VALUE_ID,
            $this->first_possible_value_retriever->getFirstPossibleValue(
                $this->artifact,
                $this->field_changed,
                $this->values_collection
            )
        );
    }

    public function testItReturnFirstValueWithValidTransitionAndDependencyRuleForTracker(): void
    {
        $this->artifact->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('isUsed')->willReturn(true);

        $this->field_changed->method('getListValueById')->withConsecutive(
            [self::ORIGINAL_FIELD_CHANGED_VALUE_ID],
            [self::FIRST_VALUE_ID],
            [self::SECOND_VALUE_ID],
            [self::THIRD_VALUE_ID]
        )->willReturnOnConsecutiveCalls(
            $this->value_from_artifact,
            $this->test_value_1,
            $this->test_value_2,
            $this->test_value_3
        );

        $this->workflow->method('isTransitionExist')->withConsecutive(
            [$this->value_from_artifact, $this->test_value_1],
            [$this->value_from_artifact, $this->test_value_2],
            [$this->value_from_artifact, $this->test_value_3]
        )->willReturnOnConsecutiveCalls(false, true, true);

        $this->workflow->method('getGlobalRulesManager')->willReturn($this->tracker_rules_manager);
        $this->tracker_rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn(
            [$this->rule_1, $this->rule_2, $this->rule_3, $this->rule_4]
        );

        $this->form_element_factory->method('getFieldById')->withConsecutive([202], [203])->willReturnOnConsecutiveCalls($this->field_not_changed_1, $this->field_not_changed_2);

        $changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue::class);

        $this->artifact->method('getValue')->with($this->field_changed)->willReturn($changeset_value);
        $changeset_value->method('getValue')->willReturn([self::ORIGINAL_FIELD_CHANGED_VALUE_ID]);

        $this->assertSame(
            self::THIRD_VALUE_ID,
            $this->first_possible_value_retriever->getFirstPossibleValue(
                $this->artifact,
                $this->field_changed,
                $this->values_collection
            )
        );
    }

    public function testItThrowExceptionWhenNoValidValue(): void
    {
        $this->artifact->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('isUsed')->willReturn(true);

        $this->field_changed->method('getListValueById')->withConsecutive(
            [self::ORIGINAL_FIELD_CHANGED_VALUE_ID],
            [self::FIRST_VALUE_ID],
            [self::SECOND_VALUE_ID],
            [self::FOURTH_VALUE_ID]
        )->willReturnOnConsecutiveCalls(
            $this->value_from_artifact,
            $this->test_value_1,
            $this->test_value_2,
            $this->test_value_4
        );

        $this->workflow->method('isTransitionExist')->withConsecutive(
            [$this->value_from_artifact, $this->test_value_1],
            [$this->value_from_artifact, $this->test_value_2],
            [$this->value_from_artifact, $this->test_value_4]
        )->willReturnOnConsecutiveCalls(false, true, true);

        $this->workflow->method('getGlobalRulesManager')->willReturn($this->tracker_rules_manager);
        $this->tracker_rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn(
            [$this->rule_1, $this->rule_2, $this->rule_3, $this->rule_4]
        );

        $this->form_element_factory->method('getFieldById')->withConsecutive([202], [203])->willReturnOnConsecutiveCalls($this->field_not_changed_1, $this->field_not_changed_2);

        $changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue::class);

        $this->artifact->method('getValue')->with($this->field_changed)->willReturn($changeset_value);
        $changeset_value->method('getValue')->willReturn([self::ORIGINAL_FIELD_CHANGED_VALUE_ID]);

        $this->expectException(NoPossibleValueException::class);

        $this->first_possible_value_retriever->getFirstPossibleValue(
            $this->artifact,
            $this->field_changed,
            BindValueIdCollectionStub::withValues(self::FIRST_VALUE_ID, self::SECOND_VALUE_ID, self::FOURTH_VALUE_ID)
        );
    }

    private function setUpTestValues(): void
    {
        $this->test_value_1        = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::FIRST_VALUE_ID,
            "value test 1",
            'description',
            12,
            0
        );
        $this->test_value_2        = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::SECOND_VALUE_ID,
            "value test 2",
            'description',
            12,
            0
        );
        $this->test_value_3        = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::THIRD_VALUE_ID,
            "value test 3",
            'description',
            12,
            0
        );
        $this->test_value_4        = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::FOURTH_VALUE_ID,
            "value test 4",
            'description',
            12,
            0
        );
        $this->value_from_artifact = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::ORIGINAL_FIELD_CHANGED_VALUE_ID,
            "value from artifact",
            'description',
            12,
            0
        );
    }

    private function setUpRules(\Tracker $tracker): void
    {
        $this->rule_1 = new Tracker_Rule_List(12, $tracker->getId(), 201, self::THIRD_VALUE_ID, 202, 127);
        $this->rule_2 = new Tracker_Rule_List(12, $tracker->getId(), 201, 114, 202, 128);
        $this->rule_3 = new Tracker_Rule_List(12, $tracker->getId(), 203, 109, 201, self::THIRD_VALUE_ID);
        $this->rule_4 = new Tracker_Rule_List(12, $tracker->getId(), 203, 110, 201, self::FOURTH_VALUE_ID);
    }
}
