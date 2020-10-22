<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\NoProjectIncrementException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollection;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCollectionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|PlanningAdapter
     */
    private $planning_adapter;
    /**
     * @var TrackerCollectionFactory
     */
    private $builder;

    protected function setUp(): void
    {
        $this->planning_adapter = M::mock(PlanningAdapter::class);
        $this->builder          = new TrackerCollectionFactory($this->planning_adapter);
    }

    public function testBuildFromProgramProjectAndItsTeams(): void
    {
        $program_project     = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user                = UserTestBuilder::aUser()->build();

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

    public function testItThrowsWhenProgramPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $program_project     = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user                = UserTestBuilder::aUser()->build();

        $malformed_planning = new PlanningData(new \NullTracker(), 3, 'Malformed planning', []);
        $this->planning_adapter->shouldReceive('buildRootPlanning')
            ->once()
            ->with($user, 101)
            ->andReturn($malformed_planning);

        $this->expectException(NoProjectIncrementException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program_project, $teams, $user);
    }

    public function testItThrowsWhenTeamPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $program_project     = new \Project(['group_id' => '101']);
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user                = UserTestBuilder::aUser()->build();

        $this->mockRootPlanning(512, 101, $user);
        $malformed_planning = new PlanningData(new \NullTracker(), 3, 'Malformed planning', []);
        $this->planning_adapter->shouldReceive('buildRootPlanning')
            ->once()
            ->with($user, 103)
            ->andReturn($malformed_planning);

        $this->expectException(NoProjectIncrementException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program_project, $teams, $user);
    }

    public function testBuildFromTeamProjects(): void
    {
        $first_team_project  = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '123']);
        $teams               = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $user                = UserTestBuilder::aUser()->build();

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
        $milestone_tracker = TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withProject(new Project(['group_id' => $project_id]))
            ->build();
        $root_planning     = new PlanningData($milestone_tracker, 7, 'Root Planning', []);
        $this->planning_adapter->shouldReceive('buildRootPlanning')
            ->once()
            ->with($user, $project_id)
            ->andReturn($root_planning);
    }
}
