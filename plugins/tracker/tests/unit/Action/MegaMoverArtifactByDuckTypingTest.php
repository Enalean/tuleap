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

use Psr\Log\NullLogger;
use Tracker;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_XMLImport;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionManager;
use Tuleap\Tracker\Artifact\XML\Exporter\ArtifactXMLExporter;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\Test\Builders\ArtifactImportedMappingBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\UpdateMoveChangesetXMLDuckTypingStub;
use Tuleap\Tracker\Tracker\XML\Updater\UpdateMoveChangesetXMLDuckTyping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MegaMoverArtifactByDuckTypingTest extends TestCase
{
    private ArtifactsDeletionManager&\PHPUnit\Framework\MockObject\MockObject $artifacts_deletion_manager;
    private ArtifactXMLExporter&\PHPUnit\Framework\MockObject\MockObject $xml_exporter;
    private UpdateMoveChangesetXMLDuckTyping $xml_updater;
    private Tracker_Artifact_PriorityManager&\PHPUnit\Framework\MockObject\MockObject $artifact_priority_manager;
    private Tracker_Artifact_XMLImport&\PHPUnit\Framework\MockObject\MockObject $xml_import;
    private MegaMoverArtifactByDuckTyping $artifact_mover;
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private Tracker $source_tracker;
    private \PFUser $user;
    private DuckTypedMoveFieldCollection $fields;
    private \Project $project;
    private \Tracker_XML_Importer_ArtifactImportedMapping $artifacts_mapping;

    protected function setUp(): void
    {
        $this->artifacts_deletion_manager = $this->createMock(ArtifactsDeletionManager::class);
        $this->xml_exporter               = $this->createMock(ArtifactXMLExporter::class);
        $this->xml_updater                = UpdateMoveChangesetXMLDuckTypingStub::build();
        $this->artifact_priority_manager  = $this->createMock(Tracker_Artifact_PriorityManager::class);
        $transaction_executor             = new DBTransactionExecutorPassthrough();
        $this->xml_import                 = $this->createMock(Tracker_Artifact_XMLImport::class);

        $this->artifact_mover = new MegaMoverArtifactByDuckTyping(
            $this->artifacts_deletion_manager,
            $this->xml_exporter,
            $this->xml_updater,
            $this->artifact_priority_manager,
            $transaction_executor,
            $this->xml_import,
        );

        $this->user              = UserTestBuilder::anActiveUser()->build();
        $this->artifact          = ArtifactTestBuilder::anArtifact(1)->submittedBy($this->user)->build();
        $this->project           = ProjectTestBuilder::aProject()->withId(199)->build();
        $this->source_tracker    = TrackerTestBuilder::aTracker()->withProject($this->project)->build();
        $this->fields            = DuckTypedMoveFieldCollection::fromFields([], [], [], []);
        $this->artifacts_mapping = ArtifactImportedMappingBuilder::fromSourcesAndDestinations([]);
    }

    public function testItThrowsWhenProjectIsNotActive(): void
    {
        $project        = ProjectTestBuilder::aProject()->withStatusDeleted()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $this->expectException(MoveArtifactTargetProjectNotActiveException::class);

        $this->artifact_mover->move($this->artifact, $this->source_tracker, $target_tracker, $this->user, $this->fields, $this->artifacts_mapping, new NullLogger());
    }

    public function testItThrowsWhenMoveCanNotBeProcessed(): void
    {
        $project  = ProjectTestBuilder::aProject()->withStatusActive()->build();
        $workflow = $this->createMock(\Workflow::class);
        $workflow->expects($this->once())->method('disable');
        $target_tracker = $this->createStub(Tracker::class);
        $target_tracker->method('getProject')->willReturn($project);
        $target_tracker->method('getWorkflow')->willReturn($workflow);

        $this->xml_exporter->expects($this->once())->method('exportFullHistory');
        $this->xml_import->expects($this->once())->method('importArtifactWithAllDataFromXMLContentInAMoveContext')->willReturn(null);
        $this->artifact_priority_manager->expects($this->once())->method('getGlobalRank')->willReturn(86);
        $this->artifacts_deletion_manager->expects($this->once())->method('deleteArtifactBeforeMoveOperation');

        $this->expectException(MoveArtifactNotDoneException::class);

        $this->artifact_mover->move($this->artifact, $this->source_tracker, $target_tracker, $this->user, $this->fields, $this->artifacts_mapping, new NullLogger());
        self::assertSame(1, $this->xml_updater->getCallCount());
    }

    public function testItRunTheMoveAndUpdateRankAndReturnTotalArtifactsDeleted(): void
    {
        $workflow = $this->createMock(\Workflow::class);
        $workflow->expects($this->once())->method('disable');
        $target_tracker = $this->createStub(Tracker::class);
        $target_tracker->method('getProject')->willReturn($this->project);
        $target_tracker->method('getWorkflow')->willReturn($workflow);

        $exported_artifact = ArtifactTestBuilder::anArtifact(1)->build();

        $this->xml_exporter->expects($this->once())->method('exportFullHistory');
        $this->xml_import->expects($this->once())->method('importArtifactWithAllDataFromXMLContentInAMoveContext')->willReturn($exported_artifact);
        $this->artifact_priority_manager->expects($this->once())->method('putArtifactAtAGivenRank');
        $this->artifact_priority_manager->expects($this->once())->method('getGlobalRank')->willReturn(86);
        $this->artifacts_deletion_manager->expects($this->once())->method('deleteArtifactBeforeMoveOperation');

        $this->artifact_mover->move($this->artifact, $this->source_tracker, $target_tracker, $this->user, $this->fields, $this->artifacts_mapping, new NullLogger());
        self::assertSame(1, $this->xml_updater->getCallCount());
    }
}
