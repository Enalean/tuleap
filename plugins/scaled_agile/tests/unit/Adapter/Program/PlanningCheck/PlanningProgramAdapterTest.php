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

namespace Tuleap\ScaledAgile\Adapter\Program\PlanningCheck;

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessProjectNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Program\Plan\PlanStore;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanningProgramAdapterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PlanningProgramAdapter
     */
    private $adapter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\URLVerification
     */
    private $url_verification;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanStore
     */
    private $plan_store;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProgramStore
     */
    private $program_store;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamStore
     */
    private $team_store;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory    = Mockery::mock(\TrackerFactory::class);
        $this->project_manager    = Mockery::mock(\ProjectManager::class);
        $this->url_verification   = Mockery::mock(\URLVerification::class);
        $this->plan_store         = Mockery::mock(PlanStore::class);
        $this->program_store      = Mockery::mock(ProgramStore::class);
        $this->team_store         = Mockery::mock(TeamStore::class);

        $this->adapter = new PlanningProgramAdapter(
            $this->tracker_factory,
            $this->project_manager,
            $this->url_verification,
            $this->plan_store,
            $this->program_store,
            $this->team_store
        );
    }

    public function testItThrowExceptionWhenStoreProgramIsNotAProgram(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 101]);
        $team    = new Project(['group_id' => $project->getID()]);
        $program_increment    = new Project(['group_id' => 200]);
        $this->project_manager->shouldReceive('getProject')->with($project->getId())->andReturn($team);
        $this->project_manager->shouldReceive('getProject')->with($program_increment->getId())->andReturn($program_increment);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn($program_increment->getID());
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturnFalse();

        $this->expectException(ProjectIsNotAProgramException::class);

        $this->adapter->buildProgramFromTeamProject($project, $user);
    }

    public function testItThrowExceptionWhenUserCanNotAccessToProgram(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 101]);
        $team    = new Project(['group_id' => $project->getID()]);
        $program_increment    = new Project(['group_id' => 200]);
        $this->project_manager->shouldReceive('getProject')->with($project->getId())->andReturn($team);
        $this->project_manager->shouldReceive('getProject')->with($program_increment->getId())->andReturn($program_increment);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn($program_increment->getID());
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturnTrue();

        $this->url_verification->shouldReceive('userCanAccessProject')->once()
            ->andThrow(Project_AccessProjectNotFoundException::class);
        $this->expectException(UserCanNotAccessToProgramException::class);
        $this->adapter->buildProgramFromTeamProject($project, $user);
    }

    public function testItBuildAProgram(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 101]);
        $team    = new Project(['group_id' => $project->getID()]);
        $program_increment    = new Project(['group_id' => 200]);
        $this->project_manager->shouldReceive('getProject')->with($project->getId())->andReturn($team);
        $this->project_manager->shouldReceive('getProject')->with($program_increment->getId())->andReturn($program_increment);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn($program_increment->getID());
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturnTrue();

        $this->url_verification->shouldReceive('userCanAccessProject')->once();

        $program = new Program($program_increment->getID());
        $this->assertEquals($program, $this->adapter->buildProgramFromTeamProject($project, $user));
    }

    public function testItThrowAnExceptionIfProgramTrackerIsNotFound(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $this->plan_store->shouldReceive('getProgramIncrementTrackerId')->andReturn(1);

        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturnNull();

        $this->expectException(ProgramNotFoundException::class);
        $this->adapter->buildProgramIncrementFromProjectId(100, $user);
    }

    public function testItThrowsAnExceptionIFUserCanNotSeeProgramTracker(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $this->plan_store->shouldReceive('getProgramIncrementTrackerId')->andReturn(1);

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $tracker->shouldReceive('userCanView')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturn($tracker);

        $this->expectException(ConfigurationUserCanNotSeeProgramException::class);

        $this->adapter->buildProgramIncrementFromProjectId(100, $user);
    }

    public function testItBuildProgramIncrementTracker(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $this->plan_store->shouldReceive('getProgramIncrementTrackerId')->andReturn(1);

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $tracker->shouldReceive('userCanView')->andReturnTrue();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturn($tracker);

        $program_increment = new ProgramIncrementTracker($tracker->getId());

        $this->assertEquals($program_increment, $this->adapter->buildProgramIncrementFromProjectId(100, $user));
    }
}
