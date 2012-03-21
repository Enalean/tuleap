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

require_once(dirname(__FILE__).'/../../include/Planning/Controller.class.php');
require_once(dirname(__FILE__).'/../../include/Planning/Planning.class.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_Builder.php');
require_once(dirname(__FILE__).'/../builders/planning.php');
require_once dirname(__FILE__).'/../builders/controller.php';

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('PlanningFactory');
Mock::generate('Planning');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_SearchContentView');
Mock::generate('Tracker_CrossSearch_ViewBuilder');

class Planning_ControllerTest extends TuleapTestCase {
    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $id = 987;
        $title = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title);
        $this->assertPattern('/No items yet/', $content);
    }
    
    public function itDisplaysTheArtifactTitleAndId() {
        $id = 987;
        $title = "screen hangs with macos and some escapable characters #<";
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title);
        $this->assertPattern("/art-$id/", $content);
        $this->assertPattern("/$title/", $content);
    }

    public function itListsAllLinkedItems() {
        $id = 987;
        $linked_items = array(
            $this->GivenAnArtifact(123, 'Tutu'),
            $this->GivenAnArtifact(123, 'Tata')
        );
        
        $artifact = $this->GivenAnArtifact($id, 'Toto');
        $artifact->setReturnValue('getLinkedArtifacts', $linked_items);
        $factory = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        $request = new Codendi_Request(array('aid' => $id));

        $content = $this->WhenICaptureTheOutputOfShowAction($request, $factory);
        $this->assertPattern('/Tutu/', $content);
        $this->assertPattern('/Tata/', $content);
    }
    
    public function itDisplaysTheSearchContentView() {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $a_list_of_draggable_items = 'A list of draggable items';
        $content_view->setReturnValue('fetch', $a_list_of_draggable_items);
        
        $project_id = 1111;
        $criteria = array();
        $search = new MockTracker_CrossSearch_Search();
        $hierarchy_factory = new MockTracker_HierarchyFactory();
        
        $project = new MockProject();
        $project_manager = new MockProjectManager();
        $project_manager->setReturnValue('getProject', $project, array($project_id));
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $view_builder->expectOnce('buildContentView', array($project, $criteria, $search, $hierarchy_factory));
        $view_builder->setReturnValue('buildContentView', $content_view);
        
        $id       = 987;
        $title    = "screen hangs with macos and some escapable characters #<";
        $request  = new Codendi_Request(array('aid' => $id
                                            , 'group_id' =>$project_id
                                            , 'criteria' => $criteria));
        $artifact = $this->GivenAnArtifact($id, $title);
        $factory  = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search, $hierarchy_factory);
        
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }
    
    public function itAssumesNoCriteriaIfRequestedCriterieIsAbsent() {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $a_list_of_draggable_items = 'A list of draggable items';
        $content_view->setReturnValue('fetch', $a_list_of_draggable_items);
        
        $project_id = 1111;
        $criteria = array();
        $search = new MockTracker_CrossSearch_Search();
        $hierarchy_factory = new MockTracker_HierarchyFactory();
        
        $project = new MockProject();
        $project_manager = new MockProjectManager();
        $project_manager->setReturnValue('getProject', $project, array($project_id));
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $view_builder->expectOnce('buildContentView', array($project, $criteria, $search, $hierarchy_factory));
        $view_builder->setReturnValue('buildContentView', $content_view);
        
        $id       = 987;
        $title    = "screen hangs with macos and some escapable characters #<";
        $request  = new Codendi_Request(array('aid' => $id
                                            , 'group_id' => $project_id));
        $artifact = $this->GivenAnArtifact($id, $title);
        $factory  = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search, $hierarchy_factory);
        
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }
    
    public function itAssumesNoCriteriaIfRequestedCriterieIsNotValid() {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $a_list_of_draggable_items = 'A list of draggable items';
        $content_view->setReturnValue('fetch', $a_list_of_draggable_items);
        
        $project_id = 1111;
        $criteria = array();
        $search = new MockTracker_CrossSearch_Search();
        $hierarchy_factory = new MockTracker_HierarchyFactory();
        
        $project = new MockProject();
        $project_manager = new MockProjectManager();
        $project_manager->setReturnValue('getProject', $project, array($project_id));
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $view_builder->expectOnce('buildContentView', array($project, $criteria, $search, $hierarchy_factory));
        $view_builder->setReturnValue('buildContentView', $content_view);
        
        $id       = 987;
        $title    = "screen hangs with macos and some escapable characters #<";
        $request  = new Codendi_Request(array('aid' => $id
                                            , 'group_id' => $project_id
                                            , 'criteria' => 'nimp'));
        $artifact = $this->GivenAnArtifact($id, $title);
        $factory  = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search, $hierarchy_factory);
        
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }
    
    private function GivenAnArtifact($id, $title) {
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('fetchTitle', "#$id $title");
        $artifact->setReturnValue('getId', $id);
        return $artifact;
    }
    
    private function WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title) {
        $request = new Codendi_Request(array('aid' => $id));
        
        $artifact = $this->GivenAnArtifact($id, $title);
        
        $factory = new MockTracker_ArtifactFactory();
        $factory->setReturnValue('getArtifactByid', $artifact, array($id));
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfShowAction($request, $factory) {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $content_view->setReturnValue('fetch', 'stuff');
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $view_builder->setReturnValue('buildContentView', $content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, new MockProjectManager(), new MockTracker_CrossSearch_Search(), new MockTracker_HierarchyFactory());
    }
    
    private function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search, $hierarchy_factory) {
        ob_start();
        $controller = new Planning_Controller($request, $factory, new MockPlanningFactory(), new MockTrackerFactory());
        $controller->show($view_builder, $project_manager, $search, $hierarchy_factory);
        $content = ob_get_clean();
        return $content;
    }
}

abstract class Planning_ControllerIndexTest extends TuleapTestCase {
    function setUp() {
        parent::setUp();
        
        $this->group_id         = '123';
        $this->request          = new Codendi_Request(array('group_id' => $this->group_id));
        $this->artifact_factory = new MockTracker_ArtifactFactory();
        $this->planning_factory = new MockPlanningFactory();
        $this->tracker_factory  = new MockTrackerFactory();
        $this->controller       = new Planning_Controller($this->request, $this->artifact_factory, $this->planning_factory, $this->tracker_factory);
    }
    
    protected function renderIndex() {
        $this->planning_factory->expectOnce('getPlannings', array($this->group_id));
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

class Planning_ControllerNewTest extends TuleapTestCase {
    
    function setUp() {
        parent::setUp();
        $this->group_id         = '123';
        $this->request          = new Codendi_Request(array('group_id' => $this->group_id));
        $this->artifact_factory = new MockTracker_ArtifactFactory();
        $this->planning_factory = new MockPlanningFactory();
        $this->tracker_factory  = new MockTrackerFactory();
        $this->controller       = new Planning_Controller($this->request, $this->artifact_factory, $this->planning_factory, $this->tracker_factory);
        
        $this->trackers = array(
            101 => aTracker()->withId(101)->withName('Epics')->build(),
            102 => aTracker()->withId(102)->withName('Stories')->build(),
        );
        
        $this->renderNew();
    }
    
    protected function renderNew() {
        $this->tracker_factory->expectOnce('getTrackersByGroupId', array($this->group_id));
        $this->tracker_factory->setReturnValue('getTrackersByGroupId', $this->trackers);
        
        ob_start();
        $this->controller->new_();
        $this->output = ob_get_clean();
    }
    
    public function itHasATextFieldForTheName() {
        $this->assertPattern('/<input type="text" name="planning_name"/', $this->output);
    }
    
    public function itHasAMultiSelectBoxListingTrackers() {
        
        $this->assertPattern('/\<select multiple="multiple" name="planning_backlog_ids\[\]"/', $this->output);
        foreach ($this->trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'"\>'.$tracker->getName().'/', $this->output);
        }
    }
    
    public function itHasASelectBoxListingTrackers() {
        $this->assertPattern('/\<select name="planning_release_id"/', $this->output);
        foreach ($this->trackers as $tracker) {
            $this->assertPattern('/\<option value="'.$tracker->getId().'"\>'.$tracker->getName().'/', $this->output);
        }
    }
}

abstract class Planning_ControllerCreateTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->group_id         = '123';
        $this->request          = new Codendi_Request(array('group_id' => $this->group_id));
        $this->artifact_factory = new MockTracker_ArtifactFactory();
        $this->planning_factory = new MockPlanningFactory();
        $this->tracker_factory  = new MockTrackerFactory();
        
        $this->tracker_factory->setReturnValue('getTrackersByGroupId', array());
    }
    
    protected function create() {
        $this->controller = new Planning_Controller($this->request, $this->artifact_factory, $this->planning_factory, $this->tracker_factory);
        
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    }
}

class Planning_ControllerCreateWithInvalidParamsTest extends Planning_ControllerCreateTest {
    public function setUp() {
        parent::setUp();
        
        $this->request->set('planning_name', '');
        $this->request->set('planning_backlog_ids', array());
        $this->request->set('planning_release_id', '');
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
        $this->request->set('planning_backlog_ids', array('1', '2'));
        $this->request->set('planning_release_id', '3');
    }
    
    public function itCreatesThePlanningAndRedirectsToTheIndex() {
        $this->planning_factory->expectOnce('create', array('Release Planning', $this->group_id, array('1', '2'), '3'));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$this->group_id);
        $this->create();
    }
}

class Planning_ControllerEditWithInvalidPlanningIdTest extends TuleapTestCase {
    public function itGeneratesError404() {
        $invalid_id       = 'invalid';
        $request          = new Codendi_Request(array('planning_id' => $invalid_id));
        $planning_factory = new MockPlanningFactory();
        $controller       = aPlanningController()->with('request', $request)
                                                 ->with('planning_factory', $planning_factory)
                                                 ->build();
        
        $planning_factory->expectOnce('getPlanning', array($invalid_id));
        $planning_factory->throwOn('getPlanning', new Planning_NotFoundException());
        
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(404));
        $controller->edit();
    }
}

class Planning_ControllerEditWithValidPlanningIdTest extends TuleapTestCase {
    public function itRendersAnEditForm() {
        $planning         = aPlanning()->build();
        $request          = new Codendi_Request(array('planning_id' => $planning->getId()));
        $planning_factory = new MockPlanningFactory();
        $controller       = aPlanningController()->with('request', $request)
                                                 ->with('planning_factory', $planning_factory)
                                                 ->build();
        
        $planning_factory->expectOnce('getPlanning', array($planning->getId()));
        $planning_factory->setReturnValue('getPlanning', $planning);
        
        ob_start();
        $controller->edit();
        $output = ob_get_clean();
        
        $this->assertPattern('/\<form/', $output);
    }
}

class Planning_ControllerDeleteTest extends TuleapTestCase {
    public function itDeletesThePlanningAndRedirectsToTheIndex() {
        $group_id         = '34';
        $planning_id      = '12';
        $request          = new Codendi_Request(array('planning_id' => $planning_id,
                                                      'group_id'    => $group_id));
        $planning_factory = new MockPlanningFactory();
        $controller       = aPlanningController()->with('request', $request)
                                                 ->with('planning_factory', $planning_factory)
                                                 ->build();
        
        $planning_factory->expectOnce('deletePlanning', array($planning_id));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$group_id);
        $controller->delete();
    }
}

?>
