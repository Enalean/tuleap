<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Milestone;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\TeamProjectsCollection;
use Tuleap\Test\Builders\UserTestBuilder;

final class MilestoneTrackerCollectionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var MilestoneTrackerCollectionFactory
     */
    private $builder;

    protected function setUp(): void
    {
        $this->planning_factory = M::mock(\PlanningFactory::class);
        $this->builder          = new MilestoneTrackerCollectionFactory($this->planning_factory);
    }

    public function testBuildFromProgramProjectAndItsTeams(): void
    {
        $program_project         = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user = UserTestBuilder::aUser()->build();

        $program_tracker_id = 512;
        $this->mockRootPlanning($program_tracker_id, 101, $user);
        $first_tracker_id = 1024;
        $this->mockRootPlanning($first_tracker_id, 103, $user);
        $second_tracker_id = 2048;
        $this->mockRootPlanning($second_tracker_id, 123, $user);

        $trackers = $this->builder->buildFromProgramProjectAndItsTeam(
            $program_project,
            $teams,
            $user
        );
        $ids      = $trackers->getTrackerIds();
        $this->assertContains($program_tracker_id, $ids);
        $this->assertContains($first_tracker_id, $ids);
        $this->assertContains($second_tracker_id, $ids);
    }

    public function testItThrowsWhenProgramProjectHasNoRootPlanning(): void
    {
        $program_project         = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user = UserTestBuilder::aUser()->build();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->andReturnNull();

        $this->expectException(MissingRootPlanningException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program_project, $teams, $user);
    }

    public function testItThrowsWhenProgramPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $program_project         = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user = UserTestBuilder::aUser()->build();

        $malformed_planning = new \Planning(3, 'Malformed planning', 101, 'Irrelevant', 'Irrelevant');
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 101)
            ->andReturn($malformed_planning);

        $this->expectException(NoMilestoneTrackerException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program_project, $teams, $user);
    }

    public function testItThrowsWhenTeamProjectHasNoRootPlanning(): void
    {
        $program_project         = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user = UserTestBuilder::aUser()->build();

        $this->mockRootPlanning(512, 101, $user);
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($user, 103)
            ->andReturnNull();

        $this->expectException(MissingRootPlanningException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program_project, $teams, $user);
    }

    public function testItThrowsWhenTeamPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $program_project         = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user = UserTestBuilder::aUser()->build();

        $this->mockRootPlanning(512, 101, $user);
        $malformed_planning = new \Planning(3, 'Malformed planning', 103, 'Irrelevant', 'Irrelevant');
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 103)
            ->andReturn($malformed_planning);

        $this->expectException(NoMilestoneTrackerException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program_project, $teams, $user);
    }

    public function testBuildFromTeamProjects(): void
    {
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user                       = UserTestBuilder::aUser()->build();

        $first_tracker_id = 1024;
        $this->mockRootPlanning($first_tracker_id, 103, $user);
        $second_tracker_id = 2048;
        $this->mockRootPlanning($second_tracker_id, 123, $user);

        $trackers = $this->builder->buildFromTeamProjects($teams, $user);
        $ids      = $trackers->getTrackerIds();
        $this->assertContains($first_tracker_id, $ids);
        $this->assertContains($second_tracker_id, $ids);
    }

    private function mockRootPlanning(int $tracker_id, int $project_id, \PFUser $user): void
    {
        $root_planning     = new \Planning(7, 'Root Planning', $project_id, 'Irrelevant', 'Irrelevant');
        $milestone_tracker = M::mock(\Tracker::class);
        $milestone_tracker->shouldReceive('getId')->andReturn($tracker_id);
        $milestone_tracker->shouldReceive('getGroupId')->andReturn($project_id);
        $root_planning->setPlanningTracker($milestone_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, $project_id)
            ->andReturn($root_planning);
    }
}
