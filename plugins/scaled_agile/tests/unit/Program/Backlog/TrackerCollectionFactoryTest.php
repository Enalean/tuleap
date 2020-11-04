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
use Planning;
use Project;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\NoProgramIncrementException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCollectionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \Tuleap\ScaledAgile\ProjectData
     */
    private $project_data;

    /**
     * @var \Tuleap\ScaledAgile\ProjectData
     */
    private $second_team_project_data;

    /**
     * @var \Tuleap\ScaledAgile\ProjectData
     */
    private $first_team_project_data;

    /**
     * @var \Tuleap\ScaledAgile\ProjectData
     */
    private $program_project_data;
    /**
     * @var TrackerCollectionFactory
     */
    private $builder;

    protected function setUp(): void
    {
        $this->planning_factory = \Mockery::mock(\PlanningFactory::class);
        $planning_adapter       = new PlanningAdapter($this->planning_factory);
        $this->builder          = new TrackerCollectionFactory($planning_adapter);

        $this->program_project_data     = ProjectDataAdapter::build(
            new \Project(['group_id' => '101', 'unix_group_name' => "program", 'group_name' => 'Program'])
        );
        $this->first_team_project_data  = ProjectDataAdapter::build(
            new \Project(['group_id' => '103', 'unix_group_name' => "teamA", 'group_name' => 'First Team'])
        );
        $this->second_team_project_data = ProjectDataAdapter::build(
            new \Project(['group_id' => '123', 'unix_group_name' => "teamB", 'group_name' => 'Second Team'])
        );

        $project            = new \Project(
            ['group_id' => 101, 'unix_group_name' => "project_name", 'group_name' => 'Public Name']
        );
        $tracker            = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $this->tracker_data = TrackerDataAdapter::build($tracker);
        $this->project_data = ProjectDataAdapter::build($project);
    }

    public function testBuildFromProgramProjectAndItsTeams(): void
    {
        $teams               = new TeamProjectsCollection(
            [$this->first_team_project_data, $this->second_team_project_data]
        );
        $user                = UserTestBuilder::aUser()->build();

        $program_tracker_id = 512;
        $this->mockRootPlanning($program_tracker_id, 101, $user);
        $first_tracker_id = 1024;
        $this->mockRootPlanning($first_tracker_id, 103, $user);
        $second_tracker_id = 2048;
        $this->mockRootPlanning($second_tracker_id, 123, $user);

        $trackers = $this->builder->buildFromProgramProjectAndItsTeam(
            $this->program_project_data,
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
        $teams = new TeamProjectsCollection(
            [$this->first_team_project_data, $this->second_team_project_data]
        );
        $user  = UserTestBuilder::aUser()->build();

        $malformed_planning = new Planning(1, 'Malformed planning', $this->project_data->getId(), '', []);
        $malformed_planning->setPlanningTracker(new \NullTracker());
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, $this->project_data->getId())
            ->andReturn($malformed_planning);

        $this->expectException(NoProgramIncrementException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($this->program_project_data, $teams, $user);
    }

    public function testItThrowsWhenTeamPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $teams = new TeamProjectsCollection(
            [$this->first_team_project_data, $this->second_team_project_data]
        );
        $user  = UserTestBuilder::aUser()->build();

        $this->mockRootPlanning(512, 101, $user);

        $malformed_planning = new Planning(1, 'Malformed planning', $this->project_data->getId(), '', []);
        $malformed_planning->setPlanningTracker(new \NullTracker());
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, $this->first_team_project_data->getId())
            ->andReturn($malformed_planning);

        $this->expectException(NoProgramIncrementException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($this->program_project_data, $teams, $user);
    }

    public function testBuildFromTeamProjects(): void
    {
        $teams               = new TeamProjectsCollection(
            [$this->first_team_project_data, $this->second_team_project_data]
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
        $project           = new Project(
            ['group_id' => $project_id, 'unix_group_name' => 'irrelevant', 'group_name' => "Irrelevant"]
        );
        $milestone_tracker = TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withProject($project)
            ->build();
        $root_planning     = new Planning(7, 'Root Planning', $project->getID(), '', []);
        $root_planning->setPlanningTracker($milestone_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, $project_id)
            ->andReturn($root_planning);
    }
}
