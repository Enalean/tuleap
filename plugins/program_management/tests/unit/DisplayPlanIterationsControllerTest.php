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

declare(strict_types=1);

namespace Tuleap\ProgramManagement;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TitleValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\IterationView\DisplayPlanIterationsPresenter;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramBaseInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramPrivacyStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserPreferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserIsProgramAdminStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DisplayPlanIterationsControllerTest extends TestCase
{
    private const PROGRAM_ID           = 101;
    private const PROGRAM_INCREMENT_ID = 1260;
    private const ITERATION_TRACKER_ID = 224;

    /**
     * @var Stub&\ProjectManager
     */
    private $project_manager;
    /**
     * @var MockObject&\TemplateRenderer
     */
    private $template_renderer;
    private BuildProgramStub $program_builder;
    private string $variable_program_increment_id;
    private string $variable_project_name;
    private RetrieveVisibleIterationTrackerStub $iteration_tracker_retriever;

    protected function setUp(): void
    {
        $this->project_manager               = $this->createStub(\ProjectManager::class);
        $this->template_renderer             = $this->createMock(\TemplateRenderer::class);
        $this->program_builder               = BuildProgramStub::stubValidProgram();
        $this->variable_program_increment_id = (string) self::PROGRAM_INCREMENT_ID;
        $this->variable_project_name         = 'test_project';
        $this->iteration_tracker_retriever   = RetrieveVisibleIterationTrackerStub::withValidTracker(
            TrackerReferenceStub::withId(self::ITERATION_TRACKER_ID)
        );
    }

    private function processController(): void
    {
        $controller = new DisplayPlanIterationsController(
            $this->project_manager,
            $this->template_renderer,
            $this->program_builder,
            BuildProgramFlagsStub::withDefaults(),
            BuildProgramPrivacyStub::withPrivateAccess(),
            BuildProgramBaseInfoStub::withDefault(),
            BuildProgramIncrementInfoStub::withId(self::PROGRAM_INCREMENT_ID),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsVisibleArtifactStub::withVisibleIds(self::PROGRAM_INCREMENT_ID),
            VerifyUserIsProgramAdminStub::withProgramAdminUser(),
            $this->iteration_tracker_retriever,
            RetrieveIterationLabelsStub::buildLabels('Cycles', 'cycle'),
            RetrieveUserPreferenceStub::withNameAndValue('accessibility_mode', '1'),
            new TitleValueRetriever(
                RetrieveFullArtifactStub::withArtifact(
                    ArtifactTestBuilder::anArtifact(1)
                        ->withTitle('Title')
                        ->build()
                )
            )
        );

        $user      = $this->getUser();
        $request   = HTTPRequestBuilder::get()->withUser($user)->build();
        $variables = [
            'project_name' => $this->variable_project_name,
            'increment_id' => $this->variable_program_increment_id,
        ];

        $controller->process($request, LayoutBuilder::build(), $variables);
    }

    public function testItThrowsNotFoundExceptionWhenProjectIsNotFoundInVariables(): void
    {
        $this->variable_project_name = 'unknown-project-unix-name';
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->processController();
    }

    public function testItThrowsNotFoundWhenServiceIsNotAvailable(): void
    {
        $project = $this->getProject(false);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->expectException(NotFoundException::class);
        $this->processController();
    }

    public function testPreventsAccessWhenProjectIsATeam(): void
    {
        $project = $this->getProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->program_builder = BuildProgramStub::stubInvalidProgram();

        $this->expectException(NotFoundException::class);
        $this->processController();
    }

    public function testItThrowsAForbiddenExceptionWhenUserCannotAccessProgram(): void
    {
        $project = $this->getProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->program_builder = BuildProgramStub::stubInvalidProgramAccess();

        $this->expectException(ForbiddenException::class);
        $this->processController();
    }

    public function testItThrowsANotFoundExceptionWhenProgramIncrementNotFound(): void
    {
        $project = $this->getProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->variable_program_increment_id = '100';

        $this->expectException(NotFoundException::class);
        $this->processController();
    }

    public function testItThrowsANotFoundExceptionWhenProgramHasNoIterationTrackerDefined(): void
    {
        $project = $this->getProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->iteration_tracker_retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();

        $this->expectException(NotFoundException::class);
        $this->processController();
    }

    public function testItDisplaysIterationsPlanning(): void
    {
        $project = $this->getProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('plan-iterations', self::isInstanceOf(DisplayPlanIterationsPresenter::class));
        $this->processController();
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
            ->with(ProgramService::SERVICE_SHORTNAME)
            ->willReturn($is_program_management_used);

        return $project;
    }

    protected function getUser(): \PFUser
    {
        $user = $this->createMock(\PFUser::class);

        $user->method('getPreference')->willReturn(false);
        $user->method('isAdmin')->willReturn(true);
        $user->method('getId')->willReturn(101);
        $user->method('getUserName')->willReturn('John');

        return $user;
    }
}
