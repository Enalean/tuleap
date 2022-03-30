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

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use Planning;
use PlanningFactory;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Baseline\Support\CurrentUserContext;

class ArtifactLinkRepositoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ArtifactLinkRepository */
    private $repository;

    /** @var PlanningFactory|MockInterface */
    private $planning_factory;

    /** @before */
    protected function createInstance()
    {
        $this->planning_factory = Mockery::mock(PlanningFactory::class);
        $this->repository       = new ArtifactLinkRepository($this->planning_factory);
    }

    /** @var Tracker_Artifact_Changeset|MockInterface */
    protected $changeset;

    /** @before */
    protected function createEntities(): void
    {
        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset->allows(['getTracker->getGroupId' => 200]);
    }

    public function testFindLinkedArtifactIds()
    {
        $this->planning_factory
            ->shouldReceive('getPlannings')
            ->andReturn([]);

        $artifact_link = $this->mockArtifactLink(
            $this->changeset,
            $this->current_user,
            [
                $this->mockArtifactWithId(1),
                $this->mockArtifactWithId(2),
                $this->mockArtifactWithId(3),
            ]
        );

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)
            ->shouldReceive('getAnArtifactLinkField')
            ->with($this->current_user)
            ->andReturn($artifact_link)
            ->getMock();

        $this->changeset
            ->shouldReceive('getArtifact')
            ->andReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_user, $this->changeset);

        $this->assertEquals([1, 2, 3], $artifact_ids);
    }

    public function testFindLinkedArtifactIdsReturnsEmptyArrayWhenNoLinkField()
    {
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class, ['getAnArtifactLinkField' => null]);
        $this->changeset
            ->shouldReceive('getArtifact')
            ->andReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_user, $this->changeset);

        $this->assertEquals([], $artifact_ids);
    }

    public function testFindLinkedArtifactIdsDoesNotReturnArtifactsOfMilestoneTrackers()
    {
        $tracker = Mockery::mock(Tracker::class, ['getGroupId' => 200]);

        $this->changeset
            ->shouldReceive('getTracker')
            ->andReturn($tracker);

        $this->planning_factory
            ->shouldReceive('getPlannings')
            ->with($this->current_user, 200)
            ->andReturn(
                [
                    Mockery::mock(Planning::class)
                        ->shouldReceive('getPlanningTrackerId')
                        ->andReturn(10)
                        ->getMock(),
                ]
            );

        $artifact_link = $this->mockArtifactLink(
            $this->changeset,
            $this->current_user,
            [
                $this->mockArtifactWithIdAndTrackerId(1, 10),
                $this->mockArtifactWithIdAndTrackerId(2, 11),
            ]
        );

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)
            ->shouldReceive('getAnArtifactLinkField')
            ->with($this->current_user)
            ->andReturn($artifact_link)
            ->getMock();

        $this->changeset
            ->shouldReceive('getArtifact')
            ->andReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_user, $this->changeset);

        $this->assertEquals([2], $artifact_ids);
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact|MockInterface
     */
    private function mockArtifactWithId(int $id): \Tuleap\Tracker\Artifact\Artifact
    {
        return $this->mockArtifactWithIdAndTrackerId($id, 10);
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact|MockInterface
     */
    private function mockArtifactWithIdAndTrackerId(int $id, int $tracker_id): \Tuleap\Tracker\Artifact\Artifact
    {
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->allows(['getId' => $id, 'getTrackerId' => $tracker_id]);
        return $artifact;
    }

    /**
     * @return Tracker_FormElement_Field_ArtifactLink|MockInterface
     */
    private function mockArtifactLink(
        Tracker_Artifact_Changeset $changeset,
        PFUser $user,
        array $artifacts,
    ): Tracker_FormElement_Field_ArtifactLink {
        $artifact_link = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class)
            ->shouldReceive('getLinkedArtifacts')
            ->with($changeset, $user)
            ->andReturn($artifacts)
            ->getMock();
        return $artifact_link;
    }
}
