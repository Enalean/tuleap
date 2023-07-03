<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use PlanningFactory;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Baseline\Support\CurrentUserContext;

final class ArtifactLinkRepositoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    private ArtifactLinkRepository $repository;
    private PlanningFactory&MockObject $planning_factory;

    /** @before */
    protected function createInstance(): void
    {
        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->repository       = new ArtifactLinkRepository($this->planning_factory);
    }

    protected Tracker_Artifact_Changeset&MockObject $changeset;

    /** @before */
    protected function createEntities(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getGroupId')->willReturn(200);

        $this->changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->changeset->method('getTracker')->willReturn($tracker);
    }

    public function testFindLinkedArtifactIds(): void
    {
        $this->planning_factory
            ->method('getPlannings')
            ->willReturn([]);

        $artifact_link = $this->mockArtifactLink(
            $this->changeset,
            $this->current_tuleap_user,
            [
                $this->mockArtifactWithId(1),
                $this->mockArtifactWithId(2),
                $this->mockArtifactWithId(3),
            ]
        );

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getAnArtifactLinkField')
            ->with($this->current_tuleap_user)
            ->willReturn($artifact_link);

        $this->changeset
            ->method('getArtifact')
            ->willReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_tuleap_user, $this->changeset);

        self::assertEquals([1, 2, 3], $artifact_ids);
    }

    public function testFindLinkedArtifactIdsReturnsEmptyArrayWhenNoLinkField(): void
    {
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getAnArtifactLinkField')->willReturn(null);
        $this->changeset
            ->method('getArtifact')
            ->willReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_tuleap_user, $this->changeset);

        self::assertEquals([], $artifact_ids);
    }

    public function testFindLinkedArtifactIdsDoesNotReturnArtifactsOfMilestoneTrackers(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getGroupId')->willReturn(200);

        $this->changeset
            ->method('getTracker')
            ->willReturn($tracker);

        $planning = $this->createMock(Planning::class);
        $planning->method('getPlanningTrackerId')
            ->willReturn(10);

        $this->planning_factory
            ->method('getPlannings')
            ->with($this->current_tuleap_user, 200)
            ->willReturn([$planning]);

        $artifact_link = $this->mockArtifactLink(
            $this->changeset,
            $this->current_tuleap_user,
            [
                $this->mockArtifactWithIdAndTrackerId(1, 10),
                $this->mockArtifactWithIdAndTrackerId(2, 11),
            ]
        );

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getAnArtifactLinkField')
            ->with($this->current_tuleap_user)
            ->willReturn($artifact_link);

        $this->changeset
            ->method('getArtifact')
            ->willReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_tuleap_user, $this->changeset);

        self::assertEquals([2], $artifact_ids);
    }

    private function mockArtifactWithId(int $id): \Tuleap\Tracker\Artifact\Artifact&MockObject
    {
        return $this->mockArtifactWithIdAndTrackerId($id, 10);
    }

    private function mockArtifactWithIdAndTrackerId(int $id, int $tracker_id): \Tuleap\Tracker\Artifact\Artifact&MockObject
    {
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn($id);
        $artifact->method('getTrackerId')->willReturn($tracker_id);

        return $artifact;
    }

    private function mockArtifactLink(
        Tracker_Artifact_Changeset $changeset,
        PFUser $user,
        array $artifacts,
    ): Tracker_FormElement_Field_ArtifactLink&MockObject {
        $artifact_link = $this->createMock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link->method('getLinkedArtifacts')
            ->with($changeset, $user)
            ->willReturn($artifacts);

        return $artifact_link;
    }
}
