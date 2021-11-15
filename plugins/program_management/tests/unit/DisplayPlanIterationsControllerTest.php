<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Adapter\Program\DisplayPlanIterationsPresenter;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramBaseInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramPrivacyStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramUserPrivilegesStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class DisplayPlanIterationsControllerTest extends TestCase
{
    private const PROGRAM_ID           = 101;
    private const PROGRAM_INCREMENT_ID = 1260;

    /**
     * @var Stub&\ProjectManager
     */
    private $project_manager;
    /**
     * @var MockObject&\TemplateRenderer
     */
    private $template_renderer;

    protected function setUp(): void
    {
        $this->project_manager   = $this->createStub(\ProjectManager::class);
        $this->template_renderer = $this->createMock(\TemplateRenderer::class);
    }

    public function testItThrowsNotFoundExceptionWhenProjectIsNotFoundInVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $variables = ['project_name' => 'unknown-project-unix-name'];

        $this->expectException(NotFoundException::class);

        $this->getController(BuildProgramStub::stubInvalidProgram())
            ->process(
                HTTPRequestBuilder::get()->build(),
                LayoutBuilder::build(),
                $variables
            );
    }

    public function testItThrowsNotFoundWhenServiceIsNotAvailable(): void
    {
        $variables = ['project_name' => 'guinea-pig'];
        $project   = $this->getProject(false);

        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(NotFoundException::class);
        $this->getController(BuildProgramStub::stubInvalidProgram())->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $variables);
    }

    public function testPreventsAccessWhenProjectIsATeam(): void
    {
        $request   = HTTPRequestBuilder::get()->withUser($this->getUser())->build();
        $variables = ['project_name' => 'guinea-pig'];
        $project   = $this->getProject();

        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->expectException(NotFoundException::class);

        $this->getController(BuildProgramStub::stubInvalidProgram())->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItThrowsAForbiddenExceptionWhenUserCannotAccessProgram(): void
    {
        $request   = HTTPRequestBuilder::get()->withUser($this->getUser())->build();
        $variables = ['project_name' => 'test_project', 'increment_id' => '100'];
        $project   = $this->getProject();

        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->expectException(ForbiddenException::class);

        $this->getController(
            BuildProgramStub::stubInvalidProgramAccess(),
        )
        ->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItThrowsANotFoundExceptionWhenProgramIncrementNotFound(): void
    {
        $request   = HTTPRequestBuilder::get()->withUser($this->getUser())->build();
        $variables = ['project_name' => 'test_project', 'increment_id' => '100'];
        $project   = $this->getProject();

        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->expectException(NotFoundException::class);

        $this->getController(
            BuildProgramStub::stubValidProgram(),
        )
        ->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItDisplaysIterationsPlanning(): void
    {
        $user      = $this->getUser();
        $request   = HTTPRequestBuilder::get()->withUser($user)->build();
        $variables = ['project_name' => 'test_project', 'increment_id' => self::PROGRAM_INCREMENT_ID];
        $project   = $this->getProject();

        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('plan-iterations', self::isInstanceOf(DisplayPlanIterationsPresenter::class));

        $this->getController(BuildProgramStub::stubValidProgram())
            ->process($request, LayoutBuilder::build(), $variables);
    }

    private function getController(BuildProgramStub $build_program_stub): DisplayPlanIterationsController
    {
        return new DisplayPlanIterationsController(
            $this->project_manager,
            $this->template_renderer,
            $build_program_stub,
            BuildProgramFlagsStub::withDefaults(),
            BuildProgramPrivacyStub::withPrivateAccess(),
            BuildProgramBaseInfoStub::withDefault(),
            BuildProgramIncrementInfoStub::withId(self::PROGRAM_INCREMENT_ID),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsVisibleArtifactStub::withVisibleIds(self::PROGRAM_INCREMENT_ID),
            RetrieveProgramUserPrivilegesStub::withProgramAdminUser()
        );
    }

    private function getProject(bool $is_program_management_used = true): \Project
    {
        $project = $this->createMock(\Project::class);

        $project->method('getID')->willReturn(self::PROGRAM_ID);
        $project->method('isPublic')->willReturn(true);
        $project->method('getPublicName')->willReturn('Guinea Pig');
        $project->method('getUnixNameLowerCase')->willReturn('guinea-pig');
        $project->method('getIconUnicodeCodepoint')->willReturn('🐹');

        $project->expects(self::once())
            ->method('usesService')
            ->with(\program_managementPlugin::SERVICE_SHORTNAME)
            ->willReturn($is_program_management_used);

        return $project;
    }

    protected function getUser(): \PFUser
    {
        $user = $this->createMock(\PFUser::class);

        $user->method('getPreference')->willReturn(false);
        $user->method('isAdmin')->willReturn(true);
        $user->method('getId')->willReturn(101);
        $user->method('getName')->willReturn('John');

        return $user;
    }
}
