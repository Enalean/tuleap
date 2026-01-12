<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_XMLFullStructureExporter;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Controller;
use Planning_RequestValidator;
use PlanningFactory;
use PlanningParameters;
use PlanningPermissionsManager;
use Project;
use ProjectManager;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator;
use Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningControllerTest extends TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    public PlanningFactory&MockObject $planning_factory;
    public ArtifactsInExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private PlanningUpdater&MockObject $planning_updater;
    private Planning_RequestValidator&MockObject $planning_request_validator;
    private EventManager&MockObject $event_manager;
    private Planning_Controller $planning_controller;
    private UpdateIsAllowedChecker&MockObject $root_planning_update_checker;
    private UpdateRequestValidator&MockObject $update_request_validator;
    private BacklogTrackersUpdateChecker&MockObject $backlog_trackers_update_checker;
    private Project $project;
    private \ProjectHistoryDao&MockObject $project_history_dao;
    private \TrackerFactory&MockObject $tracker_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->planning_factory     = $this->createMock(PlanningFactory::class);
        $this->explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);

        $this->event_manager                   = $this->createMock(EventManager::class);
        $this->planning_request_validator      = $this->createMock(Planning_RequestValidator::class);
        $this->planning_updater                = $this->createMock(PlanningUpdater::class);
        $this->root_planning_update_checker    = $this->createMock(UpdateIsAllowedChecker::class);
        $this->update_request_validator        = $this->createMock(UpdateRequestValidator::class);
        $this->backlog_trackers_update_checker = $this->createMock(BacklogTrackersUpdateChecker::class);
        $this->project_history_dao             = $this->createMock(\ProjectHistoryDao::class);
        $this->tracker_factory                 = $this->createMock(\TrackerFactory::class);
    }

    private function getPlanningController(\Tuleap\HTTPRequest $request): Planning_Controller
    {
        $planning_controller              = new class (
            $request,
            $this->planning_factory,
            $this->createMock(ProjectManager::class),
            $this->createMock(AgileDashboard_XMLFullStructureExporter::class),
            $this->createMock(PlanningPermissionsManager::class),
            $this->createMock(ScrumPlanningFilter::class),
            $this->createMock(Tracker_FormElementFactory::class),
            new AgileDashboardCrumbBuilder(),
            new AdministrationCrumbBuilder(),
            new DBTransactionExecutorPassthrough(),
            $this->explicit_backlog_dao,
            $this->planning_updater,
            $this->event_manager,
            $this->planning_request_validator,
            $this->root_planning_update_checker,
            $this->createMock(PlanningEditionPresenterBuilder::class),
            $this->update_request_validator,
            $this->backlog_trackers_update_checker,
            $this->project_history_dao,
            $this->tracker_factory,
            new TestLayout(new LayoutInspector()),
        ) extends Planning_Controller
        {
            #[\Override]
            protected function checkCSRFToken(): void
            {
                // Do nothing, always consider requests as valid
            }
        };
        return $this->planning_controller = $planning_controller;
    }

    public function testItDeletesThePlanningAndRedirectsToTheIndex(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $root_planning = PlanningBuilder::aPlanning(101)->withId(109)->build();
        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->expects($this->once())->method('deletePlanning')->with(42);
        $this->explicit_backlog_dao->expects($this->never())->method('removeExplicitBacklogOfPlanning');

        $this->event_manager->expects($this->once())->method('dispatch');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParam('planning_id', 42)
            ->build();

        $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/agiledashboard/?group_id=101&action=admin'));
        $this->getPlanningController($request)->delete();
    }

    public function testItDeletesExplicitBacklogPlanning(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $root_planning = PlanningBuilder::aPlanning(101)->withId(42)->build();
        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->expects($this->once())->method('deletePlanning')->with(42);
        $this->explicit_backlog_dao->expects($this->once())->method('removeExplicitBacklogOfPlanning')->with(42);

        $this->event_manager->expects($this->once())->method('dispatch');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParam('planning_id', 42)
            ->build();

        $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/agiledashboard/?group_id=101&action=admin'));
        $this->getPlanningController($request)->delete();
    }

    public function testItDoesntDeleteAnythingIfTheUserIsNotAdmin(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->build();

        $GLOBALS['Language']->method('getText')->willReturn('');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->getPlanningController($request)->delete();
    }

    public function testItOnlyUpdateCardWallConfigWhenRequestIsInvalid(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $this->event_manager->expects($this->once())->method('processEvent');
        $this->event_manager->expects($this->once())->method('dispatch');

        $planning = PlanningBuilder::aPlanning(101)->build();
        $this->planning_factory->expects($this->exactly(2))->method('getPlanning')->willReturn($planning);
        $this->planning_factory->expects($this->once())->method('getPlanningTrackerIdsByGroupId')->willReturn([]);
        $this->update_request_validator->expects($this->once())->method('getValidatedPlanning')->willReturn(null);

        $this->planning_updater->expects($this->never())->method('update');
        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParam('planning_id', 1)
            ->build();

        try {
            $this->expectException(LayoutInspectorRedirection::class);
            $this->getPlanningController($request)->update();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }

    public function testItOnlyUpdateCardWallConfigWhenRootPlanningCannotBeUpdated(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $this->event_manager->expects($this->once())->method('processEvent');
        $this->event_manager->expects($this->once())->method('dispatch');

        $planning = PlanningBuilder::aPlanning(101)->build();
        $this->planning_factory->expects($this->exactly(2))->method('getPlanning')->willReturn($planning);
        $this->planning_factory->expects($this->once())->method('getPlanningTrackerIdsByGroupId')->willReturn([]);

        $this->update_request_validator->method('getValidatedPlanning')->willReturn(PlanningParameters::fromArray([]));
        $this->root_planning_update_checker->expects($this->once())->method('checkUpdateIsAllowed')
            ->willThrowException(new TrackerHaveAtLeastOneAddToTopBacklogPostActionException([]));

        $this->planning_updater->expects($this->never())->method('update');
        $this->backlog_trackers_update_checker->method('checkProvidedBacklogTrackersAreValid');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParams(['planning_id' => 1, 'planning' => []])
            ->build();

        try {
            $this->expectException(LayoutInspectorRedirection::class);
            $this->getPlanningController($request)->update();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }

    public function testItOnlyUpdateCardWallConfigWhenPlanningCannotBeUpdated(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $this->event_manager->expects($this->once())->method('processEvent');
        $this->event_manager->expects($this->once())->method('dispatch');

        $planning = PlanningBuilder::aPlanning(101)->build();
        $this->planning_factory->expects($this->exactly(2))->method('getPlanning')->willReturn($planning);
        $this->planning_factory->expects($this->once())->method('getPlanningTrackerIdsByGroupId')->willReturn([]);

        $this->update_request_validator->method('getValidatedPlanning')->willReturn(PlanningParameters::fromArray([]));
        $this->backlog_trackers_update_checker->method('checkProvidedBacklogTrackersAreValid')->willThrowException(
            new TrackersHaveAtLeastOneHierarchicalLinkException('tracker01', 'tracker02')
        );
        $this->root_planning_update_checker->expects($this->never())->method('checkUpdateIsAllowed');

        $this->planning_updater->expects($this->never())->method('update');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParams(['planning_id' => 1, 'planning' => []])
            ->build();

        try {
            $this->expectException(LayoutInspectorRedirection::class);
            $this->getPlanningController($request)->update();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }

    public function testItUpdatesThePlanning(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $planning_parameters = [];

        $this->event_manager->expects($this->once())->method('processEvent');
        $this->event_manager->expects($this->once())->method('dispatch');

        $planning = PlanningBuilder::aPlanning(101)->build();
        $this->planning_factory->expects($this->exactly(2))->method('getPlanning')->willReturn($planning);
        $this->planning_factory->expects($this->once())->method('getPlanningTrackerIdsByGroupId')->willReturn([]);

        $this->update_request_validator->method('getValidatedPlanning')->willReturn(PlanningParameters::fromArray([]));
        $this->root_planning_update_checker->expects($this->once())->method('checkUpdateIsAllowed');
        $this->backlog_trackers_update_checker->method('checkProvidedBacklogTrackersAreValid');
        $this->project_history_dao->expects($this->once())->method('addHistory');

        $this->tracker_factory->method('getTrackerById')->willReturn(TrackerTestBuilder::aTracker()->withName('lorem')->build());

        $this->planning_updater->expects($this->once())->method('update');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParams(['planning_id' => 1, 'planning' => []])
            ->build();

        try {
            $this->expectException(LayoutInspectorRedirection::class);
            $this->getPlanningController($request)->update();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }

    public function testItShowsAnErrorMessageAndRedirectsBackToTheCreationForm(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $this->planning_request_validator->method('isValid')->willReturn(false);

        $this->planning_factory->expects($this->never())->method('createPlanning');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->build();

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/agiledashboard/?group_id=101&action=new'));
            $this->getPlanningController($request)->create();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }

    public function testItCreatesThePlanningAndRedirectsToTheIndex(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withAdministratorOf($this->project)
            ->build();

        $planning_parameters = [
            PlanningParameters::NAME                         => 'Release Planning',
            PlanningParameters::PLANNING_TRACKER_ID          => '3',
            PlanningParameters::BACKLOG_TITLE                => 'Release Backlog',
            PlanningParameters::PLANNING_TITLE               => 'Sprint Plan',
            PlanningParameters::BACKLOG_TRACKER_IDS          => ['2'],
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE => ['2', '3'],
        ];

        $this->planning_request_validator->method('isValid')->willReturn(true);

        $this->planning_factory->expects($this->once())->method('createPlanning');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->withParam('planning', $planning_parameters)
            ->build();

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/agiledashboard/?group_id=101&action=admin'));
            $this->getPlanningController($request)->create();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }

    public function testItDoesntCreateAnythingIfTheUserIsNotAdmin(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('');

        $user = UserTestBuilder::anActiveUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $this->planning_factory->expects($this->never())->method('createPlanning');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withProject($this->project)
            ->build();

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/agiledashboard/?group_id=101'));
            $this->getPlanningController($request)->create();
        } finally {
            self::assertCount(1, $this->global_response->inspector->getFeedback());
        }
    }
}
