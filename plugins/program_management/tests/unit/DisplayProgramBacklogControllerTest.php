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
use Tuleap\ForgeConfigSandbox;
use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramBacklogPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DisplayProgramBacklogControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ProjectFlagsBuilder
     */
    private $project_flags_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TemplateRenderer
     */
    private $template_renderer;
    private BuildProgram $build_program;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project_manager       = $this->createStub(\ProjectManager::class);
        $this->project_flags_builder = $this->createStub(ProjectFlagsBuilder::class);
        $this->build_program         = BuildProgramStub::stubValidProgram();
        $this->template_renderer     = $this->createMock(\TemplateRenderer::class);
        $this->user                  = UserTestBuilder::buildWithDefaults();

        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
            TrackerReferenceStub::withDefaults()
        );
    }

    public function testItReturnsNotFoundWhenProjectIsNotFoundFromVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $variables = ['project_name' => 'not_found'];

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $variables);
    }

    public function testItThrowsExceptionWhenServiceIsNotAvailable(): void
    {
        $this->mockProject(false);

        $variables = ['project_name' => 'test_project'];

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), $variables);
    }

    public function testPreventsAccessWhenProjectIsATeam(): void
    {
        $this->mockProject();
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $request   = HTTPRequestBuilder::get()->withUser($this->user)->build();
        $variables = ['project_name' => 'test_project'];

        $this->expectException(ForbiddenException::class);

        $this->getController(VerifyIsTeamStub::withValidTeam())->process($request, LayoutBuilder::build(), $variables);
    }

    public function testPreventsAccessWhenProgramIncrementTrackerIsNotVisible(): void
    {
        $this->mockProject();
        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withNotVisibleProgramIncrementTracker();

        $request   = HTTPRequestBuilder::get()->withUser($this->user)->build();
        $variables = ['project_name' => 'test_project'];

        $this->expectException(NotFoundException::class);
        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItDisplayProgramBacklogWhenProgramIncrementHasNoTracker(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->mockProject();
        $this->project_flags_builder->method('buildProjectFlags')->willReturn([]);

        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withNoProgramIncrementTracker();

        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->willReturn(false);
        $user->method('isAdmin')->willReturn(true);
        $user->method('getId')->willReturn(101);
        $user->method('getUserName')->willReturn('John');

        $request   = HTTPRequestBuilder::get()->withUser($user)->build();
        $variables = ['project_name' => 'test_project'];

        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('program-backlog', self::isInstanceOf(ProgramBacklogPresenter::class));

        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItDisplayProgramBacklog(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->mockProject();
        $this->project_flags_builder->method('buildProjectFlags')->willReturn([]);

        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
            TrackerReferenceStub::withDefaults()
        );

        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->willReturn(false);
        $user->method('isAdmin')->willReturn(true);
        $user->method('getId')->willReturn(101);
        $user->method('getUserName')->willReturn('John');

        $request   = HTTPRequestBuilder::get()->withUser($user)->build();
        $variables = ['project_name' => 'test_project'];

        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('program-backlog', self::isInstanceOf(ProgramBacklogPresenter::class));

        $this->getController(VerifyIsTeamStub::withNotValidTeam())->process($request, LayoutBuilder::build(), $variables);
    }

    private function getController(VerifyIsTeam $verify_is_team): DisplayProgramBacklogController
    {
        return new DisplayProgramBacklogController(
            $this->project_manager,
            $this->project_flags_builder,
            $this->build_program,
            $this->template_renderer,
            $this->program_increment_tracker_retriever,
            RetrieveProgramIncrementLabelsStub::buildLabels('Program Increments', 'program_increment'),
            $verify_is_team,
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            VerifyUserCanSubmitStub::userCanSubmit(),
            RetrieveIterationLabelsStub::buildLabels('iteration', 'iteration'),
            RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerReferenceStub::withDefaults())
        );
    }

    private function mockProject(bool $is_service_active = true): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('isPublic')->willReturn(true);
        $project->method('getPublicName')->willReturn('test_project');
        $project->method('getUnixNameLowerCase')->willReturn('test_project');
        $project->method('getIconUnicodeCodepoint')->willReturn('');
        $project->expects(self::once())
            ->method('usesService')
            ->with(ProgramService::SERVICE_SHORTNAME)
            ->willReturn($is_service_active);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
    }
}
