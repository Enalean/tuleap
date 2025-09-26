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

namespace Tuleap\Tracker\Rule;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElementFactory;
use Tracker_Rule_List;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BindValueIdCollectionStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FirstValidValueAccordingToDependenciesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_VALUE_ID  = 101;
    private const SECOND_VALUE_ID = 102;
    private const THIRD_VALUE_ID  = 103;
    private const FOURTH_VALUE_ID = 104;

    private \Tracker_FormElementFactory|Stub $form_element_factory;
    private Stub|Artifact $artifact;
    private Tracker_Rule_List $rule_1;
    private Tracker_Rule_List $rule_2;
    private Tracker_Rule_List $rule_3;
    private Tracker_Rule_List $rule_4;
    private SelectboxField|Stub $field_changed;
    private SelectboxField|Stub $field_not_changed_1;
    private SelectboxField|Stub $field_not_changed_2;
    private array $rules;
    private FirstValidValueAccordingToDependenciesRetriever $first_valid_value_according_to_dependencies_retriever;
    private BindValueIdCollectionStub $value_collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->form_element_factory = $this->createStub(Tracker_FormElementFactory::class);

        $tracker        = TrackerTestBuilder::aTracker()->withId(112)->build();
        $this->artifact = $this->createStub(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($tracker);

        $this->setUpFields();
        $this->value_collection = BindValueIdCollectionStub::withValues(self::FIRST_VALUE_ID, self::SECOND_VALUE_ID, self::THIRD_VALUE_ID);
        $this->setUpRules($tracker);

        $this->form_element_factory->method('getFieldById')->willReturnCallback(
            fn (int $artifact_id): \Tuleap\Tracker\FormElement\Field\TrackerField => match ($artifact_id) {
                202 => $this->field_not_changed_1,
                203 => $this->field_not_changed_2,
            }
        );

        $changeset_value_field_not_changed_1 = $this->createStub(Tracker_Artifact_ChangesetValue::class);
        $changeset_value_field_not_changed_2 = $this->createStub(Tracker_Artifact_ChangesetValue::class);

        $this->artifact->method('getValue')->willReturnCallback(
            fn (\Tuleap\Tracker\FormElement\Field\TrackerField $field): Tracker_Artifact_ChangesetValue => match ($field) {
                $this->field_not_changed_1 => $changeset_value_field_not_changed_1,
                $this->field_not_changed_2 => $changeset_value_field_not_changed_2,
            }
        );

        $changeset_value_field_not_changed_1->method('getValue')->willReturn([127]);
        $changeset_value_field_not_changed_2->method('getValue')->willReturn([109]);

        $this->first_valid_value_according_to_dependencies_retriever = new FirstValidValueAccordingToDependenciesRetriever($this->form_element_factory);
        $this->rules                                                 = [$this->rule_1, $this->rule_2, $this->rule_3, $this->rule_4];
    }

    public function testItReturnFirstValueWithValidTransitionIfTheirIsNoDependencyRuleForTracker(): void
    {
        self::assertSame(
            self::FIRST_VALUE_ID,
            $this->first_valid_value_according_to_dependencies_retriever->getFirstValidValuesAccordingToDependencies(
                $this->value_collection,
                $this->field_changed,
                $this->artifact,
                []
            )
        );
    }

    public function testItReturnFirstValueWithValidTransitionAndDependencyRuleForTracker(): void
    {
        self::assertSame(
            self::THIRD_VALUE_ID,
            $this->first_valid_value_according_to_dependencies_retriever->getFirstValidValuesAccordingToDependencies(
                $this->value_collection,
                $this->field_changed,
                $this->artifact,
                $this->rules
            )
        );
    }

    public function testItReturnFirstNullIfNoValidValue(): void
    {
        $invalid_values = BindValueIdCollectionStub::withValues(self::FOURTH_VALUE_ID, self::SECOND_VALUE_ID, 105);

        $this->assertNull(
            $this->first_valid_value_according_to_dependencies_retriever->getFirstValidValuesAccordingToDependencies(
                $invalid_values,
                $this->field_changed,
                $this->artifact,
                $this->rules
            )
        );
    }

    private function setUpFields(): void
    {
        $this->field_changed       = $this->createStub(SelectboxField::class);
        $this->field_not_changed_1 = $this->createStub(SelectboxField::class);
        $this->field_not_changed_2 = $this->createStub(SelectboxField::class);

        $this->field_changed->method('getId')->willReturn(201);
    }

    private function setUpRules(\Tuleap\Tracker\Tracker $tracker): void
    {
        $this->rule_1 = new Tracker_Rule_List(12, $tracker->getId(), 201, self::THIRD_VALUE_ID, 202, 127);
        $this->rule_2 = new Tracker_Rule_List(12, $tracker->getId(), 201, 114, 202, 128);
        $this->rule_3 = new Tracker_Rule_List(12, $tracker->getId(), 203, 109, 201, self::THIRD_VALUE_ID);
        $this->rule_4 = new Tracker_Rule_List(12, $tracker->getId(), 203, 110, 201, self::FOURTH_VALUE_ID);
    }
}
