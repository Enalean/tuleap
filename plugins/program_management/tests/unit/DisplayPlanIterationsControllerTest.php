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
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class DisplayPlanIterationsControllerTest extends TestCase
{
    /**
     * @var Stub&\ProjectManager
     */
    private $project_manager;
    /**
     * @var MockObject&\TemplateRenderer
     */
    private $template_renderer;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project_manager   = $this->createStub(\ProjectManager::class);
        $this->template_renderer = $this->createMock(\TemplateRenderer::class);
        $this->user              = UserTestBuilder::buildWithDefaults();
    }

    public function testItThrowsNotFoundExceptionWhenProjectIsNotFoundInVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $variables = ['project_name' => 'unknown-project-unix-name'];

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withNotValidTeam())
            ->process(
                HTTPRequestBuilder::get()->build(),
                LayoutBuilder::build(),
                $variables
            );
    }

    public function testItThrowsNotFoundWhenServiceIsNotAvailable(): void
    {
        $this->mockProject(false);

        $variables = ['project_name' => 'guinea-pig'];

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $variables);
    }

    public function testPreventsAccessWhenProjectIsATeam(): void
    {
        $this->mockProject();

        $request   = HTTPRequestBuilder::get()->withUser($this->user)->build();
        $variables = ['project_name' => 'guinea-pig'];

        $this->expectException(ForbiddenException::class);

        $this->getController(VerifyIsTeamStub::withValidTeam())->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItDisplaysIterationsPlanning(): void
    {
        $this->mockProject();

        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('plan-iterations', []);

        $user      = $this->createMock(\PFUser::class);
        $request   = HTTPRequestBuilder::get()->withUser($user)->build();
        $variables = ['project_name' => 'test_project'];

        $this->getController(VerifyIsTeamStub::withNotValidTeam())
            ->process($request, LayoutBuilder::build(), $variables);
    }

    private function getController(VerifyIsTeamStub $verify_is_team_stub): DisplayPlanIterationsController
    {
        return new DisplayPlanIterationsController(
            $this->project_manager,
            $this->template_renderer,
            $verify_is_team_stub
        );
    }

    private function mockProject(bool $is_service_active = true): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('isPublic')->willReturn(true);
        $project->method('getPublicName')->willReturn('Guinea Pig');
        $project->method('getUnixNameLowerCase')->willReturn('guinea-pig');
        $project->method('getIconUnicodeCodepoint')->willReturn('🐹');
        $project->expects(self::once())
            ->method('usesService')
            ->with(\program_managementPlugin::SERVICE_SHORTNAME)
            ->willReturn($is_service_active);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
    }
}
