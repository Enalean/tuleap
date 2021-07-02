<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;
use Tuleap\ProgramManagement\Stub\BuildPotentialTeamsStub;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

final class DisplayAdminProgramManagementControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;
    private BuildProgramStub $build_program;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramManagementBreadCrumbsBuilder
     */
    private $breadcrumbs_builder;
    /**
     * @var string[]
     */
    private array $variables;
    private BuildPotentialTeamsStub $build_potential_teams;

    protected function setUp(): void
    {
        $this->variables = ['project_name' => 'not_found'];

        $this->project_manager       = $this->createStub(\ProjectManager::class);
        $this->build_program         = BuildProgramStub::stubValidProgram();
        $this->template_renderer     = $this->createMock(\TemplateRenderer::class);
        $this->breadcrumbs_builder   = $this->createStub(ProgramManagementBreadCrumbsBuilder::class);
        $this->build_potential_teams = BuildPotentialTeamsStub::buildValidPotentialTeamsFromId(PotentialTeam::fromId(150, 'team'));
    }

    public function testItReturnsNotFoundWhenProjectIsNotFoundFromVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->getController()->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorWhenServiceIsNotActivated(): void
    {
        $this->mockProject(false);

        $this->expectException(NotFoundException::class);
        $this->getController()->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $this->variables);
    }

    public function testThrownAnErrorWhenProjectIsNotProgram(): void
    {
        $this->mockProject();
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('The program management service can only be used in a project defined as a program.');

        $this->getController()->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorIfUserIsNotProjectAdmin(): void
    {
        $this->mockProject();

        $user = $this->createMock(\PFUser::class);
        $user->method('isAdmin')->willReturn(false);

        $request = HTTPRequestBuilder::get()->withUser($user)->build();
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You need to be project administrator to access to program administration.');

        $this->getController()->process($request, LayoutBuilder::build(), $this->variables);
    }

    public function testItDisplayAdminProgram(): void
    {
        $this->mockProject();

        $user = $this->createMock(\PFUser::class);
        $user->method('isAdmin')->willReturn(true);

        $request = HTTPRequestBuilder::get()->withUser($user)->build();

        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('admin', self::isInstanceOf(ProgramAdminPresenter::class));

        $this->breadcrumbs_builder->expects(self::once())->method('build');

        $this->getController()->process($request, LayoutBuilder::build(), $this->variables);
    }

    private function getController(): DisplayAdminProgramManagementController
    {
        return new DisplayAdminProgramManagementController(
            $this->project_manager,
            $this->template_renderer,
            $this->build_program,
            $this->breadcrumbs_builder,
            $this->build_potential_teams
        );
    }

    private function mockProject(bool $is_service_active = true): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->expects(self::once())
            ->method('usesService')
            ->with(\program_managementPlugin::SERVICE_SHORTNAME)
            ->willReturn($is_service_active);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
    }
}
