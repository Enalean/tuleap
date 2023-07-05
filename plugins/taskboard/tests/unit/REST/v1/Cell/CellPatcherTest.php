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

namespace Tuleap\Taskboard\REST\v1\Cell;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\REST\Helpers\OrderRepresentation;
use Tuleap\Tracker\REST\Helpers\ArtifactsRankOrderer;
use Tuleap\REST\I18NRestException;
use Tuleap\Taskboard\Swimlane\SwimlaneChildrenRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

final class CellPatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CellPatcher $patcher;
    private MockObject&UserManager $user_manager;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private SwimlaneChildrenRetriever&MockObject $children_retriever;
    private ArtifactsRankOrderer&MockObject $rank_orderer;
    private CardMappedFieldUpdater&MockObject $mapped_field_updater;
    private \PFUser $current_user;

    protected function setUp(): void
    {
        $this->user_manager         = $this->createMock(UserManager::class);
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->children_retriever   = $this->createMock(SwimlaneChildrenRetriever::class);
        $this->rank_orderer         = $this->createMock(ArtifactsRankOrderer::class);
        $this->mapped_field_updater = $this->createMock(CardMappedFieldUpdater::class);
        $this->patcher              = new CellPatcher(
            $this->user_manager,
            $this->artifact_factory,
            $this->children_retriever,
            $this->rank_orderer,
            $this->mapped_field_updater
        );
        $this->current_user         = $this->mockCurrentUser();
    }

    public function testPatchCellThrowsWhenSwimlaneArtifactCantBeFound(): void
    {
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->patcher->patchCell(45, 7, CellPatchRepresentation::build(null, null));
    }

    public function testPatchCellThrowsWhenCurrentUserCantSeeSwimlane(): void
    {
        $this->mockArtifact(45, false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->patcher->patchCell(45, 7, CellPatchRepresentation::build(null, null));
    }

    public function testPatchCellThrowsWhenProjectIsSuspended(): void
    {
        $swimlane_artifact = $this->mockArtifact(45, true);
        $project           = ProjectTestBuilder::aProject()->withStatusSuspended()->build();
        $tracker           = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $swimlane_artifact->method('getTracker')->willReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->rank_orderer->expects(self::never())->method('reorder');
        $this->mapped_field_updater->expects(self::never())->method('updateCardMappedField');
        $this->patcher->patchCell(45, 7, CellPatchRepresentation::build(null, null));
    }

    public function testPatchCellThrowsWhenPatchPayloadIsInvalid(): void
    {
        $artifact = $this->mockSwimlaneArtifactWithValidProject();
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->with(45)
            ->willReturn($artifact);

        $payload = CellPatchRepresentation::build(null, null);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->rank_orderer->expects(self::never())->method('reorder');
        $this->mapped_field_updater->expects(self::never())->method('updateCardMappedField');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenArtifactToAddCantBeFound(): void
    {
        $artifact = $this->mockSwimlaneArtifactWithValidProject();
        $payload  = CellPatchRepresentation::build(9999, null);
        $this->artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [45, $artifact],
                [9999, null],
            ]);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->mapped_field_updater->expects(self::never())->method('updateCardMappedField');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenCurrentUserCantSeeArtifactToAdd(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();
        $artifact          = $this->mockArtifactWithoutFactory(456, false);

        $this->artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [45, $swimlane_artifact],
                [456, $artifact],
            ]);

        $payload = CellPatchRepresentation::build(456, null);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->mapped_field_updater->expects(self::never())->method('updateCardMappedField');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellUpdatesArtifactToAdd(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();
        $artifact_to_add   = $this->mockArtifactWithoutFactory(456, true);
        $payload           = CellPatchRepresentation::build(456, null);

        $this->artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [45, $swimlane_artifact],
                [456, $artifact_to_add],
            ]);

        $this->mapped_field_updater
            ->expects(self::once())
            ->method('updateCardMappedField')
            ->with($swimlane_artifact, 7, $artifact_to_add, $this->current_user);

        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenOrderRepresentationIsInvalid(): void
    {
        $artifact = $this->mockSwimlaneArtifactWithValidProject();
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->with(45)
            ->willReturn($artifact);

        $order   = OrderRepresentation::build([456], "invalid", 123);
        $payload = CellPatchRepresentation::build(null, $order);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->rank_orderer->expects(self::never())->method('reorder');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenOrderRepresentationDoesNotHaveUniqueIds(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->with(45)
            ->willReturn($swimlane_artifact);

        $order   = OrderRepresentation::build([456, 456], OrderRepresentation::BEFORE, 123);
        $payload = CellPatchRepresentation::build(null, $order);
        $this->children_retriever->expects(self::once())
            ->method('getSwimlaneArtifactIds')
            ->with($swimlane_artifact, $this->current_user)
            ->willReturn([123, 456]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->rank_orderer->expects(self::never())->method('reorder');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellReordersChildrenOfSwimlane(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->with(45)
            ->willReturn($swimlane_artifact);

        $order   = OrderRepresentation::build([456], OrderRepresentation::BEFORE, 123);
        $payload = CellPatchRepresentation::build(null, $order);
        $this->children_retriever
            ->expects(self::once())
            ->method('getSwimlaneArtifactIds')
            ->with($swimlane_artifact, $this->current_user)
            ->willReturn([123, 456]);
        $this->rank_orderer
            ->expects(self::once())
            ->method('reorder')
            ->with($order, \Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, self::isInstanceOf(\Project::class));

        $this->patcher->patchCell(45, 7, $payload);
    }

    private function mockCurrentUser(): \PFUser
    {
        $current_user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getCurrentUser')
            ->willReturn($current_user);
        return $current_user;
    }

    private function mockArtifactWithoutFactory(int $artifact_id, bool $user_can_view): MockObject&Artifact
    {
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->expects(self::once())
            ->method('userCanView')
            ->with($this->current_user)
            ->willReturn($user_can_view);
        return $artifact;
    }

    private function mockArtifact(int $artifact_id, bool $user_can_view): MockObject&Artifact
    {
        $artifact = $this->mockArtifactWithoutFactory($artifact_id, $user_can_view);
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->with($artifact_id)
            ->willReturn($artifact);
        return $artifact;
    }

    private function mockSwimlaneArtifactWithValidProject(): MockObject&Artifact
    {
        $swimlane_artifact = $this->mockArtifactWithoutFactory(45, true);
        $project           = ProjectTestBuilder::aProject()->withStatusActive()->build();
        $tracker           = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $swimlane_artifact->method('getTracker')->willReturn($tracker);

        return $swimlane_artifact;
    }
}
