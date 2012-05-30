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

if (!defined('TRACKER_BASE_URL')) {                                             // 
    define('TRACKER_BASE_URL', '/plugins/tracker');                             // 
}                                                                               // TODO: use constants.php instead
if (!defined('TRACKER_BASE_DIR')) {                                             //       (available only in trunk)
    define('TRACKER_BASE_DIR', dirname(__FILE__) .'/../../../tracker/include'); // 
}


require_once dirname(__FILE__).'/../../../tracker/include/Tracker/TrackerManager.class.php';
require_once(dirname(__FILE__).'/../../include/Planning/Controller.class.php');
require_once(dirname(__FILE__).'/../../include/Planning/Planning.class.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_Builder.php');
require_once(dirname(__FILE__).'/../builders/planning.php');
require_once(dirname(__FILE__).'/../builders/planning_factory.php');
require_once dirname(__FILE__).'/../builders/controller.php';

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('PlanningFactory');
Mock::generate('Planning');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_SearchContentView');
Mock::generate('Tracker_CrossSearch_ViewBuilder');


abstract class Planning_ControllerIndexTest extends TuleapTestCase {
    function setUp() {
        parent::setUp();
        
        $this->group_id         = '123';
        $this->request          = aRequest()->with('group_id', $this->group_id)
                                            ->build();
        $this->current_user     = $this->request->getCurrentUser();
        $this->planning_factory = new MockPlanningFactory();
        $this->controller       = new Planning_Controller($this->request, $this->planning_factory);
    }

    protected function renderIndex() {
        $this->planning_factory->expectOnce('getPlannings', array($this->current_user, $this->group_id));
        $this->planning_factory->setReturnValue('getPlannings', $this->plannings);
        
        ob_start();
        $this->controller->index();
        $this->output = ob_get_clean();
    }
    
    public function itHasALinkToCreateANewPlanning() {
        $this->assertPattern('/action=new/', $this->output);
    }
}

class Planning_ControllerEmptyIndexTest extends Planning_ControllerIndexTest {
    function setUp() {
        parent::setUp();
        $this->plannings = array();
        $this->renderIndex();
    }
    
    public function itListsNothing() {
        $this->assertNoPattern('/<ul>/', $this->output);
    }
}

class Planning_ControllerNonEmptyIndexTest extends Planning_ControllerIndexTest {
    function setUp() {
        parent::setUp();
        
        $this->plannings = array(
            aPlanning()->withId(1)->withName('Release Planning')->build(),
            aPlanning()->withId(2)->withName('Sprint Planning')->build(),
        );
        
        $this->renderIndex();
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
        $this->group_id         = '123';
        $this->request          = aRequest()->with('group_id', $this->group_id)->build();
        $this->dao              = mock('PlanningDao');
        $this->planning_factory = aPlanningFactory()->withDao($this->dao)->build();
        $this->tracker_factory  = $this->planning_factory->getTrackerFactory();
        $this->controller       = new Planning_Controller($this->request, $this->planning_factory);
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
        $this->assertPattern('/<input type="text" name="planning_name"/', $this->output);
    }
    
    public function itHasAMultiSelectBoxListingBacklogTrackers() {
        
        $this->assertPattern('/\<select multiple="multiple" name="backlog_tracker_ids\[\]"/', $this->output);
        foreach ($this->available_backlog_trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'"\>'.$tracker->getName().'/', $this->output);
        }
    }
    
    public function itHasASelectBoxListingPlanningTrackers() {
        $this->assertPattern('/\<select name="planning_tracker_id"/', $this->output);
        foreach ($this->available_planning_trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'"\>'.$tracker->getName().'/', $this->output);
        }
    }
}

abstract class Planning_ControllerCreateTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->group_id         = '123';
        $this->request          = aRequest()->with('group_id', $this->group_id)->build();
        $this->planning_factory = new MockPlanningFactory();
        
        $this->planning_factory->setReturnValue('getAvailableTrackers', array());
        $this->planning_factory->setReturnValue('getPlanningTrackerIdsByGroupId', array());
    }
    
    protected function create() {
        $this->controller = new Planning_Controller($this->request, $this->planning_factory);
        
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    }
}

class Planning_ControllerCreateWithInvalidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
        parent::setUp();
        
        $this->request->set('planning_name', '');
        $this->request->set('backlog_tracker_ids', array());
        $this->request->set('planning_tracker_id', '');
    }
    
    public function itShowsAnErrorMessageAndRedirectsBackToTheCreationForm() {
        $this->expectFeedback('error', '*');
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=new');
        $this->create();
    }
}

class Planning_ControllerCreateWithValidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
        parent::setUp();
        
        $this->request->set('planning_name', 'Release Planning');
        $this->request->set('backlog_tracker_ids', array('1', '2'));
        $this->request->set('planning_tracker_id', '3');
        $this->request->set('planning_backlog_title', 'Release Backlog');
        $this->request->set('planning_plan_title', 'Sprint Plan');
    }
    
    public function itCreatesThePlanningAndRedirectsToTheIndex() {
        $this->planning_factory->expectOnce('createPlanning', array('Release Planning', $this->group_id, 'Release Backlog', 'Sprint Plan', array('1', '2'), '3'));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id);
        $this->create();
    }
}

class Planning_ControllerDeleteTest extends TuleapTestCase {
    public function itDeletesThePlanningAndRedirectsToTheIndex() {
        $group_id         = '34';
        $planning_id      = '12';
        $request          = aRequest()->with('group_id', $group_id)
                                      ->with('planning_id', $planning_id);
        $planning_factory = new MockPlanningFactory();
        $controller       = aPlanningController()->withRequest($request)
                                                 ->withPlanningFactory($planning_factory)
                                                 ->build();
        
        $planning_factory->expectOnce('deletePlanning', array($planning_id));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$group_id);
        $controller->delete();
    }
}

?>
