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
    
    public function setUp() {
        parent::setUp();
        $this->planning = new Planning(123, 'Stuff Backlog', $group_id = 103, array(), 66);
        $this->setText('-- Please choose', array('global', 'please_choose_dashed'));
    }
    
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
    
    public function itDisplaysTheNameOfThePlanning() {
        $name    = $this->planning->getName();
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact(987, 'whatever');
        $this->assertPattern("/$name/", $content);
    }
    
    public function itDisplaysASelectorOfArtifact() {
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact(987, 'whatever');
        $this->assertPattern('/<select class="planning-artifact-chooser" name="aid"/', $content);
        $this->assertPattern('/<option value="">-- Please choose/', $content);
        $this->assertPattern('/<option value="1001" >An open artifact/', $content);
        $this->assertPattern('/<option value="1002" >Another open artifact/', $content);
        $this->assertPattern('/<input type="hidden" name="planning_id" value="123"/', $content);
        $this->assertPattern('/<input type="hidden" name="action" value="show"/', $content);
        $this->assertPattern('/<input type="hidden" name="group_id" value="103"/', $content);
    }
    
    public function itDoesNotShowAnyErrorIfThereIsNoArtifactGivenInTheRequest() {
        $this->WhenICaptureTheOutputOfShowActionWithoutArtifact();
        $this->assertNoErrors();
    }

    public function itListsAllLinkedItems() {
        $id = 987;
        $linked_items = array(
            $this->GivenAnArtifact(123, 'Tutu'),
            $this->GivenAnArtifact(123, 'Tata')
        );
        
        $artifact = $this->GivenAnArtifact($id, 'Toto');
        $artifact->setReturnValue('getLinkedArtifacts', $linked_items);
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $request = new Codendi_Request(
            array(
                'aid'         => $id,
                'planning_id' => $this->planning->getId(),
            )
        );

        $content = $this->WhenICaptureTheOutputOfShowAction($request, $factory);
        $this->assertPattern('/Tutu/', $content);
        $this->assertPattern('/Tata/', $content);
    }
    
    public function itDisplaysTheSearchContentView() {
        $requested_criteria = array('stuff');
        $this->assertThatWeBuildAcontentViewWith($requested_criteria, array('stuff'));
    }
    
    public function itAssumesNoCriteriaIfRequestedCriterieIsAbsent() {
        $requested_criteria = null;
        $this->assertThatWeBuildAcontentViewWith($requested_criteria, array());
    }
     
    public function itAssumesNoCriteriaIfRequestedCriterieIsNotValid() {
        $requested_criteria = 'invalid parameter type';
        $this->assertThatWeBuildAcontentViewWith($requested_criteria, array());
    }
    
    private function assertThatWeBuildAcontentViewWith($requested_criteria, $expected_criteria) {
        $project_id = 1111;
        $id         = 987;
        $params     = array(
            'aid'         => $id,
            'planning_id' => $this->planning->getId(),
            'group_id'    => $project_id,
        );
        
        if ($requested_criteria !== null) {
            $params['criteria'] = $requested_criteria;
        }
        $request  = new Codendi_Request($params);
        
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $a_list_of_draggable_items = 'A list of draggable items';
        $content_view->setReturnValue('fetch', $a_list_of_draggable_items);
        
        $search = new MockTracker_CrossSearch_Search();
        
        $project = new MockProject();
        $project_manager = new MockProjectManager();
        $project_manager->setReturnValue('getProject', $project, array($project_id));
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $linked_items = array();
        $view_builder->expectOnce('buildCustomContentView', array('Planning_SearchContentView', $project, $expected_criteria, $search, $linked_items));
        $view_builder->setReturnValue('buildCustomContentView', $content_view);
        
        $title    = "screen hangs with macos and some escapable characters #<";
        $artifact = $this->GivenAnArtifact($id, $title);
        $artifact->setReturnValue('getLinkedArtifacts', $linked_items);
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search);
        
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }
    
    private function GivenAnArtifact($id, $title) {
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('fetchTitle', "#$id $title");
        $artifact->setReturnValue('getId', $id);
        return $artifact;
    }
    
    private function GivenAnArtifactWithNoLinkedItem($id, $title) {
        $artifact = $this->GivenAnArtifact($id, $title);
        $artifact->setReturnValue('getLinkedArtifacts', array());
        return $artifact;
    }
    
    private function GivenAnArtifactFactory(array $artifacts = array()) {
        $factory  = new MockTracker_ArtifactFactory();
        foreach ($artifacts as $artifact) {
            $factory->setReturnValue('getArtifactByid', $artifact, array($artifact->getId()));
        }
        $factory->setReturnValue(
            'getOpenArtifactsByTrackerId', 
            array(
                $this->GivenAnArtifactWithNoLinkedItem(1001, 'An open artifact'),
                $this->GivenAnArtifactWithNoLinkedItem(1002, 'Another open artifact'),
            ), 
            array($this->planning->getReleaseTrackerId()));
        return $factory;
    }
    
    private function WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title) {
        $request  = new Codendi_Request(array('aid' => $id, 'planning_id' => $this->planning->getId()));
        $artifact = $this->GivenAnArtifactWithNoLinkedItem($id, $title);
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfShowActionWithoutArtifact() {
        $request = new Codendi_Request(array('planning_id' => $this->planning->getId()));
        $factory = $this->GivenAnArtifactFactory();
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfShowAction($request, $factory) {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $content_view->setReturnValue('fetch', 'stuff');
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $view_builder->setReturnValue('buildCustomContentView', $content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, new MockProjectManager(), new MockTracker_CrossSearch_Search());
    }
    
    private function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search) {
        $planning_factory = new MockPlanningFactory();
        $planning_factory->setReturnValue('getPlanning', $this->planning, array($this->planning->getId()));
        
        ob_start();
        $controller = new Planning_Controller($request, $factory, $planning_factory, new MockTrackerFactory());
        $controller->show($view_builder, $project_manager, $search);
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
        $tracker_factory  = new MockTrackerFactory();
        $planning_factory = new MockPlanningFactory();
        $controller       = aPlanningController()->with('request', $request)
                                                 ->with('planning_factory', $planning_factory)
                                                 ->with('tracker_factory', $tracker_factory)
                                                 ->build();
        
        $planning_factory->expectOnce('getPlanning', array($planning->getId()));
        $planning_factory->setReturnValue('getPlanning', $planning);
        
        $tracker_factory->setReturnValue('getTrackersByGroupId', array());
        
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

class Planning_ControllerUpdateTest extends TuleapTestCase {
    public function itUpdatesThePlanningAndRedirectsToTheIndex() {
        $group_id         = '34';
        $planning_id      = '12';
        $request          = new Codendi_Request(array('group_id' => $group_id));
        
        $request->set('planning_id', $planning_id);
        $request->set('planning_name', 'Release Planning');
        $request->set('planning_backlog_ids', array('1', '2'));
        $request->set('planning_release_id', '3');
        
        $planning_factory = new MockPlanningFactory();
        $controller       = aPlanningController()->with('request', $request)
                                                 ->with('planning_factory', $planning_factory)
                                                 ->build();
        
        $planning_factory->expectOnce('updatePlanning', array($planning_id, 'Release Planning', array('1', '2'), '3'));
        $this->expectRedirectTo('/plugins/agiledashboard/?group_id='.$group_id);
        $controller->update();
    }
}

?>
