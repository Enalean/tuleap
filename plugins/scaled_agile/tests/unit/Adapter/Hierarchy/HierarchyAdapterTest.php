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

namespace Tuleap\ScaledAgile\Adapter\Program\Hierarchy;

use Mockery;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use TrackerFactory;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerAdapter;
use Tuleap\ScaledAgile\Adapter\Team\TeamTrackerAdapter;
use Tuleap\ScaledAgile\Program\Hierarchy\Hierarchy;
use Tuleap\ScaledAgile\Program\Plan\PlanStore;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class HierarchyAdapterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var HierarchyAdapter
     */
    private $hierarchy_adapter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamStore
     */
    private $team_store;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanStore
     */
    private $plan_store;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->planning_factory = Mockery::mock(PlanningFactory::class);
        $planning_adapter       = new PlanningAdapter($this->planning_factory);

        $this->tracker_factory  = \Mockery::mock(TrackerFactory::class);
        $this->team_store       = Mockery::mock(TeamStore::class);
        $team_tracker_adapter = new TeamTrackerAdapter($this->tracker_factory, $this->team_store);

        $this->plan_store       = Mockery::mock(PlanStore::class);
        $program_tracker_adapter = new ProgramTrackerAdapter($this->tracker_factory, $this->plan_store);

        $this->hierarchy_adapter = new HierarchyAdapter(
            $planning_adapter,
            $team_tracker_adapter,
            $program_tracker_adapter
        );
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotTopBacklogLevel(): void
    {
        $user               = UserTestBuilder::aUser()->build();
        $program            = new Program(101);
        $program_tracker_id = 1;
        $team_backlog_id    = 200;

        $project = $this->buildProgramTracker($program_tracker_id, $program->getId());
        $this->buildPlannableProgram($program_tracker_id);
        $team = $this->buildTeam($program, $team_backlog_id);
        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnTrue();

        $milestone_tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($team)
            ->build();
        $root_planning     = new Planning(7, 'Root Planning', $project->getID(), '', []);
        $root_planning->setPlanningTracker($milestone_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, $team->getGroupId())->once()->andReturn($root_planning);

        $this->expectException(TeamTrackerMustBeInPlannableTopBacklogException::class);

        $this->hierarchy_adapter->buildHierarchy($user, $program, $program_tracker_id, $team_backlog_id);
    }

    public function testItBuildsHierarchy(): void
    {
        $user               = UserTestBuilder::aUser()->build();
        $program            = new Program(101);
        $program_tracker_id = 1;
        $team_backlog_id    = 200;

        $project = $this->buildProgramTracker($program_tracker_id, $program->getId());
        $this->buildPlannableProgram($program_tracker_id);
        $team = $this->buildTeam($program, $team_backlog_id);

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnTrue();

        $milestone_tracker = TrackerTestBuilder::aTracker()
            ->withId(300)
            ->withProject($project)
            ->build();

        $user_story_tracker = TrackerTestBuilder::aTracker()
            ->withId($team_backlog_id)
            ->withProject($project)
            ->build();
        $root_planning      = new Planning(7, 'Root Planning', $project->getID(), '', [$user_story_tracker]);
        $root_planning->setPlanningTracker($milestone_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, $team->getGroupId())->once()->andReturn(
            $root_planning
        );
        $root_planning->setBacklogTrackers([$user_story_tracker]);

        $expected = new Hierarchy($program_tracker_id, $team_backlog_id);

        $this->assertEquals(
            $expected,
            $this->hierarchy_adapter->buildHierarchy($user, $program, $program_tracker_id, $team_backlog_id)
        );
    }

    private function buildProgramTracker(int $program_tracker_id, int $project_id): Project
    {
        $project         = new \Project(
            ['group_id' => $project_id, 'group_name' => 'Program', 'unix_group_name' => 'program']
        );
        $program_tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($program_tracker_id)->once()->andReturn(
            $program_tracker
        );

        return $project;
    }

    private function buildPlannableProgram(int $program_tracker_id): void
    {
        $this->plan_store->shouldReceive('isPlannable')->with($program_tracker_id)->once()->andReturnTrue();
    }

    private function buildTeam(Program $program, int $team_backlog_id): Project
    {
        $team         = new \Project(['group_id' => $program->getId(), 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId($team_backlog_id)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($team_backlog_id)->once()->andReturn(
            $team_tracker
        );

        return $team;
    }
}
