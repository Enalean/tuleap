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

namespace Tuleap\Tracker\Workflow;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Stub\BindValueIdCollectionStub;
use Workflow;

final class ValidValuesAccordingToTransitionsRetrieverTest extends TestCase
{
    private const FIRST_VALUE_ID                  = 101;
    private const SECOND_VALUE_ID                 = 102;
    private const THIRD_VALUE_ID                  = 103;
    private const ORIGINAL_FIELD_CHANGED_VALUE_ID = 108;

    private Tracker_FormElement_Field_Selectbox|Stub $field_changed;
    private Artifact|Stub $artifact;
    private Stub|Workflow $workflow;
    private BindValueIdCollectionStub $values_collection;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_1;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_2;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $test_value_3;
    private \Tracker_FormElement_Field_List_Bind_StaticValue $value_from_artifact;

    protected function setUp(): void
    {
        $this->field_changed = $this->createStub(Tracker_FormElement_Field_Selectbox::class);
        $this->field_changed->method('getId')->willReturn(201);

        $this->artifact = $this->createStub(Artifact::class);
        $this->workflow = $this->createStub(Workflow::class);

        $this->values_collection = BindValueIdCollectionStub::withValues(
            self::FIRST_VALUE_ID,
            self::SECOND_VALUE_ID,
            self::THIRD_VALUE_ID
        );

        $changeset_value_field_changed = $this->createStub(Tracker_Artifact_ChangesetValue::class);
        $changeset_value_field_changed->method('getValue')->willReturn([self::ORIGINAL_FIELD_CHANGED_VALUE_ID]);

        $this->artifact->method('getValue')->willReturn($changeset_value_field_changed);

        $this->setUpTestValues();
    }

    public function testItDoesNothingWhenNoValue(): void
    {
        $expected_result = BindValueIdCollectionStub::withValues(
            self::FIRST_VALUE_ID,
            self::SECOND_VALUE_ID,
            self::THIRD_VALUE_ID
        );

        $this->workflow->method('isUsed')->willReturn(true);
        $this->field_changed->expects(self::exactly(1))->method('getListValueById')->willReturn(null);
        $this->getValidValues();
        $this->assertEquals($expected_result, $this->values_collection);

        $ids = $this->values_collection->getValueIds();

        $this->assertContains(self::FIRST_VALUE_ID, $ids);
        $this->assertContains(self::SECOND_VALUE_ID, $ids);
        $this->assertContains(self::THIRD_VALUE_ID, $ids);
    }

    public function testItDoesNothingWhenNoTransitions(): void
    {
        $expected_result = BindValueIdCollectionStub::withValues(
            self::FIRST_VALUE_ID,
            self::SECOND_VALUE_ID,
            self::THIRD_VALUE_ID
        );

        $this->workflow->method('isUsed')->willReturn(false);
        $this->field_changed->expects(self::exactly(1))->method('getListValueById')->willReturn(
            $this->value_from_artifact
        );

        $this->getValidValues();
        $this->assertEquals($expected_result, $this->values_collection);

        $ids = $this->values_collection->getValueIds();

        $this->assertContains(self::FIRST_VALUE_ID, $ids);
        $this->assertContains(self::SECOND_VALUE_ID, $ids);
        $this->assertContains(self::THIRD_VALUE_ID, $ids);
    }

    public function testItRemoveInvalidValueWhenTransitionsExist(): void
    {
        $expected_result = BindValueIdCollectionStub::withValues(
            self::FIRST_VALUE_ID,
            self::SECOND_VALUE_ID,
            self::THIRD_VALUE_ID
        );
        $expected_result->removeValue(self::SECOND_VALUE_ID);

        $this->field_changed->expects(self::exactly(4))->method('getListValueById')->withConsecutive(
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

        $this->workflow->method('isUsed')->willReturn(true);
        $this->workflow->method('isTransitionExist')->withConsecutive(
            [$this->value_from_artifact, $this->test_value_1],
            [$this->value_from_artifact, $this->test_value_2],
            [$this->value_from_artifact, $this->test_value_3]
        )->willReturnOnConsecutiveCalls(
            true,
            false,
            true
        );

        $this->getValidValues();

        $this->assertEquals($expected_result, $this->values_collection);

        $ids = $this->values_collection->getValueIds();

        $this->assertContains(self::FIRST_VALUE_ID, $ids);
        $this->assertNotContains(self::SECOND_VALUE_ID, $ids);
        $this->assertContains(self::THIRD_VALUE_ID, $ids);
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
        $this->value_from_artifact = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::ORIGINAL_FIELD_CHANGED_VALUE_ID,
            "value from artifact",
            'description',
            12,
            0
        );
    }

    private function getValidValues(): void
    {
        ValidValuesAccordingToTransitionsRetriever::getValidValuesAccordingToTransitions(
            $this->artifact,
            $this->field_changed,
            $this->values_collection,
            $this->workflow
        );
    }
}
