<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../../../tracker/tests/builders/all.php');

require_once TRACKER_BASE_DIR .'/Tracker/TrackerManager.class.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningController.class.php';
require_once dirname(__FILE__).'/../../include/Planning/Planning.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../builders/aPlanningFactory.php';
require_once dirname(__FILE__).'/../builders/aPlanningController.php';

Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('PlanningFactory');
Mock::generate('Planning');
Mock::generatePartial('Planning_Controller', 'MockPlanning_Controller', array('render'));
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_SearchContentView');
Mock::generate('Tracker_CrossSearch_ViewBuilder');

abstract class Planning_Controller_BaseTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->group_id         = 123;
        $this->project          = stub('Project')->getID()->returns($this->group_id);
        $this->project_manager  = stub('ProjectManager')->getProject($this->group_id)->returns($this->project);
        $this->current_user     = stub('User')->getId()->returns(666);
        $this->request          = aRequest()->withProjectManager($this->project_manager)->with('group_id', "$this->group_id")->withUser($this->current_user)->build();
        $this->planning_factory = new MockPlanningFactory();
        $this->controller       = new Planning_Controller($this->request, $this->planning_factory, mock('Planning_MilestoneFactory'), '/path/to/theme');
    }

    protected function userIsAdmin() {
        stub($this->project)->userIsAdmin($this->current_user)->returns(true);
    }

    protected function userIsNotAdmin() {
        stub($this->project)->userIsAdmin($this->current_user)->returns(false);
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

    protected function renderAdmin() {
        $this->planning_factory->expectOnce('getPlannings', array($this->current_user, $this->group_id));
        $this->planning_factory->setReturnValue('getPlannings', $this->plannings);

        ob_start();
        $this->controller->admin();
        $this->output = ob_get_clean();
    }

    public function itHasALinkToCreateANewPlanning() {
        $this->assertPattern('/action=new/', $this->output);
    }
}

class Planning_ControllerEmptyAdminTest extends Planning_ControllerAdminTest {
    function setUp() {
        parent::setUp();
        $this->plannings = array();
        $this->renderAdmin();
    }

    public function itListsNothing() {
        $this->assertNoPattern('/<ul>/', $this->output);
    }
}

class Planning_ControllerNonEmptyAdminTest extends Planning_ControllerAdminTest {
    function setUp() {
        parent::setUp();

        $this->plannings = array(
            aPlanning()->withId(1)->withName('Release Planning')->build(),
            aPlanning()->withId(2)->withName('Sprint Planning')->build(),
        );

        $this->renderAdmin();
    }

    public function itListsExistingPlannings() {
        foreach($this->plannings as $planning) {
            $this->assertPattern('/'.$planning->getName().'/', $this->output);
            $this->assertPattern('/href=".*?planning_id='.$planning->getId().'.*"/', $this->output);
        }
    }
}


class MockBaseLanguage_Planning_ControllerNewTest extends MockBaseLanguage {
    function getText($key1, $key2, $args = array()) {
        if ($key1 == 'plugin_agiledashboard' && $key2 == 'planning-allows-assignment') {
            return 'This planning allows assignment of '. $args[0] .' to '. $args[1];
        }
        return parent::getText($key1, $key2, $args);
    }
}
class Planning_ControllerNewTest extends TuleapTestCase {

    private $available_backlog_trackers;

    function setUp() {
        parent::setUp();
        $this->group_id         = 123;
        $this->request          = aRequest()->with('group_id', "$this->group_id")->build();
        $this->dao              = mock('PlanningDao');
        $this->planning_factory = aPlanningFactory()->withDao($this->dao)->build();
        $this->tracker_factory  = $this->planning_factory->getTrackerFactory();
        $this->controller       = new Planning_Controller($this->request, $this->planning_factory, mock('Planning_MilestoneFactory'), '/path/to/theme');
        $GLOBALS['Language']    = new MockBaseLanguage_Planning_ControllerNewTest();

        $this->available_backlog_trackers = array(
            101 => aTracker()->withId(101)->withName('Stories')->build(),
            102 => aTracker()->withId(102)->withName('Releases')->build(),
            103 => aTracker()->withId(103)->withName('Sprints')->build()
        );

        $this->available_planning_trackers = array(
            101 => aTracker()->withId(101)->withName('Stories')->build(),
            103 => aTracker()->withId(103)->withName('Sprints')->build()
        );

        $this->renderNew();
    }

    protected function renderNew() {
        stub($this->tracker_factory)->getTrackersByGroupId($this->group_id)->returns($this->available_backlog_trackers);
        stub($this->dao)->searchNonPlanningTrackersByGroupId($this->group_id)->returns(array());

        ob_start();
        $this->controller->new_();
        $this->output = ob_get_clean();
    }

    public function itHasATextFieldForTheName() {
        $this->assertPattern('/<input type="text" name="planning\[name\]"/', $this->output);
    }

    public function itHasASelectBoxListingBacklogTrackers() {
        $this->assertPattern('/\<select name="planning\[backlog_tracker_id\]"/', $this->output);
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

        $this->planning_factory->setReturnValue('getAvailableTrackers', array());
        $this->planning_factory->setReturnValue('getPlanningTrackerIdsByGroupId', array());
    }
}

class Planning_ControllerCreateWithInvalidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
        parent::setUp();

        $this->request->set('planning[name]', '');
        $this->request->set('planning[backlog_tracker_id]', '');
        $this->request->set('planning[planning_tracker_id]', '');
    }

    public function itShowsAnErrorMessageAndRedirectsBackToTheCreationForm() {
        $this->userIsAdmin();
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=new');
        $this->controller->create();
    }
}

class Planning_ControllerCreateWithValidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
        parent::setUp();

        $this->planning_parameters = array('name'                => 'Release Planning',
                                           'backlog_tracker_id'  => '2',
                                           'planning_tracker_id' => '3',
                                           'backlog_title'       => 'Release Backlog',
                                           'plan_title'          => 'Sprint Plan');
        $this->request->set('planning', $this->planning_parameters);
    }

    public function itCreatesThePlanningAndRedirectsToTheIndex() {
        $this->userIsAdmin();
        $this->planning_factory->expectOnce('createPlanning', array($this->group_id, PlanningParameters::fromArray($this->planning_parameters)));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id);
        $this->controller->create();
    }

    public function itDoesntCreateAnythingIfTheUserIsNotAdmin() {
        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('createPlanning');
        $this->controller->create();
    }
}

class Planning_Controller_EditTest extends TuleapTestCase {

    public function itRendersTheEditTemplate() {
        $group_id         = 123;
        $planning_id      = 456;
        $planning         = aPlanning()->withGroupId($group_id)
                                       ->withId($planning_id)->build();
        $request          = aRequest()->with('planning_id', $planning_id)
                                      ->with('action', 'edit')->build();
        $planning_factory = mock('PlanningFactory');
        $controller       = new MockPlanning_Controller();

        stub($planning_factory)->getPlanningWithTrackers($planning_id)->returns($planning);
        stub($planning_factory)->getAvailableTrackers($group_id)->returns(array());
        stub($planning_factory)->getAvailablePlanningTrackers($planning)->returns(array());

        $controller->__construct($request, $planning_factory, mock('Planning_MilestoneFactory'), '/path/to/theme');

        $controller->expectOnce('render', array('edit', new IsAExpectation('Planning_FormPresenter')));
        $controller->edit();
    }
}

class Planning_Controller_Update_BaseTest extends Planning_Controller_BaseTest {

    protected $planning_id         = 123;
    protected $planning_parameters = array(
        'name'                => 'Foo',
        'backlog_title'       => 'Bar',
        'plan_title'          => 'Baz',
        'backlog_tracker_id'  => 43875,
        'planning_tracker_id' => 654823
    );

    public function setUp() {
        parent::setUp();
        $this->request->set('planning_id', $this->planning_id);
        $this->request->set('planning', $this->planning_parameters);

        // TODO: Inject validator into controller so that we can mock it and test it in isolation.
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId($this->group_id)->returns(array());
    }
}

class Planning_Controller_ValidUpdateTest extends Planning_Controller_Update_BaseTest {

    public function itUpdatesThePlanningAndRedirectToTheIndex() {
        $this->userIsAdmin();
        $this->planning_factory->expectOnce('updatePlanning', array($this->planning_id, PlanningParameters::fromArray($this->planning_parameters)));
        $this->expectRedirectTo("/plugins/agiledashboard/?group_id={$this->group_id}&action=index");
        $this->controller->update();
    }

    public function itDoesntUpdateAnythingIfTheUserIsNotAdmin() {
        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('updatePlanning');
        $this->controller->update();
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
        $this->controller->update();
    }

    public function itReRendersTheEditForm() {
        $this->expectRedirectTo("/plugins/agiledashboard/?group_id=$this->group_id&planning_id=$this->planning_id&action=edit");
        $this->controller->update();
    }

    public function itDisplaysTheRelevantErrorMessages() {
        $this->expectFeedback('error', '*');
        $this->controller->update();
    }
}

class Planning_ControllerDeleteTest extends Planning_Controller_BaseTest {

    protected $planning_id = '12';

    public function itDeletesThePlanningAndRedirectsToTheIndex() {
        $this->userIsAdmin();
        $this->request->set('planning_id', $this->planning_id);

        stub($this->planning_factory)->deletePlanning($this->planning_id)->once();
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id);
        $this->controller->delete();
    }

    public function itDoesntDeleteAnythingIfTheUserIsNotAdmin() {
        $this->assertThatPlanningFactoryActionIsNotCalledWhenUserIsNotAdmin('deletePlanning');
        $this->controller->delete();
    }
}

?>
