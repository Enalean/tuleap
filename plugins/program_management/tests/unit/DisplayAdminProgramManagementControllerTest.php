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
use Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration\ConfigurationErrorPresenterBuilder;
use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Admin\PlannableTrackersConfiguration\PotentialPlannableTrackersConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramAdminPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\IterationCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ProgramIncrementCreatorChecker;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\ProgramManagement\Tests\Stub\AllProgramSearcherStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectUGroupCanPrioritizeItemsPresentersStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTrackersOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationsFeatureActiveStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTrackerSemanticsStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class DisplayAdminProgramManagementControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BuildProgramStub $build_program;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ProgramManagementBreadCrumbsBuilder
     */
    private $breadcrumbs_builder;
    /**
     * @var string[]
     */
    private array $variables;
    private SearchTeamsOfProgramStub $team_searcher;
    private BuildProject $build_project;
    private VerifyIsTeamStub $team_verifier;
    private VerifyProjectPermissionStub $permission_verifier;
    private PotentialPlannableTrackersConfigurationPresentersBuilder $plannable_tracker_builder;
    private \HTTPRequest $request;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\TrackerFactory
     */
    private $tracker_factory;
    private \PFUser $user;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ProgramIncrementCreatorChecker
     */
    private $program_increment_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&IterationCreatorChecker
     */
    private $iteration_checker;

    protected function setUp(): void
    {
        $this->variables = ['project_name' => 'not_found'];

        $this->user                      = UserTestBuilder::buildWithDefaults();
        $this->request                   = HTTPRequestBuilder::get()->withUser($this->user)->build();
        $this->template_renderer         = $this->createMock(\TemplateRenderer::class);
        $this->breadcrumbs_builder       = $this->createStub(ProgramManagementBreadCrumbsBuilder::class);
        $this->team_searcher             = SearchTeamsOfProgramStub::buildTeams(150);
        $this->build_project             = new BuildProjectStub();
        $this->plannable_tracker_builder = new PotentialPlannableTrackersConfigurationPresentersBuilder(RetrievePlannableTrackersStub::buildIds());
        $this->build_program             = BuildProgramStub::stubValidProgram();
        $this->team_verifier             = VerifyIsTeamStub::withNotValidTeam();
        $this->permission_verifier       = VerifyProjectPermissionStub::withAdministrator();
        $this->tracker_factory           = $this->createStub(\TrackerFactory::class);
        $this->program_increment_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $this->iteration_checker         = $this->createStub(IterationCreatorChecker::class);
    }

    private function getController(RetrieveProject $retrieve_project): DisplayAdminProgramManagementController
    {
        $program_tracker = TrackerReferenceStub::withDefaults();

        return new DisplayAdminProgramManagementController(
            $retrieve_project,
            $this->template_renderer,
            $this->breadcrumbs_builder,
            $this->team_searcher,
            $this->build_project,
            $this->team_verifier,
            $this->build_program,
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_tracker),
            RetrieveVisibleIterationTrackerStub::withValidTracker($program_tracker),
            $this->plannable_tracker_builder,
            BuildProjectUGroupCanPrioritizeItemsPresentersStub::buildWithIds('102_3'),
            $this->permission_verifier,
            RetrieveProgramIncrementLabelsStub::buildLabels(null, null),
            SearchTrackersOfProgramStub::withTrackers(
                TrackerReferenceStub::withId(80),
            ),
            RetrieveIterationLabelsStub::buildLabels(null, null),
            AllProgramSearcherStub::buildPrograms(),
            VerifyIterationsFeatureActiveStub::withActiveFeature(),
            new ConfigurationErrorPresenterBuilder(
                new ConfigurationErrorsGatherer(
                    BuildProgramStub::stubValidProgram(),
                    $this->program_increment_checker,
                    $this->iteration_checker,
                    SearchTeamsOfProgramStub::buildTeams(),
                    new BuildProjectStub(),
                    RetrieveUserStub::withUser($this->user)
                ),
                RetrievePlannableTrackersStub::buildIds(1, 2),
                VerifyTrackerSemanticsStub::withAllSemantics(),
                $this->tracker_factory
            ),
            RetrieveUserStub::withGenericUser(),
        );
    }

    public function testItReturnsNotFoundWhenProjectIsNotFoundFromVariables(): void
    {
        $this->expectException(NotFoundException::class);
        $this->getController(RetrieveProjectStub::withValidProjects())->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorWhenServiceIsNotActivated(): void
    {
        $this->expectException(NotFoundException::class);
        $this->getController(RetrieveProjectStub::withValidProjects($this->mockProject(false)))
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrownAnErrorWhenProjectIsATeam(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $this->expectException(ForbiddenException::class);

        $this->getController(RetrieveProjectStub::withValidProjects($this->mockProject()))
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorIfUserIsNotProjectAdmin(): void
    {
        $this->permission_verifier = VerifyProjectPermissionStub::withNotAdministrator();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You need to be project administrator to access to program administration.');

        $this->getController(RetrieveProjectStub::withValidProjects($this->mockProject()))
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorIfUserCanNotAccessToProgram(): void
    {
        $this->build_program = BuildProgramStub::stubInvalidProgramAccess();

        $this->expectException(ForbiddenException::class);

        $this->getController(RetrieveProjectStub::withValidProjects($this->mockProject()))
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testItDisplayAdminProgram(): void
    {
        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('admin', self::isInstanceOf(ProgramAdminPresenter::class));

        $this->breadcrumbs_builder->expects(self::once())->method('build');

        $this->tracker_factory->method('getTrackerById')->willReturn(TrackerTestBuilder::aTracker()->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->withId(1)->withName('Tracker')->build());
        $this->program_increment_checker->method('canCreateAProgramIncrement');
        $this->iteration_checker->method('canCreateAnIteration');

        $this->getController(RetrieveProjectStub::withValidProjects($this->mockProject()))
            ->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    private function mockProject(bool $is_service_active = true): Project
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->expects(self::once())
            ->method('usesService')
            ->with(\program_managementPlugin::SERVICE_SHORTNAME)
            ->willReturn($is_service_active);

        return $project;
    }
}
