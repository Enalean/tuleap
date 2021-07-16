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
use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Admin\IterationTrackerConfiguration\PotentialIterationTrackerConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\PlannableTrackersConfiguration\PotentialPlannableTrackersConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramAdminPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramIncrementTrackerConfiguration\PotentialProgramIncrementTrackerConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Stub\BuildPotentialTeamsStub;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\BuildProjectUGroupCanPrioritizeItemsPresentersStub;
use Tuleap\ProgramManagement\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Stub\RetrieveTrackerFromProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

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
    private SearchTeamsOfProgramStub $team_searcher;
    private BuildProject $build_project;
    /**
     * @var \EventManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;
    private PotentialProgramIncrementTrackerConfigurationPresentersBuilder $program_increment_tracker_builder;
    private VerifyIsTeamStub $team_verifier;
    private VerifyProjectPermissionStub $permission_verifier;
    private PotentialPlannableTrackersConfigurationPresentersBuilder $plannable_tracker_builder;
    private \HTTPRequest $request;

    protected function setUp(): void
    {
        $this->variables = ['project_name' => 'not_found'];

        $user                                    = UserTestBuilder::aUser()->withRealName('Test User')->build();
        $this->request                           = HTTPRequestBuilder::get()->withUser($user)->build();
        $this->project_manager                   = $this->createStub(\ProjectManager::class);
        $this->template_renderer                 = $this->createMock(\TemplateRenderer::class);
        $this->breadcrumbs_builder               = $this->createStub(ProgramManagementBreadCrumbsBuilder::class);
        $this->build_potential_teams             = BuildPotentialTeamsStub::buildValidPotentialTeamsFromId(
            PotentialTeam::fromId(150, 'team')
        );
        $this->team_searcher                     = SearchTeamsOfProgramStub::buildTeams(150);
        $this->build_project                     = new BuildProjectStub();
        $this->event_manager                     = $this->createMock(\EventManager::class);
        $this->program_increment_tracker_builder = new PotentialProgramIncrementTrackerConfigurationPresentersBuilder();
        $this->plannable_tracker_builder         = new PotentialPlannableTrackersConfigurationPresentersBuilder(RetrievePlannableTrackersStub::buildIds());
        $this->build_program                     = BuildProgramStub::stubValidProgram();
        $this->team_verifier                     = VerifyIsTeamStub::withNotValidTeam();
        $this->permission_verifier               = VerifyProjectPermissionStub::withAdministrator();
    }

    public function testItReturnsNotFoundWhenProjectIsNotFoundFromVariables(): void
    {
        $this->project_manager->method('getProjectByUnixName')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->getController()->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorWhenServiceIsNotActivated(): void
    {
        $this->mockProject(false);

        $this->expectException(NotFoundException::class);
        $this->getController()->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrownAnErrorWhenProjectIsATeam(): void
    {
        $this->mockProject();
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $this->expectException(ForbiddenException::class);

        $this->getController()->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorIfUserIsNotProjectAdmin(): void
    {
        $this->mockProject();
        $this->permission_verifier = VerifyProjectPermissionStub::withNotAdministrator();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You need to be project administrator to access to program administration.');

        $this->getController()->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testThrowAnErrorIfUserCanNotAccessToProgram(): void
    {
        $this->mockProject();
        $this->build_program = BuildProgramStub::stubInvalidProgramAccess();

        $this->expectException(\LogicException::class);

        $this->getController()->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    public function testItDisplayAdminProgram(): void
    {
        $this->mockProject();

        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with('admin', self::isInstanceOf(ProgramAdminPresenter::class));

        $this->breadcrumbs_builder->expects(self::once())->method('build');
        $this->event_manager->expects(self::atLeast(2))->method('dispatch');

        $this->getController()->process($this->request, LayoutBuilder::build(), $this->variables);
    }

    private function getController(): DisplayAdminProgramManagementController
    {
        return new DisplayAdminProgramManagementController(
            $this->project_manager,
            $this->template_renderer,
            $this->breadcrumbs_builder,
            $this->build_potential_teams,
            $this->team_searcher,
            $this->build_project,
            $this->team_verifier,
            $this->build_program,
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build()),
            $this->event_manager,
            RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build()),
            $this->program_increment_tracker_builder,
            $this->plannable_tracker_builder,
            BuildProjectUGroupCanPrioritizeItemsPresentersStub::buildWithIds('102_3'),
            $this->permission_verifier,
            RetrieveProgramIncrementLabelsStub::buildLabels(null, null),
            RetrieveTrackerFromProgramStub::fromTrackerReference(
                TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(80)->withName('Sprint')->build()),
            ),
            new PotentialIterationTrackerConfigurationPresentersBuilder(RetrieveIterationTrackerStub::buildValidTrackerId(10))
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
