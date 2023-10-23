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

use Project;
use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramAdminPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\PotentialPlannableTrackersConfigurationBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ProjectUGroupCanPrioritizeItemsBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AllProgramSearcherStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIsAProgramOrUsedInPlanCheckerStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildUGroupRepresentationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUGroupsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProjectsUserIsAdminStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTrackersOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProjectUsedInPlanStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsSynchronizationPendingStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTeamSynchronizationHasErrorStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTrackerSemanticsStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplayAdminProgramManagementControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TEAM_ID              = 150;
    private const ITERATION_TRACKER_ID = 96;
    private BuildProgramStub $build_program;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProgramManagementBreadCrumbsBuilder
     */
    private $breadcrumbs_builder;
    /**
     * @var string[]
     */
    private array $variables;
    private VerifyIsTeamStub $team_verifier;
    private VerifyProjectPermissionStub $permission_verifier;
    private PotentialPlannableTrackersConfigurationBuilder $plannable_tracker_builder;
    private \HTTPRequest $request;
    private \PFUser $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        $this->variables = ['project_name' => 'not_found'];

        $this->user                      = UserTestBuilder::buildWithDefaults();
        $this->request                   = HTTPRequestBuilder::get()->withUser($this->user)->build();
        $this->template_renderer         = $this->createMock(\TemplateRenderer::class);
        $this->breadcrumbs_builder       = $this->createMock(ProgramManagementBreadCrumbsBuilder::class);
        $this->plannable_tracker_builder = new PotentialPlannableTrackersConfigurationBuilder(
            RetrievePlannableTrackersStub::build(TrackerReferenceStub::withDefaults())
        );
        $this->build_program             = BuildProgramStub::stubValidProgram();
        $this->team_verifier             = VerifyIsTeamStub::withNotValidTeam();
        $this->permission_verifier       = VerifyProjectPermissionStub::withAdministrator();
        $this->project_manager           = $this->createMock(\ProjectManager::class);
    }

    private function getController(): DisplayAdminProgramManagementController
    {
        $program_tracker                 = TrackerReferenceStub::withDefaults();
        $pproject_ugroups_can_prioritize = new ProjectUGroupCanPrioritizeItemsBuilder(
            RetrieveUGroupsStub::buildWithUGroups(),
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds(3, 195),
            BuildUGroupRepresentationStub::build()
        );

        $teams_searcher = SearchTeamsOfProgramStub::withTeamIds(self::TEAM_ID);

        return new DisplayAdminProgramManagementController(
            SearchProjectsUserIsAdminStub::buildWithoutProject(),
            $this->template_renderer,
            $this->breadcrumbs_builder,
            $teams_searcher,
            RetrieveProjectReferenceStub::withProjects(ProjectReferenceStub::withId(self::TEAM_ID)),
            $this->team_verifier,
            $this->build_program,
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_tracker),
            RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerReferenceStub::withId(self::ITERATION_TRACKER_ID)),
            $this->plannable_tracker_builder,
            $pproject_ugroups_can_prioritize,
            $this->permission_verifier,
            RetrieveProgramIncrementLabelsStub::buildLabels(null, null),
            SearchTrackersOfProgramStub::withTrackers(
                TrackerReferenceStub::withId(80),
            ),
            RetrieveIterationLabelsStub::buildLabels(null, null),
            AllProgramSearcherStub::buildPrograms(),
            new ConfigurationErrorsGatherer(
                BuildProgramStub::stubValidProgram(),
                ProgramIncrementCreatorCheckerBuilder::build(),
                IterationCreatorCheckerBuilder::build(),
                $teams_searcher,
                RetrieveProjectReferenceStub::withProjects(
                    ProjectReferenceStub::withId(self::TEAM_ID),
                    ProjectReferenceStub::withId(self::TEAM_ID),
                )
            ),
            $this->project_manager,
            SearchOpenProgramIncrementsStub::withProgramIncrements(ProgramIncrementBuilder::buildWithId(209)),
            SearchMirrorTimeboxesFromProgramStub::buildWithMissingMirror(),
            VerifyIsSynchronizationPendingStub::withoutOnGoingSynchronization(),
            SearchVisibleTeamsOfProgramStub::withTeamIds(self::TEAM_ID),
            VerifyTeamSynchronizationHasErrorStub::buildWithoutError(),
            RetrievePlannableTrackersStub::build(TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2)),
            VerifyTrackerSemanticsStub::withAllSemantics(),
            ProjectIsAProgramOrUsedInPlanCheckerStub::stubValidProgram(),
            VerifyIsProjectUsedInPlanStub::withProjectUsedInPlan()
        );
    }

    public function testItReturnsNotFoundWhenProjectIsNotFoundFromVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(false);
        $this->expectException(NotFoundException::class);
        $this->getController()->process(
            $this->request,
            LayoutBuilder::build(),
            $this->variables
        );
    }

    public function testThrowAnErrorWhenServiceIsNotActivated(): void
    {
        $project = $this->mockProject(false);
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->expectException(NotFoundException::class);
        $this->getController()
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrownAnErrorWhenProjectIsATeam(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $this->expectException(ForbiddenException::class);

        $project = $this->mockProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->getController()
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorIfUserIsNotProjectAdmin(): void
    {
        $this->permission_verifier = VerifyProjectPermissionStub::withNotAdministrator();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You need to be project administrator to access to program administration.');

        $project = $this->mockProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);
        $this->getController()
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testItDisplayAdminProgram(): void
    {
        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('admin', self::isInstanceOf(ProgramAdminPresenter::class));

        $this->breadcrumbs_builder->expects(self::once())->method('build');

        $project = $this->mockProject();
        $this->project_manager->method('getProjectByUnixName')->willReturn($project);

        $this->getController()
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    private function mockProject(bool $is_service_active = true): Project
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('getPublicName')->willReturn("A project");
        $project->method('getUnixNameLowerCase')->willReturn("a-project");
        $project->method('getUrl')->willReturn("/a-project/");
        $project->method('getIconUnicodeCodepoint')->willReturn("");
        $project->expects(self::once())
            ->method('usesService')
            ->with(ProgramService::SERVICE_SHORTNAME)
            ->willReturn($is_service_active);

        return $project;
    }
}
