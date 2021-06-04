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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Planning;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCollectionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $team_project_id = 104;
    private Project $second_team;
    private Project $first_team;
    private ProgramIncrementCollectionFactory $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|BuildPlanProgramIncrementConfiguration
     */
    private $configuration_builder;

    protected function setUp(): void
    {
        $this->planning_factory = $this->createStub(\PlanningFactory::class);
        $planning_adapter       = new PlanningAdapter($this->planning_factory);

        $this->configuration_builder = $this->createStub(BuildPlanProgramIncrementConfiguration::class);
        $this->builder               = new ProgramIncrementCollectionFactory(
            $planning_adapter,
            $this->configuration_builder
        );

        $this->first_team  = ProjectAdapter::build(
            ProjectTestBuilder::aProject()->withId($this->team_project_id)->build()
        );
        $this->second_team = ProjectAdapter::build(ProjectTestBuilder::aProject()->withId(123)->build());
    }

    public function testBuildFromProgramProjectAndItsTeams(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $teams   = new TeamProjectsCollection([$this->first_team, $this->second_team]);

        $program_tracker_id = 512;
        $this->configuration_builder->method('buildProgramIncrementTrackerFromProgram')
            ->willReturn(new ProgramTracker(TrackerTestBuilder::aTracker()->withId($program_tracker_id)->build()));
        $first_tracker_id     = 1024;
        $first_team_planning  = $this->buildRootPlanning($first_tracker_id, $this->team_project_id);
        $second_tracker_id    = 2048;
        $second_team_planning = $this->buildRootPlanning($second_tracker_id, 123);
        $this->planning_factory->method('getRootPlanning')
            ->willReturnOnConsecutiveCalls($first_team_planning, $second_team_planning);

        $trackers = $this->builder->buildFromProgramProjectAndItsTeam($program, $teams, $user);
        $ids      = $trackers->getTrackerIds();
        self::assertContains($program_tracker_id, $ids);
        self::assertContains($first_tracker_id, $ids);
        self::assertContains($second_tracker_id, $ids);
    }

    public function testItThrowsWhenTeamPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $teams   = new TeamProjectsCollection([$this->first_team, $this->second_team]);

        $program_tracker_id = 512;
        $this->configuration_builder->method('buildProgramIncrementTrackerFromProgram')
            ->willReturn(new ProgramTracker(TrackerTestBuilder::aTracker()->withId($program_tracker_id)->build()));

        $malformed_planning = new Planning(1, 'Malformed planning', $this->team_project_id, '', []);
        $malformed_planning->setPlanningTracker(new \NullTracker());
        $this->planning_factory->method('getRootPlanning')
            ->willReturn($malformed_planning);

        $this->expectException(PlanningHasNoProgramIncrementException::class);
        $this->builder->buildFromProgramProjectAndItsTeam($program, $teams, $user);
    }

    private function buildRootPlanning(int $tracker_id, int $project_id): Planning
    {
        $project           = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $milestone_tracker = TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withProject($project)
            ->build();
        $root_planning     = new Planning(7, 'Root Planning', $project->getID(), '', []);
        $root_planning->setPlanningTracker($milestone_tracker);
        return $root_planning;
    }
}
