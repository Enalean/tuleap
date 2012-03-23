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
require_once(dirname(__FILE__).'/../../include/Planning/ArtifactPlannificationController.class.php');
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
Mock::generate('TrackerFactory');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('PlanningFactory');
Mock::generate('Planning');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_SearchContentView');
Mock::generate('Tracker_CrossSearch_ViewBuilder');



class ArtifactPlannificationControllerTest extends TuleapTestCase {
    
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
            $this->GivenAnArtifactWithNoLinkedItem(123, 'Tutu'),
            $this->GivenAnArtifactWithNoLinkedItem(123, 'Tata')
        );
        
        $artifact = $this->GivenAnArtifact($id, 'Toto', $linked_items);
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
        $request_params     = array(
            'aid'         => $id,
            'planning_id' => $this->planning->getId(),
            'group_id'    => $project_id,
        );
        
        if ($requested_criteria !== null) {
            $request_params['criteria'] = $requested_criteria;
        }
        $request  = new Codendi_Request($request_params);
        
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $a_list_of_draggable_items = 'A list of draggable items';
        $content_view->setReturnValue('fetch', $a_list_of_draggable_items);
        
        $project = new MockProject();
        $project_manager = $this->GivenAProjectManagerThatReturns($project, $project_id);
        
        $already_linked_items = array();
        $tracker_ids  = array();
        
        $view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $view_builder->expectOnce('buildPlanningContentView', array($project, $expected_criteria, $already_linked_items, $tracker_ids));
        $view_builder->setReturnValue('buildPlanningContentView', $content_view);
        
        $artifact = $this->GivenAnArtifact($id, "screen hangs with macos and some escapable characters #<", $already_linked_items);
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, new MockTracker_CrossSearch_Search());
        
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }
    
    private function GivenAnArtifact($id, $title, $already_linked_items) {
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('fetchTitle', "#$id $title");
        $artifact->setReturnValue('getId', $id);
        $artifact->setReturnValue('getLinkedArtifacts', $already_linked_items);
        return $artifact;
    }
    
    private function GivenAnArtifactWithNoLinkedItem($id, $title) {
        return $this->GivenAnArtifact($id, $title, array());
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
            array($this->planning->getPlanningTrackerId()));
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
        $view_builder->setReturnValue('buildPlanningContentView', $content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, new MockProjectManager(), new MockTracker_CrossSearch_Search());
    }
    
    private function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, $project_manager, $search) {
        $planning_factory = new MockPlanningFactory();
        $planning_factory->setReturnValue('getPlanning', $this->planning, array($this->planning->getId()));
        
        ob_start();
        $controller = new Planning_ArtifactPlannificationController($request, $factory, $planning_factory, new MockTrackerFactory());
        $controller->show($view_builder, $project_manager, $search);
        $content = ob_get_clean();
        return $content;
    }

    public function GivenAProjectManagerThatReturns($project, $project_id) {
        $project_manager = new MockProjectManager();
        $project_manager->setReturnValue('getProject', $project, array($project_id));
        return $project_manager;
    }
}
?>
