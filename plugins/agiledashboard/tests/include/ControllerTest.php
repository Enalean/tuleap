<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\AdminController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeChecker;

require_once(dirname(__FILE__).'/../../../tracker/tests/builders/all.php');

require_once TRACKER_BASE_DIR .'/Tracker/TrackerManager.class.php';
require_once dirname(__FILE__).'/../bootstrap.php';

abstract class Planning_Controller_BaseTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');
        $this->group_id               = 123;
        $this->project                = stub('Project')->getID()->returns($this->group_id);
        $this->project_manager        = stub('ProjectManager')->getProject($this->group_id)->returns($this->project);
        $this->current_user           = stub('PFUser')->getId()->returns(666);
        $this->request                = aRequest()->withProjectManager($this->project_manager)->with(
            'group_id',
            "$this->group_id"
        )->withUser($this->current_user)->build();
        $this->planning_factory       = mock(PlanningFactory::class);
        $this->mono_milestone_checker = mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');
        $this->scrum_planning_filter  = mock('Tuleap\AgileDashboard\Planning\ScrumPlanningFilter');
        $service_crumb_builder        = mock(AgileDashboardCrumbBuilder::class);
        $admin_crumb_builder          = mock(AdministrationCrumbBuilder::class);

        $this->planning_request_validator = Mockery::mock(Planning_RequestValidator::class);
        $this->planning_controller  = new Planning_Controller(
            $this->request,
            $this->planning_factory,
            mock('Planning_MilestoneFactory'),
            mock('ProjectManager'),
            mock('AgileDashboard_XMLFullStructureExporter'),
            '/path/to/plugin',
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_ConfigurationManager'),
            mock('AgileDashboard_KanbanFactory'),
            mock('PlanningPermissionsManager'),
            $this->mono_milestone_checker,
            $this->scrum_planning_filter,
            mock('Tracker_FormElementFactory'),
            $service_crumb_builder,
            $admin_crumb_builder,
            Mockery::mock(TimeframeChecker::class),
            Mockery::mock(DBTransactionExecutor::class),
            Mockery::mock(ArtifactsInExplicitBacklogDao::class),
            Mockery::mock(PlanningUpdater::class),
            Mockery::mock(EventManager::class),
            $this->planning_request_validator
        );

        $configuration_manager = mock('AgileDashboard_ConfigurationManager');
        $this->event_manager   = \Mockery::spy(\EventManager::class);

        stub($configuration_manager)->getScrumTitle()->returns('Scrum');
        stub($configuration_manager)->getKanbanTitle()->returns('Kanban');
        stub($configuration_manager)->scrumIsActivatedForProject()->returns(true);
        stub($configuration_manager)->kanbanIsActivatedForProject()->returns(true);

        $count_element_mode_checker = Mockery::mock(CountElementsModeChecker::class);

        $this->controller = new AdminController(
            $this->request,
            $this->planning_factory,
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_KanbanFactory'),
            $configuration_manager,
            mock('TrackerFactory'),
            $this->event_manager,
            $service_crumb_builder,
            $admin_crumb_builder,
            $count_element_mode_checker,
            Mockery::mock(ScrumPresenterBuilder::class)
        );

        $count_element_mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnFalse();

        stub($this->mono_milestone_checker)->isMonoMilestoneEnabled()->returns(false);
        stub($this->mono_milestone_checker)->isScrumMonoMilestoneAvailable()->returns(false);

        stub($this->planning_factory)->getPotentialPlanningTrackers()->returns(array());
        stub($this->planning_factory)->getAvailablePlanningTrackers()->returns(array(1));
        stub($this->planning_factory)->getPlanningsOutOfRootPlanningHierarchy()->returns(array());
        stub($this->current_user)->useLabFeatures()->returns(false);

        $this->user_manager = stub('UserManager')->getCurrentUser()->returns($this->current_user);
        UserManager::setInstance($this->user_manager);
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        UserManager::clearInstance();
        parent::tearDown();
    }

    protected function userIsAdmin()
    {
        stub($this->current_user)->isAdmin($this->group_id)->returns(true);
    }

    protected function userIsNotAdmin()
    {
        stub($this->current_user)->isAdmin($this->group_id)->returns(false);
    }

    /**
     * @param string $action example: 'updatePlanning'
     */
    protected function assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin($action)
    {
        $this->userIsNotAdmin();
        stub($this->planning_factory)->$action()->never();
        stub($GLOBALS['Response'])->redirect()->once();
        stub($GLOBALS['Response'])->addFeedback('error', '*')->once();
        $this->expectException();
    }
}

class Planning_ControllerNewTest extends TuleapTestCase
{

    private $available_backlog_trackers;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', TRACKER_BASE_DIR . '/../../..');
        $this->group_id               = 123;
        $project_manager              = Mockery::spy(ProjectManager::class, ['getProject' => aMockProject()->withId($this->group_id)->build()]);
        $this->request                = aRequest()->withProjectManager($project_manager)->with('group_id', "$this->group_id")->build();
        $this->planning_factory       = mock('PlanningFactory');
        $this->tracker_factory        = mock('TrackerFactory');
        $scrum_mono_milestone_checker = mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');

        $kanban_factory = stub('AgileDashboard_KanbanFactory')->getKanbanTrackerIds()->returns([]);

        $event_manager                    = Mockery::mock(EventManager::class);
        $this->planning_request_validator = Mockery::mock(Planning_RequestValidator::class);
        $this->planning_controller        = new Planning_Controller(
            $this->request,
            $this->planning_factory,
            mock('Planning_MilestoneFactory'),
            mock('ProjectManager'),
            mock('AgileDashboard_XMLFullStructureExporter'),
            '/path/to/plugin',
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_ConfigurationManager'),
            $kanban_factory,
            mock('PlanningPermissionsManager'),
            $scrum_mono_milestone_checker,
            new ScrumPlanningFilter($scrum_mono_milestone_checker, $this->planning_factory),
            mock('Tracker_FormElementFactory'),
            mock(AgileDashboardCrumbBuilder::class),
            mock(AdministrationCrumbBuilder::class),
            Mockery::mock(TimeframeChecker::class),
            Mockery::mock(DBTransactionExecutor::class),
            Mockery::mock(ArtifactsInExplicitBacklogDao::class),
            Mockery::mock(PlanningUpdater::class),
            $event_manager,
            $this->planning_request_validator
        );

        stub($GLOBALS['Language'])->getText()->returns('');

        $this->available_backlog_trackers = array(
            aTracker()->withId(101)->withName('Stories')->build(),
            aTracker()->withId(102)->withName('Releases')->build(),
            aTracker()->withId(103)->withName('Sprints')->build()
        );

        $this->available_planning_trackers = array(
            aTracker()->withId(101)->withName('Stories')->build(),
            aTracker()->withId(103)->withName('Sprints')->build()
        );

        stub($this->planning_factory)->buildNewPlanning($this->group_id)->returns(
            aPlanning()->withGroupId($this->group_id)->build()
        );

        stub($event_manager)->processEvent();

        $this->renderNew();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    protected function renderNew()
    {
        stub($this->planning_factory)->getAvailablePlanningTrackers()->returns($this->available_planning_trackers);
        stub($this->planning_factory)->getAvailableBacklogTrackers()->returns($this->available_backlog_trackers);

        $this->output = $this->planning_controller->new_();
    }

    public function itHasATextFieldForTheName()
    {
        $this->assertPattern('/<input type="text" name="planning\[name\]"/', $this->output);
    }

    public function itHasASelectBoxListingBacklogTrackers()
    {
        $this->assertPattern('/\<select name="planning\['.PlanningParameters::BACKLOG_TRACKER_IDS.'\]\[\]"/', $this->output);
        foreach ($this->available_backlog_trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'".*\>'.$tracker->getName().'/', $this->output);
        }
    }

    public function itHasASelectBoxListingPlanningTrackers()
    {
        $this->assertPattern('/\<select name="planning\[planning_tracker_id\]"/', $this->output);
        foreach ($this->available_planning_trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'".*\>'.$tracker->getName().'/', $this->output);
        }
    }
}

abstract class Planning_ControllerCreateTest extends Planning_Controller_BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->planning_factory->setReturnValue('getAvailableBacklogTrackers', array());
        $this->planning_factory->setReturnValue('getPlanningTrackerIdsByGroupId', array());
    }
}

class Planning_ControllerCreateWithInvalidParamsTest extends Planning_ControllerCreateTest
{
    public function setUp()
    {
        parent::setUp();

        $this->request->set('planning[name]', '');
        $this->request->set('planning['.PlanningParameters::BACKLOG_TRACKER_IDS.'][]', '');
        $this->request->set('planning[planning_tracker_id]', '');
    }

    public function itShowsAnErrorMessageAndRedirectsBackToTheCreationForm()
    {
        stub($this->planning_request_validator)->isValid()->returns(false);

        $this->userIsAdmin();
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id=' . $this->group_id . '&action=new');

        $this->planning_controller->create();
    }
}

class Planning_ControllerCreateWithValidParamsTest extends Planning_ControllerCreateTest
{
    public function setUp()
    {
        parent::setUp();

        $this->planning_parameters = array(
            PlanningParameters::NAME                => 'Release Planning',
            PlanningParameters::PLANNING_TRACKER_ID => '3',
            PlanningParameters::BACKLOG_TITLE       => 'Release Backlog',
            PlanningParameters::PLANNING_TITLE      => 'Sprint Plan',
            PlanningParameters::BACKLOG_TRACKER_IDS => array(
                '2'
            ),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE => array(
                '2',
                '3'
            )
        );
        $this->request->set('planning', $this->planning_parameters);
    }

    public function itCreatesThePlanningAndRedirectsToTheIndex()
    {
        stub($this->planning_request_validator)->isValid()->returns(true);

        $this->userIsAdmin();
        $this->planning_factory->expectOnce('createPlanning', array($this->group_id, PlanningParameters::fromArray($this->planning_parameters)));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=admin');
        $this->planning_controller->create();
    }

    public function itDoesntCreateAnythingIfTheUserIsNotAdmin()
    {
        stub($this->planning_request_validator)->isValid()->returns(true);

        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('createPlanning');
        $this->planning_controller->create();
    }
}
