<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\DispatchSynchronizationCommandStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\StorePendingTeamSynchronizationStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SynchronizeTeamControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Stub&\ProjectManager
     */
    private $project_manager;
    private \HTTPRequest $request;
    private array $variables;
    private StorePendingTeamSynchronizationStub $store_pending_team_synchronization;

    private const TEAM_ID = 123;

    protected function setUp(): void
    {
        $this->project_manager                    = $this->createStub(\ProjectManager::class);
        $this->variables                          = ['project_name' => 'my-program', 'team_id' => self::TEAM_ID];
        $this->store_pending_team_synchronization = StorePendingTeamSynchronizationStub::withCount();

        $user          = UserTestBuilder::buildWithDefaults();
        $this->request = HTTPRequestBuilder::get()->withUser($user)->build();
    }

    private function getController(
        DispatchSynchronizationCommandStub $dispatch_synchronization_command_stub,
        SearchVisibleTeamsOfProgramStub $visible_teams_of_program_stub,
        BuildProgramStub $build_program_stub,
    ): SynchronizeTeamController {
        return new SynchronizeTeamController(
            $this->project_manager,
            $dispatch_synchronization_command_stub,
            $visible_teams_of_program_stub,
            $build_program_stub,
            $this->store_pending_team_synchronization
        );
    }

    public function testItThrowsNotFoundExceptionWhenProjectIsNotFoundInVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->getController(
            DispatchSynchronizationCommandStub::build(),
            SearchVisibleTeamsOfProgramStub::withNoTeam(),
            BuildProgramStub::stubInvalidProgram()
        )->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testItThrowsNotFoundWhenServiceIsNotAvailable(): void
    {
        $project = $this->getProject(false);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(NotFoundException::class);
        $this->getController(
            DispatchSynchronizationCommandStub::build(),
            SearchVisibleTeamsOfProgramStub::withNoTeam(),
            BuildProgramStub::stubInvalidProgram()
        )->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testPreventsAccessWhenProjectIsNotAProgram(): void
    {
        $project = $this->getProject(true);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(ForbiddenException::class);
        $this->getController(
            DispatchSynchronizationCommandStub::build(),
            SearchVisibleTeamsOfProgramStub::withNoTeam(),
            BuildProgramStub::stubInvalidProgram()
        )->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testPreventsAccessWhenUserHasNoAccessToProject(): void
    {
        $project = $this->getProject(true);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(ForbiddenException::class);
        $this->getController(
            DispatchSynchronizationCommandStub::build(),
            SearchVisibleTeamsOfProgramStub::withNoTeam(),
            BuildProgramStub::stubInvalidProgramAccess()
        )->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testPreventsAccessWhenProvidedTeamIsNotATeamOfCurrentProgram(): void
    {
        $project = $this->getProject(true);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(NotFoundException::class);
        $this->getController(
            DispatchSynchronizationCommandStub::build(),
            SearchVisibleTeamsOfProgramStub::withTeamIds(200, 300),
            BuildProgramStub::stubValidProgram()
        )->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testItDispatchesATeamSynchronizationCommand(): void
    {
        $project = $this->getProject(true);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $dispatch = DispatchSynchronizationCommandStub::build();

        $this->getController(
            $dispatch,
            SearchVisibleTeamsOfProgramStub::withTeamIds(self::TEAM_ID),
            BuildProgramStub::stubValidProgram()
        )->process($this->request, LayoutBuilder::build(), $this->variables);

        $dispatched_command = $dispatch->getCallParamsAtIndex(0);

        self::assertEquals(1, $dispatch->getCallsCount());
        self::assertSame(1, $this->store_pending_team_synchronization->getCallCount());
        self::assertNotNull($dispatched_command);
        self::assertSame($dispatched_command->getProgramId(), 1);
        self::assertSame($dispatched_command->getTeamId(), self::TEAM_ID);
    }

    private function getProject(bool $is_program_management_used = true): \Project
    {
        $project = $this->createMock(\Project::class);

        $project->method('getID')->willReturn(1);
        $project->method('isPublic')->willReturn(true);
        $project->method('getPublicName')->willReturn('Guinea Pig');
        $project->method('getUnixNameLowerCase')->willReturn('guinea-pig');
        $project->method('getIconUnicodeCodepoint')->willReturn('🐹');

        $project->expects(self::once())
            ->method('usesService')
            ->with(ProgramService::SERVICE_SHORTNAME)
            ->willReturn($is_program_management_used);

        return $project;
    }
}
