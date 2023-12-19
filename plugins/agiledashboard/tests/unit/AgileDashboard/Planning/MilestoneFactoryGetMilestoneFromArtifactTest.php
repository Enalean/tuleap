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
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

class MilestoneFactoryGetMilestoneFromArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $task_artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $release_artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $release_planning;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->project          = Mockery::spy(Project::class);
        $this->release_planning = Mockery::spy(Planning::class);

        $release_tracker = Mockery::mock(Tracker::class);
        $release_tracker->shouldReceive('getProject')->andReturn($this->project);
        $this->release_artifact = Mockery::mock(Artifact::class);
        $this->release_artifact->shouldReceive('getTracker')->andReturn($release_tracker);

        $task_tracker = Mockery::mock(Tracker::class);
        $task_tracker->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $this->task_artifact = Mockery::mock(Artifact::class);
        $this->task_artifact->shouldReceive('getTracker')->andReturn($task_tracker);

        $this->planning_factory = Mockery::mock(PlanningFactory::class);

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            Mockery::spy(Tracker_ArtifactFactory::class),
            Mockery::spy(Tracker_FormElementFactory::class),
            Mockery::spy(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            Mockery::spy(PlanningPermissionsManager::class),
            Mockery::spy(AgileDashboard_Milestone_MilestoneDao::class),
            Mockery::mock(SemanticTimeframeBuilder::class),
            new NullLogger(),
            Mockery::spy(MilestoneBurndownFieldChecker::class)
        );
    }

    public function testItCreateMilestoneFromArtifact(): void
    {
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->andReturn($this->release_planning)->once();
        $release_milestone = $this->milestone_factory->getMilestoneFromArtifact($this->release_artifact);
        $this->assertEqualToReleaseMilestone($release_milestone);
    }

    private function assertEqualToReleaseMilestone($actual_release_milestone): void
    {
        $expected_release_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->release_planning,
            $this->release_artifact,
        );
        $this->assertEquals($expected_release_milestone, $actual_release_milestone);
    }

    public function testItReturnsNullWhenThereIsNoPlanningForTheTracker(): void
    {
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->andReturn(null)->once();
        $task_milestone = $this->milestone_factory->getMilestoneFromArtifact($this->task_artifact);
        $this->assertNull($task_milestone);
    }
}
