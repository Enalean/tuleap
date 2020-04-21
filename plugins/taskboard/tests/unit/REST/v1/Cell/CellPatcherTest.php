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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\REST\v1\OrderRepresentation;
use Tuleap\AgileDashboard\REST\v1\Rank\ArtifactsRankOrderer;
use Tuleap\REST\I18NRestException;
use UserManager;
use Tuleap\Taskboard\Swimlane\SwimlaneChildrenRetriever;

final class CellPatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var CellPatcher */
    private $patcher;
    /** @var M\LegacyMockInterface|M\MockInterface|UserManager */
    private $user_manager;
    /** @var M\LegacyMockInterface|M\MockInterface|Tracker_ArtifactFactory */
    private $artifact_factory;
    /** @var M\LegacyMockInterface|M\MockInterface|SwimlaneChildrenRetriever */
    private $children_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|ArtifactsRankOrderer */
    private $rank_orderer;
    /** @var M\LegacyMockInterface|M\MockInterface|CardMappedFieldUpdater */
    private $mapped_field_updater;

    /** @var M\LegacyMockInterface|M\MockInterface|\PFUser */
    private $current_user;

    protected function setUp(): void
    {
        $this->user_manager         = M::mock(UserManager::class);
        $this->artifact_factory     = M::mock(Tracker_ArtifactFactory::class);
        $this->children_retriever   = M::mock(SwimlaneChildrenRetriever::class);
        $this->rank_orderer         = M::mock(ArtifactsRankOrderer::class);
        $this->mapped_field_updater = M::mock(CardMappedFieldUpdater::class);
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
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->patcher->patchCell(45, 7, new CellPatchRepresentation());
    }

    public function testPatchCellThrowsWhenCurrentUserCantSeeSwimlane(): void
    {
        $this->mockArtifact(45, false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->patcher->patchCell(45, 7, new CellPatchRepresentation());
    }

    public function testPatchCellThrowsWhenProjectIsSuspended(): void
    {
        $swimlane_artifact = $this->mockArtifact(45, true);
        $project           = M::mock(\Project::class)->shouldReceive(['isSuspended' => true])->getMock();
        $tracker           = M::mock(\Tracker::class)->shouldReceive(['getProject' => $project])->getMock();
        $swimlane_artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->rank_orderer->shouldNotReceive('reorder');
        $this->mapped_field_updater->shouldNotReceive('updateCardMappedField');
        $this->patcher->patchCell(45, 7, new CellPatchRepresentation());
    }

    public function testPatchCellThrowsWhenPatchPayloadIsInvalid(): void
    {
        $this->mockSwimlaneArtifactWithValidProject();
        $payload = new CellPatchRepresentation();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->rank_orderer->shouldNotReceive('reorder');
        $this->mapped_field_updater->shouldNotReceive('updateCardMappedField');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenArtifactToAddCantBeFound(): void
    {
        $this->mockSwimlaneArtifactWithValidProject();
        $payload      = new CellPatchRepresentation();
        $payload->add = 9999;
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(9999)
            ->andReturnNull();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->mapped_field_updater->shouldNotReceive('updateCardMappedField');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenCurrentUserCantSeeArtifactToAdd(): void
    {
        $this->mockSwimlaneArtifactWithValidProject();
        $this->mockArtifact(456, false);
        $payload         = new CellPatchRepresentation();
        $payload->add    = 456;

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->mapped_field_updater->shouldNotReceive('updateCardMappedField');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellUpdatesArtifactToAdd(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();
        $artifact_to_add   = $this->mockArtifact(456, true);
        $payload           = new CellPatchRepresentation();
        $payload->add      = 456;

        $this->mapped_field_updater->shouldReceive('updateCardMappedField')
            ->with($swimlane_artifact, 7, $artifact_to_add, $this->current_user)
            ->once();
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenOrderRepresentationIsInvalid(): void
    {
        $this->mockSwimlaneArtifactWithValidProject();

        $order              = new OrderRepresentation();
        $order->compared_to = 123;
        $order->direction   = "invalid";
        $order->ids         = [456];
        $payload            = new CellPatchRepresentation();
        $payload->order     = $order;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->rank_orderer->shouldNotReceive('reorder');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellThrowsWhenOrderRepresentationDoesNotHaveUniqueIds(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();

        $order              = new OrderRepresentation();
        $order->compared_to = 123;
        $order->direction   = OrderRepresentation::BEFORE;
        $order->ids         = [456, 456];
        $payload            = new CellPatchRepresentation();
        $payload->order     = $order;
        $this->children_retriever->shouldReceive('getSwimlaneArtifactIds')
            ->with($swimlane_artifact, $this->current_user)
            ->once()
            ->andReturn([123, 456]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->rank_orderer->shouldNotReceive('reorder');
        $this->patcher->patchCell(45, 7, $payload);
    }

    public function testPatchCellReordersChildrenOfSwimlane(): void
    {
        $swimlane_artifact = $this->mockSwimlaneArtifactWithValidProject();

        $order              = new OrderRepresentation();
        $order->compared_to = 123;
        $order->direction   = OrderRepresentation::BEFORE;
        $order->ids         = [456];
        $payload            = new CellPatchRepresentation();
        $payload->order     = $order;
        $this->children_retriever->shouldReceive('getSwimlaneArtifactIds')
            ->with($swimlane_artifact, $this->current_user)
            ->once()
            ->andReturn([123, 456]);
        $this->rank_orderer->shouldReceive('reorder')
            ->with($order, \Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, M::any())
            ->once();

        $this->patcher->patchCell(45, 7, $payload);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\PFUser
     */
    private function mockCurrentUser()
    {
        $current_user = M::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user);
        return $current_user;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\Tracker_Artifact
     */
    private function mockArtifact(int $artifact_id, bool $user_can_view)
    {
        $artifact = M::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($artifact_id);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with($artifact_id)
            ->andReturn($artifact);
        $artifact->shouldReceive('userCanView')
            ->once()
            ->with($this->current_user)
            ->andReturn($user_can_view);
        return $artifact;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\Tracker_Artifact
     */
    private function mockSwimlaneArtifactWithValidProject()
    {
        $swimlane_artifact = $this->mockArtifact(45, true);
        $project           = M::mock(\Project::class)->shouldReceive(['isSuspended' => false])->getMock();
        $tracker           = M::mock(\Tracker::class)->shouldReceive(['getProject' => $project])->getMock();
        $swimlane_artifact->shouldReceive('getTracker')->andReturn($tracker);

        return $swimlane_artifact;
    }
}
