<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingBindValueByDuckTypingStub;

final class MovableStaticListFieldsCheckerTest extends TestCase
{
    private Tracker_FormElement_Field_List & Stub $source_field;
    private Tracker_FormElement_Field_List & Stub $target_field;

    protected function setUp(): void
    {
        $this->source_field = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->target_field = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->artifact     = ArtifactTestBuilder::anArtifact(101)->build();
    }

    public function testReturnsFalseWhenCheckIsDoneOnANonStaticBind(): void
    {
        $checker = new MovableStaticListFieldsChecker(
            RetrieveMatchingBindValueByDuckTypingStub::withoutMatchingBindValue()
        );

        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        self::assertFalse(
            $checker->checkStaticFieldCanBeMoved(
                $this->source_field,
                $this->target_field,
                $this->artifact
            )
        );
    }

    public function testReturnsFalseWhenThereIsNoMatchingValueInDestinationTracker(): void
    {
        $checker = new MovableStaticListFieldsChecker(
            RetrieveMatchingBindValueByDuckTypingStub::withoutMatchingBindValue()
        );

        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        self::assertFalse(
            $checker->checkStaticFieldCanBeMoved(
                $this->source_field,
                $this->target_field,
                $this->artifact
            )
        );
    }

    public function testReturnsTrueWhenBindIsStaticAndThereIsAMatchingValueInDestinationTracker(): void
    {
        $bind_value_in_destination_tracker = $this->createStub(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $checker = new MovableStaticListFieldsChecker(
            RetrieveMatchingBindValueByDuckTypingStub::withMatchingBindValue(
                $bind_value_in_destination_tracker
            )
        );

        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        self::assertTrue(
            $checker->checkStaticFieldCanBeMoved(
                $this->source_field,
                $this->target_field,
                $this->artifact
            )
        );
    }
}
