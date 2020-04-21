<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_OneStepCreation_OneStepCreationRequest;
use ProjectManager;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;

final class TemplateFromProjectForCreationTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->user            = Mockery::mock(PFUser::class);
    }

    public function testGetTemplateFromProjectForCreationFromRESTRepresentation(): void
    {
        $representation = new ProjectPostRepresentation();
        $representation->template_id = 123;

        $expected_project = $this->mockForSuccessfulValidation($representation->template_id);

        $template_from_project_for_creation = TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );

        $this->assertEquals($expected_project, $template_from_project_for_creation->getProject());
    }

    public function testGetTemplateFromProjectForCreationFromRegisterCreationRequest(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->andReturn('123');
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);

        $expected_project = $this->mockForSuccessfulValidation(123);

        $template_from_project_for_creation = TemplateFromProjectForCreation::fromRegisterCreationRequest(
            $request,
            $this->project_manager
        );

        $this->assertSame($expected_project, $template_from_project_for_creation->getProject());
    }

    public function testGetTemplateFromProjectForCreationFromSOAPServer(): void
    {
        $expected_project = $this->mockForSuccessfulValidation(123);

        $template_from_project_for_creation = TemplateFromProjectForCreation::fromSOAPServer(
            123,
            $this->user,
            $this->project_manager
        );

        $this->assertSame($expected_project, $template_from_project_for_creation->getProject());
    }

    private function mockForSuccessfulValidation(int $project_id): Project
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($project_id);
        $project->shouldReceive('isError')->andReturn(false);
        $project->shouldReceive('isActive')->andReturn(false);
        $project->shouldReceive('isTemplate')->andReturn(true);

        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);

        return $project;
    }

    public function testGetTemplateFromProjectForCreationFromGlobalProjectTemplate(): void
    {
        $this->assertEquals(Project::ADMIN_PROJECT_ID, TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate()->getProject()->getID());
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenTemplateIDIsNotPresent(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->andReturn(null);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);

        $this->expectException(ProjectIDTemplateNotProvidedException::class);
        TemplateFromProjectForCreation::fromRegisterCreationRequest(
            $request,
            $this->project_manager
        );
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenProjectToUseAsTemplateDoesNotExist(): void
    {
        $representation = new ProjectPostRepresentation();
        $representation->template_id = 404;

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($representation->template_id);
        $project->shouldReceive('isError')->andReturn(true);

        $this->project_manager->shouldReceive('getProject')->with($representation->template_id)->andReturn($project);

        $this->expectException(ProjectTemplateIDInvalidException::class);
        TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenProjectToUseAsTemplateIsNotActiveAndNotMarkedAsTemplate(): void
    {
        $representation = new ProjectPostRepresentation();
        $representation->template_id = 124;

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($representation->template_id);
        $project->shouldReceive('isError')->andReturn(false);
        $project->shouldReceive('isActive')->andReturn(false);
        $project->shouldReceive('isTemplate')->andReturn(false);

        $this->project_manager->shouldReceive('getProject')->with($representation->template_id)->andReturn($project);

        $this->expectException(ProjectTemplateNotActiveException::class);
        TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenProjectToUseIsActiveButTheUserRequestingTheCreationIsNotOneOfTheProjectAdmins(): void
    {
        $representation = new ProjectPostRepresentation();
        $representation->template_id = 125;

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($representation->template_id);
        $project->shouldReceive('isError')->andReturn(false);
        $project->shouldReceive('isActive')->andReturn(true);
        $project->shouldReceive('isTemplate')->andReturn(false);

        $this->project_manager->shouldReceive('getProject')->with($representation->template_id)->andReturn($project);
        $this->user->shouldReceive('isAdmin')->with($representation->template_id)->andReturn(false);
        $this->user->shouldReceive('getId')->andReturn(102);

        $this->expectException(InsufficientPermissionToUseProjectAsTemplateException::class);
        TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );
    }
}
