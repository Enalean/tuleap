<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;

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

        $this->planning_controller    = new Planning_Controller(
            $this->request,
            $this->planning_factory,
            mock('Planning_MilestoneFactory'),
            mock('ProjectManager'),
            mock('AgileDashboard_XMLFullStructureExporter'),
            '/path/to/theme',
            '/path/to/plugin',
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_ConfigurationManager'),
            mock('AgileDashboard_KanbanFactory'),
            mock('PlanningPermissionsManager'),
            mock('AgileDashboard_HierarchyChecker'),
            $this->mono_milestone_checker,
            $this->scrum_planning_filter,
            mock('TrackerFactory'),
            mock('Tracker_FormElementFactory'),
            $service_crumb_builder,
            $admin_crumb_builder
        );


        $configuration_manager = mock('AgileDashboard_ConfigurationManager');
        $this->event_manager   = mock('EventManager');

        stub($configuration_manager)->getScrumTitle()->returns('Scrum');
        stub($configuration_manager)->getKanbanTitle()->returns('Kanban');
        stub($configuration_manager)->scrumIsActivatedForProject()->returns(true);
        stub($configuration_manager)->kanbanIsActivatedForProject()->returns(true);

        $this->controller = new AdminController(
            $this->request,
            $this->planning_factory,
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_KanbanFactory'),
            $configuration_manager,
            mock('TrackerFactory'),
            $this->mono_milestone_checker,
            $this->event_manager,
            $service_crumb_builder,
            $admin_crumb_builder
        );

        stub($this->mono_milestone_checker)->isMonoMilestoneEnabled()->returns(false);
        stub($this->mono_milestone_checker)->isScrumMonoMilestoneAvailable()->returns(false);

        stub($this->planning_factory)->getPotentialPlanningTrackers()->returns(array());
        stub($this->planning_factory)->getAvailablePlanningTrackers()->returns(array(1));
        stub($this->planning_factory)->getPlanningsOutOfRootPlanningHierarchy()->returns(array());
        stub($this->current_user)->useLabFeatures()->returns(false);

        $this->user_manager = stub('UserManager')->getCurrentUser()->returns($this->current_user);
        UserManager::setInstance($this->user_manager);
    }

    public function tearDown() {
        ForgeConfig::restore();
        UserManager::clearInstance();
        parent::tearDown();
    }

    protected function userIsAdmin() {
        stub($this->current_user)->isAdmin($this->group_id)->returns(true);
    }

    protected function userIsNotAdmin() {
        stub($this->current_user)->isAdmin($this->group_id)->returns(false);
    }

    /**
     * @param string $action example: 'updatePlanning'
     */
    protected function assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin($action) {
        $this->userIsNotAdmin();
        stub($this->planning_factory)->$action()->never();
        stub($GLOBALS['Response'])->redirect()->once();
        stub($GLOBALS['Response'])->addFeedback('error', '*')->once();
        $this->expectException();
    }
}

abstract class Planning_ControllerAdminTest extends Planning_Controller_BaseTest {

    protected function renderAdminScrum() {
        $this->planning_factory->expectOnce('getPlannings', array($this->current_user, $this->group_id));
        $this->planning_factory->setReturnValue('getPlannings', $this->plannings);

        stub($this->planning_factory)->getRootPlanning()->returns(aPlanning()->withPlanningTracker(aMockTracker()->build())->build());

        $this->output = $this->controller->adminScrum();
    }

    public function itHasALinkToCreateANewPlanning() {
        $this->assertPattern('/action=new/', $this->output);
    }
}

class Planning_ControllerNonEmptyAdminTest extends Planning_ControllerAdminTest {
    function setUp() {
        parent::setUp();

        $this->plannings = array(
            aPlanning()->withId(1)->withName('Release Planning')->build(),
            aPlanning()->withId(2)->withName('Sprint Planning')->build(),
        );

        $this->renderAdminScrum();
    }

    public function itListsExistingPlannings() {
        foreach($this->plannings as $planning) {
            $this->assertPattern('/'.$planning->getName().'/', $this->output);
            $this->assertPattern('/href=".*?planning_id='.$planning->getId().'.*"/', $this->output);
        }
    }
}

class Planning_ControllerNewTest extends TuleapTestCase {

    private $available_backlog_trackers;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', TRACKER_BASE_DIR .'/../../..');
        $this->group_id               = 123;
        $project_manager              = Mockery::spy(ProjectManager::class, ['getProject' => aMockProject()->withId($this->group_id)->build()]);
        $this->request                = aRequest()->withProjectManager($project_manager)->with('group_id', "$this->group_id")->build();
        $this->planning_factory       = mock('PlanningFactory');
        $this->tracker_factory        = mock('TrackerFactory');
        $hierarchy_checker            = mock('AgileDashboard_HierarchyChecker');
        $scrum_mono_milestone_checker = mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');

        $kanban_factory = stub('AgileDashboard_KanbanFactory')->getKanbanTrackerIds()->returns(array());

        $this->planning_controller = new Planning_Controller(
            $this->request,
            $this->planning_factory,
            mock('Planning_MilestoneFactory'),
            mock('ProjectManager'),
            mock('AgileDashboard_XMLFullStructureExporter'),
            '/path/to/theme',
            '/path/to/plugin',
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_ConfigurationManager'),
            $kanban_factory,
            mock('PlanningPermissionsManager'),
            $hierarchy_checker,
            $scrum_mono_milestone_checker,
            new ScrumPlanningFilter($scrum_mono_milestone_checker, $this->planning_factory),
            mock('TrackerFactory'),
            mock('Tracker_FormElementFactory'),
            mock(AgileDashboardCrumbBuilder::class),
            mock(AdministrationCrumbBuilder::class)
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

        stub($this->planning_factory)->buildNewPlanning($this->group_id)->returns(aPlanning()->withGroupId($this->group_id)->build());

        $this->renderNew();
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    protected function renderNew() {
        stub($this->planning_factory)->getAvailablePlanningTrackers()->returns($this->available_planning_trackers);
        stub($this->planning_factory)->getAvailableBacklogTrackers()->returns($this->available_backlog_trackers);

        $this->output = $this->planning_controller->new_();
    }

    public function itHasATextFieldForTheName() {
        $this->assertPattern('/<input type="text" name="planning\[name\]"/', $this->output);
    }

    public function itHasASelectBoxListingBacklogTrackers() {
        $this->assertPattern('/\<select name="planning\['.PlanningParameters::BACKLOG_TRACKER_IDS.'\]\[\]"/', $this->output);
        foreach ($this->available_backlog_trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'".*\>'.$tracker->getName().'/', $this->output);
        }
    }

    public function itHasASelectBoxListingPlanningTrackers() {
        $this->assertPattern('/\<select name="planning\[planning_tracker_id\]"/', $this->output);
        foreach ($this->available_planning_trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'".*\>'.$tracker->getName().'/', $this->output);
        }
    }
}

abstract class Planning_ControllerCreateTest extends Planning_Controller_BaseTest {
    public function setUp() {
        parent::setUp();

        $this->planning_factory->setReturnValue('getAvailableBacklogTrackers', array());
        $this->planning_factory->setReturnValue('getPlanningTrackerIdsByGroupId', array());
    }
}

class Planning_ControllerCreateWithInvalidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
        parent::setUp();

        $this->request->set('planning[name]', '');
        $this->request->set('planning['.PlanningParameters::BACKLOG_TRACKER_IDS.'][]', '');
        $this->request->set('planning[planning_tracker_id]', '');
    }

    public function itShowsAnErrorMessageAndRedirectsBackToTheCreationForm() {
        $this->userIsAdmin();
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=new');
        $this->planning_controller->create();
    }
}

class Planning_ControllerCreateWithValidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
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

    public function itCreatesThePlanningAndRedirectsToTheIndex() {
        $this->userIsAdmin();
        $this->planning_factory->expectOnce('createPlanning', array($this->group_id, PlanningParameters::fromArray($this->planning_parameters)));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=admin');
        $this->planning_controller->create();
    }

    public function itDoesntCreateAnythingIfTheUserIsNotAdmin() {
        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('createPlanning');
        $this->planning_controller->create();
    }
}

class Planning_Controller_EditTest extends Planning_Controller_BaseTest {

    public function itRendersTheEditTemplate() {
        $group_id         = 123;
        $planning_id      = 456;
        $planning         = aPlanning()->withGroupId($group_id)
                                       ->withId($planning_id)->build();
        $project_manager              = Mockery::spy(ProjectManager::class, ['getProject' => aMockProject()->withId($group_id)->build()]);
        $request          = aRequest()
            ->withProjectManager($project_manager)
            ->with('planning_id', $planning_id)
            ->with('action', 'edit')
            ->build();
        $planning_factory = mock('PlanningFactory');
        $planning_filter  = mock('Tuleap\AgileDashboard\Planning\ScrumPlanningFilter');
        stub($planning_factory)->getPlanning($planning_id)->returns($planning);
        stub($planning_filter)->getPlanningTrackersFiltered()->returns(array());
        stub($planning_filter)->getBacklogTrackersFiltered()->returns(array());

        stub($planning_factory)->getAvailablePlanningTrackers()->returns([]);
        stub($planning_factory)->getAvailableBacklogTrackers()->returns([]);

        $kanban_factory = stub('AgileDashboard_KanbanFactory')->getKanbanTrackerIds()->returns(array());

        $controller = partial_mock(
            'Planning_Controller',
            array('renderToString'),
            array(
                $request,
                $planning_factory,
                mock('Planning_MilestoneFactory'),
                mock('ProjectManager'),
                mock('AgileDashboard_XMLFullStructureExporter'),
                '/path/to/theme',
                '/path/to/plugin',
                mock('AgileDashboard_KanbanManager'),
                mock('AgileDashboard_ConfigurationManager'),
                $kanban_factory,
                mock('PlanningPermissionsManager'),
                mock('AgileDashboard_HierarchyChecker'),
                mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker'),
                $planning_filter,
                mock('TrackerFactory'),
                mock('Tracker_FormElementFactory'),
                mock(AgileDashboardCrumbBuilder::class),
                mock(AdministrationCrumbBuilder::class)
            )
        );

        $controller->expectOnce('renderToString', array('edit', new IsAExpectation('Planning_FormPresenter')));
        $controller->edit();
    }
}

class Planning_Controller_Update_BaseTest extends Planning_Controller_BaseTest {

    protected $planning_id         = 123;
    protected $planning_parameters = array(
        'name'                                           => 'Foo',
        'backlog_title'                                  => 'Bar',
        'plan_title'                                     => 'Baz',
        'planning_tracker_id'                            => 654823,
        PlanningParameters::BACKLOG_TRACKER_IDS          => array(43875),
        PlanningPermissionsManager::PERM_PRIORITY_CHANGE => array(
            '2',
            '3'
        )
    );

    public function setUp() {
        parent::setUp();
        $this->request->set('planning_id', $this->planning_id);
        $this->request->set('planning', $this->planning_parameters);

        // TODO: Inject validator into controller so that we can mock it and test it in isolation.
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId($this->group_id)->returns(array());
        stub($this->planning_factory)->getPlanning()->returns(mock('Planning'));
    }
}

class Planning_Controller_ValidUpdateTest extends Planning_Controller_Update_BaseTest {

    public function itUpdatesThePlanningAndRedirectToTheIndex() {
        $this->userIsAdmin();
        $this->planning_factory->expectOnce('updatePlanning', array($this->planning_id, $this->group_id, PlanningParameters::fromArray($this->planning_parameters)));
        $this->expectRedirectTo("/plugins/agiledashboard/?group_id={$this->group_id}&planning_id={$this->planning_id}&action=edit");
        $this->planning_controller->update();
    }

    public function itDoesntUpdateAnythingIfTheUserIsNotAdmin() {
        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('updatePlanning');
        $this->planning_controller->update();
    }
}

class Planning_Controller_InvalidUpdateTest extends Planning_Controller_Update_BaseTest {

    protected $planning_parameters = array();

    public function setUp() {
        parent::setUp();
        $this->userIsAdmin();
    }

    public function itDoesNotUpdateThePlanning() {
        $this->planning_factory->expectNever('updatePlanning');
        $this->planning_controller->update();
    }

    public function itReRendersTheEditForm() {
        $this->expectRedirectTo("/plugins/agiledashboard/?group_id=$this->group_id&planning_id=$this->planning_id&action=edit");
        $this->planning_controller->update();
    }

    public function itDisplaysTheRelevantErrorMessages() {
        $this->expectFeedback('error', '*');
        $this->planning_controller->update();
    }
}

class Planning_ControllerDeleteTest extends Planning_Controller_BaseTest {

    protected $planning_id = '12';

    public function itDeletesThePlanningAndRedirectsToTheIndex() {
        $this->userIsAdmin();
        $this->request->set('planning_id', $this->planning_id);

        stub($this->planning_factory)->deletePlanning($this->planning_id)->once();
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=admin');
        $this->planning_controller->delete();
    }

    public function itDoesntDeleteAnythingIfTheUserIsNotAdmin() {
        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('deletePlanning');
        $this->planning_controller->delete();
    }
}
