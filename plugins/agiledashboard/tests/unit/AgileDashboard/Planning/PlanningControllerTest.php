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
use Codendi_Request;
use EventManager;
use Exception;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Planning_Controller;
use PlanningFactory;
use PlanningParameters;
use PlanningPermissionsManager;
use Project;
use ProjectManager;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator;
use Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class PlanningControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    public $planning_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    public $explicit_backlog_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningUpdater
     */
    private $planning_updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Planning_RequestValidator
     */
    private $planning_request_validator;
    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Codendi_Request|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    /**
     * @var Planning_Controller
     */
    private $planning_controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UpdateIsAllowedChecker
     */
    private $root_planning_update_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UpdateRequestValidator
     */
    private $update_request_validator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BacklogTrackersUpdateChecker
     */
    private $backlog_trackers_update_checker;

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR . '/../../..');

        $this->request = Mockery::mock(Codendi_Request::class);
        $project       = Mockery::mock(Project::class);
        $this->request->shouldReceive('getProject')->andReturn($project);
        $project->shouldReceive('getID')->andReturn(101);

        $GLOBALS['Response'] = Mockery::mock(BaseLayout::class);

        $this->planning_factory     = Mockery::mock(PlanningFactory::class);
        $this->explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->event_manager                   = Mockery::mock(EventManager::class);
        $this->planning_request_validator      = Mockery::mock(\Planning_RequestValidator::class);
        $this->planning_updater                = Mockery::mock(PlanningUpdater::class);
        $this->scrum_mono_milestone_checker    = Mockery::mock(ScrumForMonoMilestoneChecker::class);
        $this->root_planning_update_checker    = Mockery::mock(UpdateIsAllowedChecker::class);
        $this->update_request_validator        = Mockery::mock(UpdateRequestValidator::class);
        $this->backlog_trackers_update_checker = $this->createMock(BacklogTrackersUpdateChecker::class);

        $this->planning_controller = new Planning_Controller(
            $this->request,
            $this->planning_factory,
            Mockery::mock(ProjectManager::class),
            Mockery::mock(AgileDashboard_XMLFullStructureExporter::class),
            Mockery::mock(PlanningPermissionsManager::class),
            $this->scrum_mono_milestone_checker,
            Mockery::mock(ScrumPlanningFilter::class),
            Mockery::mock(Tracker_FormElementFactory::class),
            Mockery::mock(AgileDashboardCrumbBuilder::class),
            Mockery::mock(AdministrationCrumbBuilder::class),
            new DBTransactionExecutorPassthrough(),
            $this->explicit_backlog_dao,
            $this->planning_updater,
            $this->event_manager,
            $this->planning_request_validator,
            $this->root_planning_update_checker,
            Mockery::mock(PlanningEditionPresenterBuilder::class),
            $this->update_request_validator,
            $this->backlog_trackers_update_checker,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function testItDeletesThePlanningAndRedirectsToTheIndex(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();
        $this->request->shouldReceive('getCurrentUser')->twice()->andReturn($user);
        $this->request->shouldReceive('get')->once()->withArgs(['planning_id'])->andReturn(42);
        $this->event_manager->shouldReceive('dispatch')->once();

        $root_planning = Mockery::mock(\Planning::class);
        $root_planning->shouldReceive('getId')->andReturn(109);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($root_planning);
        $this->planning_factory->shouldReceive('deletePlanning')->once()->withArgs([42]);
        $this->explicit_backlog_dao->shouldReceive('removeExplicitBacklogOfPlanning')->never();

        $GLOBALS['Response']->shouldReceive('redirect')->once()->withArgs(
            ['/plugins/agiledashboard/?group_id=101&action=admin']
        );

        $this->planning_controller->delete();
    }

    public function testItDeletesExplicitBacklogPlanning(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();
        $this->request->shouldReceive('getCurrentUser')->twice()->andReturn($user);
        $this->request->shouldReceive('get')->once()->withArgs(['planning_id'])->andReturn(42);
        $this->event_manager->shouldReceive('dispatch')->once();

        $root_planning = Mockery::mock(\Planning::class);
        $root_planning->shouldReceive('getId')->andReturn(42);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($root_planning);
        $this->planning_factory->shouldReceive('deletePlanning')->once()->withArgs([42]);
        $this->explicit_backlog_dao->shouldReceive('removeExplicitBacklogOfPlanning')->once()->withArgs([42]);

        $GLOBALS['Response']->shouldReceive('redirect')->once()->withArgs(
            ['/plugins/agiledashboard/?group_id=101&action=admin']
        );

        $this->planning_controller->delete();
    }

    public function testItDoesntDeleteAnythingIfTheUserIsNotAdmin(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnFalse();
        $user->shouldReceive('isSuperUser')->once()->andReturnFalse();
        $this->request->shouldReceive('getCurrentUser')->once()->andReturn($user);
        $this->request->shouldReceive('get')->never()->withArgs(['planning_id']);

        $this->expectException(\Exception::class);
        $this->planning_controller->delete();
    }

    public function testItOnlyUpdateCardWallConfigWhenRequestIsInvalid(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();

        $this->request->shouldReceive('getCurrentUser')->once()->andReturn($user);
        $this->request->shouldReceive('get')->withArgs(['planning_id'])->andReturn(1);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $planning = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTracker')->once();
        $this->planning_factory->shouldReceive('getPlanning')->times(2)->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')
            ->once()
            ->andReturn([]);
        $this->update_request_validator->shouldReceive('getValidatedPlanning')
            ->once()
            ->andReturnNull();

        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->planning_updater->shouldNotReceive('update');

        $this->planning_controller->update();
    }

    public function testItOnlyUpdateCardWallConfigWhenRootPlanningCannotBeUpdated(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();

        $this->request->shouldReceive('getCurrentUser')->twice()->andReturn($user);
        $this->request->shouldReceive('get')->withArgs(['planning_id'])->andReturn(1);

        $planning_parameters = [];
        $this->request->shouldReceive('get')->withArgs(['planning'])->andReturn($planning_parameters);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $planning = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTracker')->once();
        $this->planning_factory->shouldReceive('getPlanning')->times(2)->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')
            ->once()
            ->andReturn([]);

        $this->update_request_validator->shouldReceive('getValidatedPlanning')->andReturn(PlanningParameters::fromArray([]));
        $this->root_planning_update_checker->shouldReceive('checkUpdateIsAllowed')
            ->once()
            ->andThrow(new TrackerHaveAtLeastOneAddToTopBacklogPostActionException([]));

        $this->planning_updater->shouldNotReceive('update');
        $this->backlog_trackers_update_checker->method("checkProvidedBacklogTrackersAreValid");

        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->planning_controller->update();
    }

    public function testItOnlyUpdateCardWallConfigWhenPlanningCannotBeUpdated(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();

        $this->request->shouldReceive('getCurrentUser')->twice()->andReturn($user);
        $this->request->shouldReceive('get')->withArgs(['planning_id'])->andReturn(1);

        $planning_parameters = [];
        $this->request->shouldReceive('get')->withArgs(['planning'])->andReturn($planning_parameters);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $planning = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTracker')->once();
        $this->planning_factory->shouldReceive('getPlanning')->times(2)->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')
            ->once()
            ->andReturn([]);

        $this->update_request_validator->shouldReceive('getValidatedPlanning')->andReturn(PlanningParameters::fromArray([]));
        $this->backlog_trackers_update_checker->method("checkProvidedBacklogTrackersAreValid")->willThrowException(
            new TrackersHaveAtLeastOneHierarchicalLinkException("tracker01", "tracker02")
        );
        $this->root_planning_update_checker->shouldNotReceive('checkUpdateIsAllowed');

        $this->planning_updater->shouldNotReceive('update');

        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->planning_controller->update();
    }

    public function testItUpdatesThePlanning(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();

        $this->request->shouldReceive('getCurrentUser')->twice()->andReturn($user);
        $this->request->shouldReceive('get')->withArgs(['planning_id'])->andReturn(1);

        $planning_parameters = [];
        $this->request->shouldReceive('get')->withArgs(['planning'])->andReturn($planning_parameters);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $planning = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTracker')->once();
        $this->planning_factory->shouldReceive('getPlanning')->times(2)->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')
            ->once()
            ->andReturn([]);

        $this->update_request_validator->shouldReceive('getValidatedPlanning')->andReturn(PlanningParameters::fromArray([]));
        $this->root_planning_update_checker->shouldReceive('checkUpdateIsAllowed')->once();
        $this->backlog_trackers_update_checker->method("checkProvidedBacklogTrackersAreValid");

        $this->planning_updater->shouldReceive('update')->once();

        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->planning_controller->update();
    }

    public function testItShowsAnErrorMessageAndRedirectsBackToTheCreationForm(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();

        $this->request->shouldReceive('getCurrentUser')->andReturn($user);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);
        $this->request->shouldReceive('getProject')->andReturn($project);

        $this->planning_request_validator->shouldReceive('isValid')->andReturnFalse();

        $this->scrum_mono_milestone_checker->shouldReceive(
            'doesScrumMonoMilestoneConfigurationAllowsPlanningCreation'
        )->andReturnTrue();

        $this->planning_factory->shouldReceive('createPlanning')->never();

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();
        $GLOBALS['Response']->shouldReceive('redirect')->with('/plugins/agiledashboard/?group_id=101&action=new')->once(
        );

        $this->planning_controller->create();
    }

    public function testItCreatesThePlanningAndRedirectsToTheIndex(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnTrue();

        $planning_parameters = [
            PlanningParameters::NAME                         => 'Release Planning',
            PlanningParameters::PLANNING_TRACKER_ID          => '3',
            PlanningParameters::BACKLOG_TITLE                => 'Release Backlog',
            PlanningParameters::PLANNING_TITLE               => 'Sprint Plan',
            PlanningParameters::BACKLOG_TRACKER_IDS          => [
                '2',
            ],
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE => [
                '2',
                '3',
            ],
        ];

        $this->request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->request->shouldReceive('get')->with('planning')->andReturn($planning_parameters);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);
        $this->request->shouldReceive('getProject')->andReturn($project);

        $this->planning_request_validator->shouldReceive('isValid')->andReturnTrue();

        $this->scrum_mono_milestone_checker->shouldReceive(
            'doesScrumMonoMilestoneConfigurationAllowsPlanningCreation'
        )->andReturnTrue();

        $this->planning_factory->shouldReceive('createPlanning')->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();
        $GLOBALS['Response']->shouldReceive('redirect')->with(
            '/plugins/agiledashboard/?group_id=101&action=admin'
        )->once();

        $this->planning_controller->create();
    }

    public function testItDoesntCreateAnythingIfTheUserIsNotAdmin(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->once()->andReturnFalse();
        $user->shouldReceive('isSuperUser')->once()->andReturnFalse();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);

        $this->request->shouldReceive('getProject')->andReturn($project);
        $this->request->shouldReceive('getCurrentUser')->andReturn($user);

        $this->planning_factory->shouldReceive('createPlanning')->never();

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();
        $GLOBALS['Response']->shouldReceive('redirect')->with('/plugins/agiledashboard/?group_id=101')->once();

        $this->expectException(Exception::class);

        $this->planning_controller->create();
    }
}
