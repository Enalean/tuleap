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

namespace Tuleap\ProgramManagement;

use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class SynchronizeTeamControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Stub&\ProjectManager
     */
    private $project_manager;
    private \HTTPRequest $request;
    private array $variables;

    protected function setUp(): void
    {
        $this->project_manager = $this->createStub(\ProjectManager::class);
        $this->variables       = ["project_name" => "my-program", 'team_id' => 123];

        $user          = UserTestBuilder::buildWithDefaults();
        $this->request = HTTPRequestBuilder::get()->withUser($user)->build();
    }

    private function getController(VerifyIsTeam $verify_is_team): SynchronizeTeamController
    {
        return new SynchronizeTeamController(
            $this->project_manager,
            $verify_is_team,
            new TestLogger()
        );
    }

    public function testItThrowsNotFoundExceptionWhenProjectIsNotFoundInVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withValidTeam())->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testItThrowsNotFoundWhenServiceIsNotAvailable(): void
    {
        $project = $this->getProject(false);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withValidTeam())->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testPreventsAccessWhenProjectIsATeam(): void
    {
        $project = $this->getProject(true);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(ForbiddenException::class);
        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process($this->request, LayoutBuilder::build(), $this->variables);
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
