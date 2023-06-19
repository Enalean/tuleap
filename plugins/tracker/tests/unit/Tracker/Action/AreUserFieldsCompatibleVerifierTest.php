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

namespace Tuleap\Document\Tests\Action;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\Tracker\Action\AreUserFieldsCompatibleVerifier;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class AreUserFieldsCompatibleVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_FormElement_Field_List & Stub $source_field;
    private Tracker_FormElement_Field_List & Stub $target_field;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->source_field = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->target_field = $this->createStub(Tracker_FormElement_Field_List::class);

        $this->source_field->method("isMultiple")->willReturn(false);
        $this->target_field->method("isMultiple")->willReturn(false);

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();
    }

    public function testItReturnsFalseWhenTheLastChangesetIsNotAUserListValueChangeset(): void
    {
        $verifier = new AreUserFieldsCompatibleVerifier();

        $this->source_field->method("getLastChangesetValue")->willReturn(null);

        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        self::assertFalse(
            $verifier->areUserFieldsCompatible($this->source_field, $this->target_field, $this->artifact)
        );
    }

    public function testItReturnsFalseWhenTheSourceAndDestinationFieldsDoNotHaveTheSameMultiplicity(): void
    {
        $verifier = new AreUserFieldsCompatibleVerifier();

        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $source_single   = $this->createStub(Tracker_FormElement_Field_List::class);
        $source_multiple = $this->createStub(Tracker_FormElement_Field_List::class);

        $target_single   = $this->createStub(Tracker_FormElement_Field_List::class);
        $target_multiple = $this->createStub(Tracker_FormElement_Field_List::class);

        $source_single->method("isMultiple")->willReturn(false);
        $source_single->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        $source_multiple->method("isMultiple")->willReturn(true);
        $source_multiple->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        $target_single->method("isMultiple")->willReturn(false);
        $target_multiple->method("isMultiple")->willReturn(true);

        self::assertFalse($verifier->areUserFieldsCompatible($source_single, $target_multiple, $this->artifact));
        self::assertFalse($verifier->areUserFieldsCompatible($source_multiple, $target_single, $this->artifact));
        self::assertTrue($verifier->areUserFieldsCompatible($source_single, $target_single, $this->artifact));
        self::assertTrue($verifier->areUserFieldsCompatible($source_multiple, $target_multiple, $this->artifact));
    }

    public function testReturnsFalseWhenThereIsNoMatchingValueInDestinationTracker(): void
    {
        $verifier = new AreUserFieldsCompatibleVerifier();

        $last_changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([]);

        $this->source_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        self::assertFalse(
            $verifier->areUserFieldsCompatible(
                $this->source_field,
                $this->target_field,
                $this->artifact
            )
        );
    }

    public function testReturnsTrueWhenBindIsStaticAndThereIsAMatchingValueInDestinationTracker(): void
    {
        $verifier = new AreUserFieldsCompatibleVerifier();

        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);

        self::assertTrue(
            $verifier->areUserFieldsCompatible(
                $this->source_field,
                $this->target_field,
                $this->artifact
            )
        );
    }
}
