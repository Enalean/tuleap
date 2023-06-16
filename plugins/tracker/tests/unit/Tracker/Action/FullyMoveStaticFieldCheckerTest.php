<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingBindValueByDuckTypingStub;

final class FullyMoveStaticFieldCheckerTest extends TestCase
{
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\Tracker_FormElement_Field_List|(\Tracker_FormElement_Field_List&\PHPUnit\Framework\MockObject\Stub)
     */
    private \PHPUnit\Framework\MockObject\Stub|\Tracker_FormElement_Field_List $target_list_field;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\Tracker_FormElement_Field_List|(\Tracker_FormElement_Field_List&\PHPUnit\Framework\MockObject\Stub)
     */
    private \PHPUnit\Framework\MockObject\Stub|\Tracker_FormElement_Field_List $source_list_field;

    protected function setUp(): void
    {
        $this->source_list_field = $this->createStub(\Tracker_FormElement_Field_List::class);

        $this->target_list_field = $this->createStub(\Tracker_FormElement_Field_List::class);

        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
    }

    public function testFieldCanBeFullyMovedWhenValueIsFoundInDestinationTracker(): void
    {
        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_list_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);
        $checker = new FullyMoveStaticFieldChecker(RetrieveMatchingBindValueByDuckTypingStub::withoutMatchingBindValue());
        $this->assertFalse($checker->checkStaticFieldCanBeFullyMoved($this->source_list_field, $this->target_list_field, $this->artifact));
    }

    public function testFieldIsPartiallyMovedWhenValueDoesNotExistsInDestinationTracker(): void
    {
        $last_changeset_value_value = $this->createStub(Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_list_field->expects(self::once())->method("getLastChangesetValue")->with($this->artifact)->willReturn($last_changeset_value);
        $bind    = new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'my value', '', 1, false);
        $checker = new FullyMoveStaticFieldChecker(RetrieveMatchingBindValueByDuckTypingStub::withMatchingBindValue($bind));

        $this->assertTrue($checker->checkStaticFieldCanBeFullyMoved($this->source_list_field, $this->target_list_field, $this->artifact));
    }
}
