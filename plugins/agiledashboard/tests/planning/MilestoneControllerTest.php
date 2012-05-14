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

require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once(dirname(__FILE__).'/../../include/Planning/MilestoneController.class.php');
require_once(dirname(__FILE__).'/../../include/Planning/Planning.class.php');
require_once(dirname(__FILE__).'/../../include/Planning/ViewBuilder.class.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/builders/aTracker.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/builders/aField.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/builders/aCrossSearchCriteria.php');
require_once(dirname(__FILE__).'/../builders/aPlanning.php');
require_once(dirname(__FILE__).'/../builders/aPlanningFactory.php');
require_once dirname(__FILE__).'/../builders/aPlanningController.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aRequest.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/aMockArtifact.php';


class Planning_MilestoneController_TestCase extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->request_uri         = '/plugins/agiledashboard/';
        $this->planning_tracker_id = 66;
        $this->planning            = new Planning(123,
                                                  'Stuff Backlog',
                                                  $group_id = 103,
                                                  'Release Backlog',
                                                  'Sprint Plan',
                                                  array(),
                                                  $this->planning_tracker_id);
        
        $this->setText('-- Please choose',
                       array('global', 'please_choose_dashed'));
        $this->setText('The artifact doesn\'t have an artifact link field, please reconfigure your tracker',
                       array('plugin_tracker', 'must_have_artifact_link_field'));
        
        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        Tracker_Hierarchy_HierarchicalTrackerFactory::setInstance($hierarchy_factory);
    }
    
    public function tearDown() {
        parent::tearDown();
        
        Tracker_ArtifactFactory::clearInstance();
        Tracker_Hierarchy_HierarchicalTrackerFactory::clearInstance();
        TrackerFactory::clearInstance();
    }
    
    protected function GivenNoMilestone() {
        return new Planning_NoMilestone($this->planning->getGroupId(),
                                        $this->planning);
    }
    
    protected function GivenAnArtifactFactory(array $artifacts = array()) {
        $open_artifacts = array(
            $this->GivenAnArtifactWithNoLinkedItem(1001, 'An open artifact'),
            $this->GivenAnArtifactWithNoLinkedItem(1002, 'Another open artifact'),
        );

        $factory  = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($factory);
        foreach ($artifacts as $artifact) {
            stub($factory)->getArtifactByid($artifact->getId())->returns($artifact);
            $open_artifacts[] = $artifact;
        }
        stub($factory)->getOpenArtifactsByTrackerIdUserCanView(aUser()->build(),
                                                               $this->planning->getPlanningTrackerId())
                      ->returns($open_artifacts);
        return $factory;
    }
    
    protected function GivenAnArtifactWithNoLinkedItem($id, $title) {
        return $this->GivenAnArtifactWithArtifactLinkField($id, $title, array());
    }

    protected function GivenAnArtifactWithArtifactLinkField($id, $title, $already_linked_items) {
        $artifact = aMockArtifact()->withId($id)
                                   ->withTitle($title)
                                   ->withLinkedArtifacts($already_linked_items)
                                   ->withReadPermission()
                                   ->withUpdatePermissionOnArtifactLinkField()
                                   ->build();
        return $artifact;
    }
    
    protected function GivenAMilestone($artifact) {
        $milestone = mock('Planning_Milestone');
        $root_node = new TreeNode(array('id'    => $artifact->getId(),
                                        'title' => $artifact->getTitle()));
        $root_node->setId($artifact->getId());
        
        stub($milestone)->getArtifact()->returns($artifact);
        stub($milestone)->getPlannedArtifacts()->returns($root_node);
        stub($milestone)->userCanView()->returns(true);
        
        return $milestone;
    }
    
    protected function WhenICaptureTheOutputOfShowAction($request, $factory, $milestone) {
        $content_view = mock('Tracker_CrossSearch_SearchContentView');
        stub($content_view)->fetch()->returns('stuff');
        $view_builder = mock('Planning_ViewBuilder');
        stub($view_builder)->build()->returns($content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $milestone, $view_builder, array(), mock('Tracker_CrossSearch_Search'));
    }
    
    protected function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $milestone, $view_builder, array $projects, $search) {
        $project_manager = $this->GivenAProjectManagerThatReturns($projects);

        $planning_factory = mock('PlanningFactory');
        $planning_tracker = mock('Tracker');
        
        $this->planning->setPlanningTracker($planning_tracker);
        stub($planning_tracker)->getId()
                               ->returns($this->planning->getPlanningTrackerId());
        stub($planning_factory)->getPlanningWithTrackers($this->planning->getId())
                               ->returns($this->planning);
        
        $tracker_factory = mock('TrackerFactory');
        TrackerFactory::setInstance($tracker_factory);
        
        $milestone_factory = mock('Planning_MilestoneFactory');
        stub($milestone_factory)->getMilestoneWithPlannedArtifacts($request->getCurrentUser(),
                                                                   $request->get('group_id'),
                                                                   $request->get('planning_id'),
                                                                   $request->get('aid'))
                                ->returns($milestone);
        
        ob_start();
        $controller = new Planning_MilestoneController($request, $factory, $planning_factory, $milestone_factory);
        
        $controller->show($view_builder, $project_manager, $search);
        $content = ob_get_clean();
        return $content;
    }

    private function GivenAProjectManagerThatReturns(array $projects) {
        $project_manager = mock('ProjectManager');
        foreach ($projects as $project) {
            stub($project_manager)->getProject($project->getId())->returns($project);
        }
        return $project_manager;
    }
}

class Planning_MilestoneController_EmptyMilestoneTest extends Planning_MilestoneController_TestCase {
    
    private function WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title) {
        $artifact = $this->GivenAnArtifactWithNoLinkedItem($id, $title);
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $request  = aRequest()->with('aid', $id)
                              ->with('group_id', $this->planning->getGroupId())
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        $milestone = $this->GivenAMilestone($artifact);
        
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory, $milestone);
    }
    
    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $aid     = 987;
        $title   = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($aid, $title);
        $this->assertPattern('/No items yet/', $content);
        $this->assertPattern('/class="[^"]*planning-droppable[^"]*"/', $content);
    }
    
    public function itDisplaysTheArtifactTitleAndId() {
        $id             = 987;
        $title          = "screen hangs with macos and some escapable characters #<";
        $expected_title = Codendi_HTMLPurifier::instance()->purify($title);

        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title);

        $this->assertPattern("/art-$id/", $content);
        $this->assertPattern("/$expected_title/", $content);
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
}

class Planning_MilestoneController_NoMilestoneTest extends Planning_MilestoneController_TestCase {
    
    private function WhenICaptureTheOutputOfShowActionWithoutArtifact() {
        $milestone = $this->GivenNoMilestone();
        $factory = $this->GivenAnArtifactFactory();
        $request = aRequest()->withUri($this->request_uri)
                             ->with('group_id', $this->planning->getGroupId())
                             ->with('planning_id', $this->planning->getId())
                             ->withUser(aUser())
                             ->build();
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory, $milestone);
    }
    
    public function itDoesNotShowAnyErrorIfThereIsNoArtifactGivenInTheRequest() {
        $this->WhenICaptureTheOutputOfShowActionWithoutArtifact();
        $this->assertNoErrors();
    }
}

class Planning_MilestoneController_NoArtifactLinkFieldTest extends Planning_MilestoneController_TestCase {
    
    private function WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField() {
        $id    = 987;
        $title = 'Coin';
        
        $artifact = aMockArtifact()->withId($id)
                                   ->withTitle($title)
                                   ->withLinkedArtifacts(array())
                                   ->withReadPermission()
                                   ->build();
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $request  = aRequest()->with('aid', $id)
                              ->with('group_id', $this->planning->getGroupId())
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        $milestone = $this->GivenAMilestone($artifact);
        
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory, $milestone);
    }
    
    public function itDisplaysAnErrorMessage() {
        $content = $this->WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField();
        $this->assertPattern('/The artifact doesn\'t have an artifact link field, please reconfigure your tracker/', $content);
    }
    
    public function itDoesNotAllowDragAndDrop() {
        $content = $this->WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField();
        $this->assertNoPattern('/class="[^"]*planning-droppable[^"]*"/', $content);
    }
}

class Planning_MilestoneController_OldTest extends Planning_MilestoneController_TestCase {

    public function itListsAllLinkedItems() {
        $id = 987;
        $linked_items = array(
            $this->GivenAnArtifactWithNoLinkedItem(123, 'Tutu'),
            $this->GivenAnArtifactWithNoLinkedItem(124, 'Tata')
        );
        
        $artifact = $this->GivenAnArtifactWithArtifactLinkField($id, 'Toto', $linked_items);
        $factory  = $this->GivenAnArtifactFactory(array_merge(array($artifact), $linked_items));
        $request  = aRequest()->with('aid', $id)
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        $milestone = $this->GivenNoMilestone();

        $content = $this->WhenICaptureTheOutputOfShowAction($request, $factory, $milestone);
        $this->assertPattern('/Tutu/', $content);
        $this->assertPattern('/Tata/', $content);
    }
    
    public function itDisplaysTheSearchContentView() {
        $shared_fields_criteria = array('220' => array('values' => array('toto', 'titi')));
        $semantic_criteria      = array('title' => 'bonjour', 'status' => Tracker_CrossSearch_SemanticStatusReportField::STATUS_CLOSED);
        $expected_criteria = aCrossSearchCriteria()
                            ->withSharedFieldsCriteria(array('220' => array('values' => array('toto', 'titi'))))
                            ->withSemanticCriteria($semantic_criteria)
                            ->build();
        $this->assertThatWeBuildAcontentViewWith($shared_fields_criteria, $semantic_criteria, $expected_criteria);
    }
    
    public function itAssumesNoCriteriaIfRequestedCriterieIsAbsent() {
        $shared_fields_criteria = $semantic_criteria = array();
        $expectedCriteria       = aCrossSearchCriteria()
                                  ->withSharedFieldsCriteria($shared_fields_criteria)
                                  ->withSemanticCriteria($semantic_criteria)
                                  ->build();
        $this->assertThatWeBuildAcontentViewWith($shared_fields_criteria, $semantic_criteria, $expectedCriteria);
    }
     
    public function itAssumesNoCriteriaIfRequestedCriterieIsNotValid() {
        $shared_fields_criteria = array('invalid parameter type');
        $semantic_criteria      = array('another invalid parameter type');
        $expectedCriteria       = aCrossSearchCriteria()
                                  ->withSharedFieldsCriteria($shared_fields_criteria)
                                  ->withSemanticCriteria($semantic_criteria)
                                  ->build();
        $this->assertThatWeBuildAcontentViewWith($shared_fields_criteria, $semantic_criteria, $expectedCriteria);
    }
    

    private function assertThatWeBuildAcontentViewWith($shared_field_criteria, $semantic_criteria, Tracker_CrossSearch_Query $expected_criteria) {
        $project_id                = $this->planning->getGroupId();
        $id                        = 987;
        $a_list_of_draggable_items = 'A list of draggable items';
        $project                   = stub('Project')->getId()->returns($project_id);
        $already_linked_items      = array();
        $factory                   = $this->GivenAnArtifactFactoryThatReturnsAnArtifact($id, $already_linked_items);
        $view_builder              = $this->GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, $expected_criteria, $already_linked_items, $a_list_of_draggable_items);
        $request                   = $this->buildRequest($id, $project_id, $shared_field_criteria, $semantic_criteria);
        $milestone                 = $this->GivenNoMilestone();

        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $milestone, $view_builder, array($project), mock('Tracker_CrossSearch_Search'));
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }

    private function GivenAnArtifactFactoryThatReturnsAnArtifact($id, $already_linked_items) {
        $artifact = $this->GivenAnArtifactWithArtifactLinkField($id, "screen hangs with macos and some escapable characters #<", $already_linked_items);
        return $this->GivenAnArtifactFactory(array($artifact));
    }


    private function GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, Tracker_CrossSearch_Query $expected_criteria, $already_linked_items, $content) {
        $content_view = $this->GivenAContentViewThatFetch($content);
        $tracker_ids  = array();
        $view_builder = mock('Planning_ViewBuilder');
        
        expect($view_builder)->build('*', 
                                     $project,
                                     new EqualExpectation($expected_criteria), 
                                     $already_linked_items, 
                                     $tracker_ids,
                                     $this->planning, 
                                     '*')
                             ->returns($content_view); // TODO an assert on planning_redirect_param

        return $view_builder;
    }

    private function GivenAContentViewThatFetch($content) {
        $content_view = stub('Tracker_CrossSearch_SearchContentView')->fetch()->returns($content);
        return $content_view;
    }

    private function buildRequest($aid, $project_id, $shared_field_criteria, $semantic_criteria) {
        $request_params = array(
            'aid'         => $aid,
            'planning_id' => $this->planning->getId(),
            'group_id'    => $project_id,
            'criteria'    => $shared_field_criteria,
            'semantic_criteria' => $semantic_criteria,
        );

        $request = aRequest()->withParams($request_params)
                             ->withUri($this->request_uri)
                             ->build();
        return $request;
    }
}
?>
