<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactMappedFieldValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ArtifactMappedFieldValueRetriever $retriever;
    private MappedFieldRetriever&MockObject $mapped_field_retriever;
    private \Planning_Milestone&MockObject $milestone;
    private \Tuleap\Tracker\Artifact\Artifact&MockObject $artifact;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->mapped_field_retriever = $this->createMock(MappedFieldRetriever::class);
        $this->retriever              = new ArtifactMappedFieldValueRetriever($this->mapped_field_retriever);
        $release_tracker              = TrackerTestBuilder::aTracker()->build();
        $release_artifact             = ArtifactTestBuilder::anArtifact(1)->inTracker($release_tracker)->build();

        $this->milestone = $this->createMock(\Planning_Milestone::class);
        $this->milestone->method('getArtifact')->willReturn($release_artifact);
        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->user     = UserTestBuilder::aUser()->build();
    }

    public function testReturnsNullWhenNoMappedField(): void
    {
        $tracker = $this->createMock(\Tracker::class);
        $this->artifact->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);
        $this->mapped_field_retriever->method('getField')
            ->with(self::callback(
                function (TaskboardTracker $taskboard_tracker) use ($tracker) {
                    return $taskboard_tracker->getTracker() === $tracker;
                }
            ))
            ->willReturn(null);

        self::assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenUserCantReadMappedField(): void
    {
        $mapped_field = $this->mockField();
        $mapped_field->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(false);

        self::assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenNoLastChangeset(): void
    {
        $this->mockFieldUserCanRead();
        $this->artifact->expects(self::once())
            ->method('getLastChangeset')
            ->willReturn(null);

        self::assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenValueIsNotListValue(): void
    {
        $mapped_field   = $this->mockFieldUserCanRead();
        $last_changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->artifact->expects(self::once())
            ->method('getLastChangeset')
            ->willReturn($last_changeset);
        $last_changeset->expects(self::once())
            ->method('getValue')
            ->with($mapped_field)
            ->willReturn(null);

        self::assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsNullWhenValueIsEmpty(): void
    {
        $mapped_field   = $this->mockFieldUserCanRead();
        $last_changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->artifact->expects(self::once())
            ->method('getLastChangeset')
            ->willReturn($last_changeset);
        $changeset_value = new \Tracker_Artifact_ChangesetValue_List(8608, $last_changeset, $mapped_field, false, []);
        $last_changeset->expects(self::once())
            ->method('getValue')
            ->willReturn($changeset_value);

        self::assertNull($this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user));
    }

    public function testReturnsFirstValueOfMappedField(): void
    {
        $mapped_field   = $this->mockFieldUserCanRead();
        $last_changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->artifact->expects(self::once())
            ->method('getLastChangeset')
            ->willReturn($last_changeset);
        $first_list_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(9074, 'On Going', '', 10, false);
        $second_list_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(9086, 'Blocked', '', 12, false);
        $changeset_value   = new \Tracker_Artifact_ChangesetValue_List(
            8608,
            $last_changeset,
            $mapped_field,
            false,
            [$first_list_value, $second_list_value]
        );
        $last_changeset->expects(self::once())
            ->method('getValue')
            ->willReturn($changeset_value);

        self::assertSame(
            $first_list_value,
            $this->retriever->getValueAtLastChangeset($this->milestone, $this->artifact, $this->user)
        );
    }

    private function mockFieldUserCanRead(): Tracker_FormElement_Field_Selectbox&MockObject
    {
        $mapped_field = $this->mockField();
        $mapped_field->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        return $mapped_field;
    }

    private function mockField(): Tracker_FormElement_Field_Selectbox&MockObject
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->artifact->expects(self::once())->method('getTracker')->willReturn($tracker);

        $mapped_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->mapped_field_retriever->expects(self::once())
            ->method('getField')
            ->with(self::callback(
                function (TaskboardTracker $taskboard_tracker) use ($tracker): bool {
                    return $taskboard_tracker->getTracker() === $tracker;
                }
            ))
            ->willReturn($mapped_field);

        return $mapped_field;
    }
}
