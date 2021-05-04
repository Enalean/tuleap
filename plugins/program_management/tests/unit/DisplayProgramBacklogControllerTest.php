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

use ForgeAccess;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\BaseLayout;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfigurationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplayProgramBacklogControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementTrackerConfigurationBuilder
     */
    private $configuration_builder;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\TemplateRenderer
     */
    private $template_renderer;

    /**
     * @var DisplayProgramBacklogController
     */
    private $controller;
    /**
     * @var ProgramAdapter
     */
    private $build_program;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectFlagsBuilder
     */
    private $project_flags_builder;

    protected function setUp(): void
    {
        $this->project_manager       = \Mockery::mock(\ProjectManager::class);
        $this->project_flags_builder = \Mockery::mock(ProjectFlagsBuilder::class);
        $this->build_program         = \Mockery::mock(BuildProgram::class);
        $this->template_renderer     = \Mockery::mock(\TemplateRenderer::class);
        $this->configuration_builder = \Mockery::mock(ProgramIncrementTrackerConfigurationBuilder::class);

        $this->controller = new DisplayProgramBacklogController(
            $this->project_manager,
            $this->project_flags_builder,
            $this->build_program,
            $this->template_renderer,
            $this->configuration_builder,
        );
    }

    public function testItThrowsExceptionWhenServiceIsNotAvailable(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->once()->with(\program_managementPlugin::SERVICE_SHORTNAME)->andReturnFalse();
        $this->project_manager->shouldReceive('getProjectByUnixName')->once()->andReturn($project);

        $this->expectException(NotFoundException::class);

        $request   = \Mockery::mock(\HTTPRequest::class);
        $layout    = \Mockery::mock(BaseLayout::class);
        $variables = ['project_name' => 'test_project'];

        $this->controller->process($request, $layout, $variables);
    }

    public function testPreventsAccessWhenProjectIsNotAProgram(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $project->shouldReceive('usesService')->once()->with(\program_managementPlugin::SERVICE_SHORTNAME)->andReturnTrue();
        $this->project_manager->shouldReceive('getProjectByUnixName')->once()->andReturn($project);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andThrow(new ProjectIsNotAProgramException(102));

        $this->expectException(ForbiddenException::class);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn(UserTestBuilder::aUser()->build());
        $variables = ['project_name' => 'test_project'];

        $this->controller->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItDisplayProgramBacklog(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->once()->with(\program_managementPlugin::SERVICE_SHORTNAME)->andReturnTrue();
        $project->shouldReceive('getId')->andReturn(101);
        $project->shouldReceive('isPublic')->andReturn(true);
        $project->shouldReceive('getPublicName')->andReturn('test_project');
        $project->shouldReceive('getUnixNameLowerCase')->andReturn('test_project');
        $this->project_manager->shouldReceive('getProjectByUnixName')->andReturn($project);
        $this->project_flags_builder->shouldReceive('buildProjectFlags')->andReturn([]);

        $request = \Mockery::mock(\HTTPRequest::class);
        $user    = UserTestBuilder::aUser()->build();
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->build_program->shouldReceive('buildExistingProgramProject')
            ->with($project->getID(), $user)
            ->once()->andReturn(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user));

        $layout = \Mockery::mock(BaseLayout::class);
        $layout->shouldReceive('addCssAsset')->once();
        $layout->shouldReceive('header')->once();
        $layout->shouldReceive('includeFooterJavascriptFile')->once();
        $layout->shouldReceive('footer')
            ->once()
            ->with([]);

        $variables = ['project_name' => 'test_project'];

        $this->template_renderer->shouldReceive('renderToPage')->once()
            ->with('program-backlog', \Mockery::type(ProgramBacklogPresenter::class));

        $this->configuration_builder->shouldReceive('build')->andReturn(
            new ProgramIncrementTrackerConfiguration(
                $project->getId(),
                true,
                "Program Increments",
                "program increment"
            )
        );

        $this->controller->process($request, $layout, $variables);
    }
}
