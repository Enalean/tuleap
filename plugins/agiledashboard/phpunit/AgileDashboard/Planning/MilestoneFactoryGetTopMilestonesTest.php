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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use TimePeriodWithoutWeekEnd;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

final class MilestoneFactoryGetTopMilestonesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimeframeBuilder
     */
    private $timeframe_builder;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_VirtualTopMilestone
     */
    private $top_milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $planning_factory        = Mockery::mock(PlanningFactory::class);
        $this->artifact_factory  = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->timeframe_builder = Mockery::mock(TimeframeBuilder::class);

        $this->milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            $this->artifact_factory,
            Mockery::spy(Tracker_FormElementFactory::class),
            Mockery::mock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            Mockery::mock(PlanningPermissionsManager::class),
            Mockery::mock(AgileDashboard_Milestone_MilestoneDao::class),
            Mockery::mock(ScrumForMonoMilestoneChecker::class),
            $this->timeframe_builder,
            Mockery::mock(MilestoneBurndownFieldChecker::class)
        );

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')->andReturn(45);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(3233);

        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(12);
        $this->tracker->shouldReceive('getName')->andReturn('tracker');

        $this->user = Mockery::mock(PFUser::class);

        $this->top_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $this->top_milestone->shouldReceive('getPlanning')->andReturn($planning);
        $this->top_milestone->shouldReceive('getProject')->andReturn($project);

        $planning_factory->shouldReceive('getRootPlanning')->andReturn(Mockery::spy(Planning::class));
    }

    public function testItReturnsEmptyArrayWhenNoTopMilestonesExist(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactsByTrackerId')->andReturn([]);

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);

        $this->assertEmpty($milestones);
    }

    public function testItReturnsMilestonePerArtifact(): void
    {
        $artifact_1 = $this->mockAnArtifactWithoutAncestor();
        $artifact_2 = $this->mockAnArtifactWithoutAncestor();

        $my_artifacts = [
            $artifact_1,
            $artifact_2
        ];

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact_1, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact_2, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->artifact_factory->shouldReceive('getArtifactsByTrackerId')->andReturn($my_artifacts);


        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);

        $this->assertCount(2, $milestones);

        $milestone_1 = $milestones[0];
        $milestone_2 = $milestones[1];

        $this->assertEquals($artifact_1, $milestone_1->getArtifact());
        $this->assertEquals($artifact_2, $milestone_2->getArtifact());
    }

    public function testItSkipsArtifactsWithoutChangeset(): void
    {
        // Some artifacts have no changeset on Tuleap.net (because of anonymous that can create
        // artifacts but artifact creation fails because they have to write access to fields
        // the artifact creation is stopped half the way hence without changeset
        $artifact_1 = Mockery::mock(Tracker_Artifact::class);
        $artifact_1->shouldReceive('getLastChangeset')->andReturn(null);
        $artifact_1->shouldReceive('getTracker')->andReturn($this->tracker);

        $artifact_2 = $this->mockAnArtifactWithoutAncestor();

        $my_artifacts = [
            $artifact_1,
            $artifact_2
        ];

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact_2, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->artifact_factory->shouldReceive('getArtifactsByTrackerId')->andReturn($my_artifacts);

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);

        $this->assertCount(1, $milestones);

        $milestone_1 = $milestones[0];

        $this->assertEquals($artifact_2, $milestone_1->getArtifact());
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    protected function mockAnArtifactWithoutAncestor()
    {
        $artifact_1 = Mockery::mock(Tracker_Artifact::class);
        $artifact_1->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_1->shouldReceive('getTracker')->andReturn($this->tracker);
        $artifact_1->shouldReceive('getAllAncestors')->andReturn([]);

        return $artifact_1;
    }
}
