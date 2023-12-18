<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use ArtifactNode;
use Mockery;
use PFUser;
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class MilestoneFactoryGetAllMilestonesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $planning_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $planning;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user    = Mockery::spy(PFUser::class);
        $this->project = Mockery::mock(Project::class);

        $this->planning_tracker = Mockery::mock(Tracker::class);
        $this->planning_tracker->shouldReceive('getProject')->andReturn($this->project);

        $this->planning = Mockery::mock(Planning::class);
        $this->planning->shouldReceive('getId')->andReturn(1);
        $this->planning->shouldReceive('getPlanningTracker')->andReturn($this->planning_tracker);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturn(12);

        $this->planning_factory = Mockery::spy(PlanningFactory::class);
        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
    }

    public function testItReturnsAnEmptyArrayWhenAllItemsAreClosed(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactsByTrackerIdUserCanView')->andReturn([]);

        $this->assertEquals([], $this->newMileStoneFactory()->getAllMilestones($this->user, $this->planning));
    }

    public function testItReturnsAsManyMilestonesAsThereAreArtifacts(): void
    {
        $artifact1 = Mockery::spy(Artifact::class);
        $artifact1->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $artifact2 = Mockery::spy(Artifact::class);
        $artifact2->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $artifacts = [
            $artifact1,
            $artifact2,
        ];

        $this->artifact_factory->shouldReceive('getArtifactsByTrackerIdUserCanView')->andReturn($artifacts);
        $this->assertCount(2, $this->newMileStoneFactory()->getAllMilestones($this->user, $this->planning));
    }

    public function testItReturnsMilestones(): void
    {
        $changeset01 = Mockery::spy(Tracker_Artifact_Changeset::class);
        $artifact    = Mockery::mock(Artifact::class);

        $artifact->shouldReceive('getId')->andReturns(101);
        $artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturns([]);
        $artifact->shouldReceive('getUniqueLinkedArtifacts')->with($this->user)->andReturns(null);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset01);

        $this->artifact_factory->shouldReceive('getArtifactsByTrackerIdUserCanView')->andReturn([$artifact]);

        $factory = $this->newMileStoneFactory();

        $all_milestones = $factory->getAllMilestones($this->user, $this->planning);
        $this->assertEquals($artifact->getId(), $all_milestones[0]->getArtifact()->getId());
    }

    public function testItReturnsMilestonesWithPlannedArtifacts(): void
    {
        $artifact = $this->getAMockedArtifact();
        $planning = $this->getAMockedPlanning();

        $this->artifact_factory->shouldReceive('getArtifactsByTrackerIdUserCanView')->andReturn([$artifact]);

        $planned_artifacts = new ArtifactNode($artifact);
        $factory           = $this->newMileStoneFactory();
        $factory->shouldReceive('getPlannedArtifacts')->andReturn($planned_artifacts);

        $milestone  = new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            Mockery::spy(ScrumForMonoMilestoneChecker::class),
            $planned_artifacts
        );
        $milestones = $factory->getAllMilestones($this->user, $planning);
        $this->assertEquals($milestone, $milestones[0]);
    }

    public function testItReturnsMilestonesWithoutPlannedArtifacts(): void
    {
        $artifact = $this->getAMockedArtifact();
        $planning = $this->getAMockedPlanning();

        $this->artifact_factory->shouldReceive('getArtifactsByTrackerIdUserCanView')->andReturn([$artifact]);

        $planned_artifacts = new ArtifactNode($artifact);
        $factory           = $this->newMileStoneFactory();
        $factory->shouldReceive('getPlannedArtifacts')->andReturn($planned_artifacts);

        $milestone  = new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            Mockery::spy(ScrumForMonoMilestoneChecker::class),
            null
        );
        $milestones = $factory->getAllMilestonesWithoutPlannedElement($this->user, $planning);
        $this->assertEquals($milestone, $milestones[0]);
    }

    /**
     * @return Mockery\Mock|Planning_MilestoneFactory
     */
    private function newMileStoneFactory()
    {
        return Mockery::mock(
            Planning_MilestoneFactory::class,
            [
                $this->planning_factory,
                $this->artifact_factory,
                Mockery::spy(Tracker_FormElementFactory::class),
                Mockery::spy(AgileDashboard_Milestone_MilestoneStatusCounter::class),
                Mockery::spy(PlanningPermissionsManager::class),
                Mockery::spy(AgileDashboard_Milestone_MilestoneDao::class),
                Mockery::spy(ScrumForMonoMilestoneChecker::class),
                Mockery::mock(SemanticTimeframeBuilder::class),
                new NullLogger(),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    protected function getAMockedArtifact()
    {
        $artifact = Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        return $artifact;
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    protected function getAMockedPlanning()
    {
        $tracker_id = 7777777;
        $planning   = Mockery::mock(Planning::class);
        $planning->shouldReceive('getPlanningTracker')->andReturn($this->planning_tracker);
        $planning->shouldReceive('getPlanningTrackerId')->andReturn($tracker_id);
        $planning->shouldReceive('getId')->andReturn(109);

        return $planning;
    }
}
