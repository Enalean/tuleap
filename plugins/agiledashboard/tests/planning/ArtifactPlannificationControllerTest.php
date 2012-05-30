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
require_once(dirname(__FILE__).'/../../include/Planning/ArtifactPlannificationController.class.php');
require_once(dirname(__FILE__).'/../../include/Planning/Planning.class.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_Builder.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Test_Tracker_FormElement_Builder.php');
require_once(dirname(__FILE__).'/../../../tracker/tests/Tracker/CrossSearch/Test_CriteriaBuilder.php');
require_once(dirname(__FILE__).'/../builders/planning.php');
require_once(dirname(__FILE__).'/../builders/planning_factory.php');
require_once dirname(__FILE__).'/../builders/controller.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aRequest.php';

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
Mock::generate('Planning_ViewBuilder');



class Planning_ArtifactPlannificationControllerTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->request_uri = '/plugins/agiledashboard/';
        $this->planning_tracker_id = 66;
        $this->planning = new Planning(123, 'Stuff Backlog', $group_id = 103, 'Release Backlog', 'Sprint Plan', array(), $this->planning_tracker_id);
        $this->setText('-- Please choose', array('global', 'please_choose_dashed'));
        $this->setText('The artifact doesn\'t have an artifact link field, please reconfigure your tracker', array('plugin_tracker', 'must_have_artifact_link_field'));
        
        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        Tracker_Hierarchy_HierarchicalTrackerFactory::setInstance($hierarchy_factory);
    }
    
    public function tearDown() {
        parent::tearDown();
        
        
        Tracker_ArtifactFactory::clearInstance();
        Tracker_Hierarchy_HierarchicalTrackerFactory::clearInstance();
        TrackerFactory::clearInstance();
    }
    
    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $id      = 987;
        $title   = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title);
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
    
    public function itDoesNotAllowDragNDropIfArtifactDestinationHasNoArtifactLink() {
        $content = $this->WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField();
        $this->assertNoPattern('/class="[^"]*planning-droppable[^"]*"/', $content);
        $this->assertPattern('/The artifact doesn\'t have an artifact link field, please reconfigure your tracker/', $content);
    }
    
    public function itDoesNotShowAnyErrorIfThereIsNoArtifactGivenInTheRequest() {
        $this->WhenICaptureTheOutputOfShowActionWithoutArtifact();
        $this->assertNoErrors();
    }

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

        $content = $this->WhenICaptureTheOutputOfShowAction($request, $factory);
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
        $project_id                = 1111;
        $id                        = 987;
        $a_list_of_draggable_items = 'A list of draggable items';
        $project                   = stub('Project')->getId()->returns($project_id);
        $already_linked_items      = array();
        $factory                   = $this->GivenAnArtifactFactoryThatReturnsAnArtifact($id, $already_linked_items);
        $view_builder              = $this->GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, $expected_criteria, $already_linked_items, $a_list_of_draggable_items);
        $request                   = $this->buildRequest($id, $project_id, $shared_field_criteria, $semantic_criteria);

        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, array($project), new MockTracker_CrossSearch_Search());
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }

    private function GivenAnArtifactFactoryThatReturnsAnArtifact($id, $already_linked_items) {
        $artifact = $this->GivenAnArtifactWithArtifactLinkField($id, "screen hangs with macos and some escapable characters #<", $already_linked_items);
        return $this->GivenAnArtifactFactory(array($artifact));
    }


    private function GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, Tracker_CrossSearch_Query $expected_criteria, $already_linked_items, $content) {
        $content_view = $this->GivenAContentViewThatFetch($content);
        $tracker_ids  = array();
        $view_builder = new MockPlanning_ViewBuilder();
        $expected_arguments = array(
        	'*', 
            $project, 
            new EqualExpectation($expected_criteria), 
            $already_linked_items, 
            $tracker_ids,
            $this->planning, 
            '*' // TODO an assert on planning_redirect_param
        );
        $view_builder->expectOnce('build', $expected_arguments);
        $view_builder->setReturnValue('build', $content_view);

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

    private function GivenAnArtifactWithArtifactLinkField($id, $title, $already_linked_items) {
        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->userCanUpdate()->returns(true);
        $artifact = $this->GivenAnArtifact($id, $title, $already_linked_items);
        stub($artifact)->getAnArtifactLinkField()->returns($field);
        return $artifact;
    }
    
    private function GivenAnArtifact($id, $title, $already_linked_items) {
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('fetchTitle', "#$id $title");
        $artifact->setReturnValue('getId', $id);
        $artifact->setReturnValue('fetchDirectLinkToArtifact', $id);
        $artifact->setReturnValue('getLinkedArtifacts', $already_linked_items);
        return $artifact;
    }
    
    private function GivenAnArtifactWithNoLinkedItem($id, $title) {
        return $this->GivenAnArtifactWithArtifactLinkField($id, $title, array());
    }
    
    private function GivenAnArtifactFactory(array $artifacts = array()) {
        $open_artifacts = array(
            $this->GivenAnArtifactWithNoLinkedItem(1001, 'An open artifact'),
            $this->GivenAnArtifactWithNoLinkedItem(1002, 'Another open artifact'),
        );

        $factory  = new MockTracker_ArtifactFactory();
        Tracker_ArtifactFactory::setInstance($factory);
        foreach ($artifacts as $artifact) {
            $factory->setReturnValue('getArtifactByid', $artifact, array($artifact->getId()));
            $open_artifacts[] = $artifact;
        }
        $factory->setReturnValue(
            'getOpenArtifactsByTrackerId', 
            $open_artifacts, 
            array($this->planning->getPlanningTrackerId()));
        return $factory;
    }
    
    private function WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField() {
        $id    = 987;
        $title = 'Coin';
        
        $artifact = $this->GivenAnArtifact($id, $title, array());
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $request  = aRequest()->with('aid', $id)
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title) {
        $artifact = $this->GivenAnArtifactWithNoLinkedItem($id, $title);
        $factory  = $this->GivenAnArtifactFactory(array($artifact));
        $request  = aRequest()->with('aid', $id)
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        
        
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfShowActionWithoutArtifact() {
        $factory = $this->GivenAnArtifactFactory();
        $request = aRequest()->withUri($this->request_uri)
                             ->with('planning_id', $this->planning->getId())
                             ->build();
        
        return $this->WhenICaptureTheOutputOfShowAction($request, $factory);
    }
    
    private function WhenICaptureTheOutputOfShowAction($request, $factory) {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $content_view->setReturnValue('fetch', 'stuff');
        $view_builder = new MockPlanning_ViewBuilder();
        $view_builder->setReturnValue('build', $content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, array(), new MockTracker_CrossSearch_Search());
    }
    
    private function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $factory, $view_builder, array $projects, $search) {
        $project_manager = $this->GivenAProjectManagerThatReturns($projects);

        $planning_factory = new MockPlanningFactory();
        $planning_tracker = mock('Tracker');
        
        $this->planning->setPlanningTracker($planning_tracker);
        stub($planning_tracker)->getId()->returns($this->planning->getPlanningTrackerId());
        $planning_factory->setReturnValue('getPlanningWithTrackers', $this->planning, array($this->planning->getId()));
        
        $tracker_factory = new MockTrackerFactory();
        TrackerFactory::setInstance($tracker_factory);
        
        ob_start();
        $controller = new Planning_ArtifactPlannificationController($request, $factory, $planning_factory, new MockTrackerFactory());
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
?>
