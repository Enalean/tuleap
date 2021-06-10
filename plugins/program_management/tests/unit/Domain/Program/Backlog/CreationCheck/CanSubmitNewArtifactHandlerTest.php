<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use ProjectManager;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanSubmitNewArtifactHandlerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramIncrementCreatorChecker
     */
    private $program_increment_creator_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|IterationCreatorChecker
     */
    private $iteration_creator_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramStore
     */
    private $program_store;

    protected function setUp(): void
    {
        $this->program_increment_creator_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $this->iteration_creator_checker         = $this->createStub(IterationCreatorChecker::class);
        $this->program_store                     = $this->createStub(ProgramStore::class);
        $this->project_manager                   = $this->createMock(ProjectManager::class);
    }

    public function testItDisablesArtifactSubmissionWhenCanNotCreateProgramIncrement(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $this->program_increment_creator_checker->method('canCreateAProgramIncrement')->willReturn(false);

        $this->mockProjectTeam();

        $this->getHandler()->handle($event);
        self::assertFalse($event->canSubmitNewArtifact());
    }

    public function testItDisablesArtifactSubmissionWhenCanNotCreateIteration(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $this->program_increment_creator_checker->method('canCreateAProgramIncrement')->willReturn(true);
        $this->iteration_creator_checker->method('canCreateAnIteration')->willReturn(false);

        $this->mockProjectTeam();

        $this->getHandler()->handle($event);
        self::assertFalse($event->canSubmitNewArtifact());
    }

    public function testItAllowsArtifactSubmissionWhenAllChecksAreValid(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $this->program_increment_creator_checker->method('canCreateAProgramIncrement')->willReturn(true);
        $this->iteration_creator_checker->method('canCreateAnIteration')->willReturn(true);

        $this->mockProjectTeam();

        $this->getHandler()->handle($event);
        self::assertTrue($event->canSubmitNewArtifact());
    }

    public function testItAllowsArtifactSubmissionWhenProjectIsNotAProgram(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $this->getHandler(false)->handle($event);
        self::assertTrue($event->canSubmitNewArtifact());
    }

    private function getHandler(bool $build_valid_program = true): CanSubmitNewArtifactHandler
    {
        $project_data_adapter = new ProjectAdapter($this->project_manager);

        $program_build = BuildProgramStub::stubValidProgram();
        if (! $build_valid_program) {
            $program_build = BuildProgramStub::stubInvalidProgram();
        }

        return new CanSubmitNewArtifactHandler(
            $program_build,
            $this->program_increment_creator_checker,
            $this->iteration_creator_checker,
            $this->program_store,
            $project_data_adapter
        );
    }

    private function mockProjectTeam(): void
    {
        $this->program_store->method('getTeamProjectIdsForGivenProgramProject')->willReturn([['team_project_id' => 104]]);
        $first_team_project = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with($first_team_project->getID())
            ->willReturn($first_team_project);
    }
}
