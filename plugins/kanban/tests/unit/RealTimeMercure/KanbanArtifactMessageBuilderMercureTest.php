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

namespace Tuleap\Kanban\RealTimeMercure;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageException;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class KanbanArtifactMessageBuilderMercureTest extends TestCase
{
    private \Tuleap\Kanban\KanbanItemDao&MockObject $kanban_item_dao;
    private Tracker_Artifact_ChangesetFactory&MockObject $changeset_factory;
    private KanbanArtifactMessageBuilderMercure $message_builder;
    private \Tracker_Semantic_Status&MockObject $tracker_semantic;
    private \Tracker_FormElement_Field_List&MockObject $status_field;
    protected function setUp(): void
    {
        parent::setUp();
        $this->kanban_item_dao   = $this->createMock(\Tuleap\Kanban\KanbanItemDao::class);
        $this->changeset_factory = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $this->message_builder   = new KanbanArtifactMessageBuilderMercure($this->kanban_item_dao, $this->changeset_factory);
        $this->tracker_semantic  = $this->createMock(\Tracker_Semantic_Status::class);
        $this->status_field      = $this->createMock(\Tracker_FormElement_Field_List::class);
        $this->status_field->method('getId')->willReturn(1);
    }

    public function testBuildArtifactUpdatednoError(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $result   = $this->message_builder->buildArtifactUpdated($artifact);
        self::assertInstanceOf(KanbanArtifactUpdatedMessageRepresentationMercure::class, $result);
    }

    public function testBuildArtifactMovedNoError(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupValues();
        $this->setupKanbanItemDao();
        $this->setupStatusField();
        $this->setupChangeSet();
        $this->setupPreviousChangeset();

        $data = $this->message_builder->buildArtifactMoved($artifact, $this->tracker_semantic);
        self::assertInstanceOf(KanbanArtifactMovedMessageRepresentationMercure::class, $data);
    }

    public function testBuildArtifactMovedNoStatusField(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupValues();
        $this->setupKanbanItemDao();
        $this->setupNullStatusField();
        $this->setupChangeSet();
        $this->setupPreviousChangeset();
        $data = $this->message_builder->buildArtifactMoved($artifact, $this->tracker_semantic);
        self::assertNull($data);
    }

    public function testBuildArtifactMovedNoChangeset(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupValues();
        $this->setupKanbanItemDao();
        $this->setupStatusField();
        $this->setupNullChangeSet();
        $this->setupPreviousChangeset();
        $this->expectException(RealTimeArtifactMessageException::class);
        $data = $this->message_builder->buildArtifactMoved($artifact, $this->tracker_semantic);
    }

    public function testBuildArtifactMovedNotChanged(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupValues();
        $this->setupKanbanItemDao();
        $this->setupStatusField();
        $this->setupChangeSetHasNotChanged();
        $this->setupPreviousChangeset();
        $this->expectException(RealTimeArtifactMessageException::class);
        $data = $this->message_builder->buildArtifactMoved($artifact, $this->tracker_semantic);
    }

    public function testBuildArtifactMovedNoPrevious(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupValues();
        $this->setupKanbanItemDao();
        $this->setupStatusField();
        $this->setupChangeSet();
        $this->setupNullPreviousChangeset();
        $this->expectException(RealTimeArtifactMessageException::class);
        $data = $this->message_builder->buildArtifactMoved($artifact, $this->tracker_semantic);
    }

    public function testBuildArtifactReorderedNoError(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupKanbanItemDao();
        $this->setupValues();
        $this->setupStatusField();
        $this->setupChangeSet();
        $this->tracker_semantic->method('isOpenValue')->willReturn(false);
        $data = $this->message_builder->buildArtifactReordered($artifact, $this->tracker_semantic);
        self::assertInstanceOf(KanbanArtifactMovedMessageRepresentationMercure::class, $data);
    }

    public function testBuildArtifactReorderedNoStatus(): void
    {
        $tracker  = $this->setupTracker();
        $artifact = $this->setupArtifact($tracker);
        $this->setupStatusField();
        $this->setupKanbanItemDao();
        $this->setupNullChangeSet();
        $this->expectException(RealTimeArtifactMessageException::class);
        $data = $this->message_builder->buildArtifactReordered($artifact, $this->tracker_semantic);
    }

    private function setupArtifact(\Tracker $tracker): Artifact
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(1);
        $artifact->method('getStatusForChangeset')->willReturn('');
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getStatusForChangeset')->willReturnOnConsecutiveCalls('', '');
        return $artifact;
    }

    private function setupTracker(): \Tracker
    {
        return TrackerTestBuilder::aTracker()->withId(1)->build();
    }

    private function setupValues(): void
    {
        $value1 = $this->createMock(\Tracker_FormElement_Field_List_BindValue::class);
        $value2 = $this->createMock(\Tracker_FormElement_Field_List_BindValue::class);
        $values = [$value1, $value2];
        $this->status_field->method('getAllValues')->willReturn($values);
    }

    private function setupStatusField(): void
    {
        $this->tracker_semantic->method('getField')->willReturn($this->status_field);
        $this->status_field->method('isNone')->willReturnOnConsecutiveCalls(true, true);
        $this->status_field->method('getLabel')->willReturn('test');
        $this->tracker_semantic->method('isOpenValue')->willReturn(true, true);
    }

    private function setupNullStatusField(): void
    {
        $this->tracker_semantic->method('getField')->willReturn(null);
    }

    private function setupKanbanItemDao(): void
    {
        $this->kanban_item_dao->method('getKanbanBacklogItemIds')->willReturn([
            ['id' => 3],
            ['id' => 4],
        ]);
        $this->kanban_item_dao->method('getKanbanArchiveItemIds')->willReturn([
            ['id' => 1],
            ['id' => 2],
        ]);
    }

    private function setupChangeSet(): void
    {
        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(true);
        $changeset->method('getId')->willReturn(1);
        $this->changeset_factory->method('getLastChangeset')->willReturn($changeset);
    }

    private function setupChangeSetHasNotChanged(): void
    {
        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(false);
        $changeset->method('getId')->willReturn(1);
        $this->changeset_factory->method('getLastChangeset')->willReturn($changeset);
    }

    private function setupNullChangeSet(): void
    {
        $this->changeset_factory->method('getLastChangeset')->willReturn(null);
    }

    private function setupPreviousChangeset(): void
    {
        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(true);
        $changeset->method('getId')->willReturn(1);
        $this->changeset_factory->method('getPreviousChangesetWithFieldValue')->willReturn($changeset);
    }

    private function setupNullPreviousChangeset(): void
    {
        $this->changeset_factory->method('getPreviousChangesetWithFieldValue')->willReturn(null);
    }
}
