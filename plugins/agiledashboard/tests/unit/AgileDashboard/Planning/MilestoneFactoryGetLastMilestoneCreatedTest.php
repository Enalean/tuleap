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
use Mockery;
use PFUser;
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class MilestoneFactoryGetLastMilestoneCreatedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Tracker
     */
    private $planning_tracker;
    /**
     * @var int
     */
    private $planning_tracker_id;
    /**
     * @var int
     */
    private $planning_id;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $current_user;
    /**
     * @var Mockery\Mock | Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $sprint_1_artifact;
    /**
     * @var Planning_ArtifactMilestone
     */
    private $sprint_1_milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->current_user      = Mockery::mock(PFUser::class);
        $planning_factory        = Mockery::spy(PlanningFactory::class);
        $this->artifact_factory  = Mockery::spy(Tracker_ArtifactFactory::class);
        $this->milestone_factory = Mockery::mock(
            Planning_MilestoneFactory::class,
            [
                $planning_factory,
                $this->artifact_factory,
                Mockery::mock(Tracker_FormElementFactory::class),
                Mockery::mock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
                Mockery::mock(PlanningPermissionsManager::class),
                Mockery::mock(AgileDashboard_Milestone_MilestoneDao::class),
                Mockery::mock(SemanticTimeframeBuilder::class),
                new NullLogger(),
                Mockery::spy(MilestoneBurndownFieldChecker::class),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->sprint_1_artifact  = Mockery::mock(Artifact::class);
        $this->sprint_1_milestone = Mockery::mock(Planning_ArtifactMilestone::class);

        $this->planning_id         = 12;
        $this->planning_tracker_id = 123;
        $this->planning_tracker    = Mockery::mock(Tracker::class);
        $this->planning_tracker->shouldReceive('getProject')->andReturn(Mockery::spy(Project::class));

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')->andReturn($this->planning_tracker_id);
        $planning->shouldReceive('getPlanningTracker')->andReturn($this->planning_tracker);

        $planning_factory->shouldReceive('getPlanning')->with($this->planning_id)->andReturn($planning);
    }

    public function testItReturnsEmptyMilestoneWhenNothingMatches(): void
    {
        $this->artifact_factory->shouldReceive('getOpenArtifactsByTrackerIdUserCanView')->andReturn([]);
        $milestone = $this->milestone_factory->getLastMilestoneCreated($this->current_user, $this->planning_id);
        $this->assertInstanceOf(Planning_NoMilestone::class, $milestone);
    }

    public function testItReturnsTheLastOpenArtifactOfPlanningTracker(): void
    {
        $this->artifact_factory->shouldReceive('getOpenArtifactsByTrackerIdUserCanView')
            ->with($this->current_user, $this->planning_tracker_id)
            ->andReturn(['115' => $this->sprint_1_artifact, '104' => Mockery::mock(Artifact::class)]);

        $this->milestone_factory->shouldReceive('getMilestoneFromArtifact')
            ->with($this->sprint_1_artifact)
            ->andReturn($this->sprint_1_milestone);

        $milestone = $this->milestone_factory->getLastMilestoneCreated($this->current_user, $this->planning_id);
        $this->assertEquals($this->sprint_1_milestone, $milestone);
    }
}
