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

$current_dir = dirname(__FILE__);

require_once $current_dir.'/../../include/constants.php';
require_once $current_dir.'/../../../tracker/include/constants.php';
require_once AGILEDASHBOARD_BASE_DIR .'/Planning/MilestoneController.class.php';
require_once AGILEDASHBOARD_BASE_DIR .'/Planning/Planning.class.php';
require_once AGILEDASHBOARD_BASE_DIR .'/Planning/NoMilestone.class.php';
require_once $current_dir.'/../../../tracker/tests/builders/aTracker.php';
require_once $current_dir.'/../../../tracker/tests/builders/aField.php';
require_once $current_dir.'/../../../tracker/tests/builders/aCrossSearchCriteria.php';
require_once $current_dir.'/../builders/aMilestone.php';
require_once $current_dir.'/../builders/aPlanning.php';
require_once $current_dir.'/../builders/aPlanningFactory.php';
require_once $current_dir.'/../builders/aPlanningController.php';
require_once $current_dir.'/../../../../tests/simpletest/common/include/builders/aRequest.php';
require_once AGILEDASHBOARD_BASE_DIR .'/Planning/ViewBuilder.class.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aMockArtifact.php';

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

class Planning_MilestoneControllerTest extends TuleapTestCase {
    private $planning;

    public function setUp() {
        parent::setUp();

        $this->request_uri = '/plugins/agiledashboard/';
        $this->saved_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $_SERVER['REQUEST_URI'] = $this->request_uri;
        
        
        $this->planning_tracker_id = 66;
        $this->planning = new Planning(123, 'Stuff Backlog', $group_id = 103, 'Release Backlog', 'Sprint Plan', null, $this->planning_tracker_id);
        $this->setText('-- Please choose', array('global', 'please_choose_dashed'));
        $this->setText('The artifact doesn\'t have an artifact link field, please reconfigure your tracker', array('plugin_tracker', 'must_have_artifact_link_field'));

        $this->milestone_factory = mock('Planning_MilestoneFactory');
        stub($this->milestone_factory)->getSiblingMilestones()->returns(array());
        stub($this->milestone_factory)->getAllMilestones()->returns(array());

        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        Tracker_Hierarchy_HierarchicalTrackerFactory::setInstance($hierarchy_factory);
        Tracker_HierarchyFactory::setInstance(mock('Tracker_HierarchyFactory'));
    }

    public function tearDown() {
        parent::tearDown();

        $_SERVER['REQUEST_URI'] = $this->saved_request_uri;

        Tracker_ArtifactFactory::clearInstance();
        Tracker_Hierarchy_HierarchicalTrackerFactory::clearInstance();
        TrackerFactory::clearInstance();
        Tracker_HierarchyFactory::clearInstance();
    }

    public function itExplicitlySaysThereAreNoItemsWhenThereIsNothing() {
        $aid     = 987;
        $title   = "screen hangs with macos";
        $content = $this->WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($aid, $title);
        $this->assertPattern('/No items yet/', $content);
        $this->assertPattern('/class="[^"]*planning-droppable[^"]*"/', $content);
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
        $view_builder              = $this->GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, $expected_criteria, $already_linked_items, $a_list_of_draggable_items);
        $request                   = $this->buildRequest($id, $project_id, $shared_field_criteria, $semantic_criteria);
        $milestone                 = $this->GivenNoMilestone($project);

        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $milestone, $view_builder, array($project), new MockTracker_CrossSearch_Search());
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }

    private function GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, Tracker_CrossSearch_Query $expected_criteria, $already_linked_items, $content) {
        $content_view = $this->GivenAContentViewThatFetch($content);
        $backlog_tracker_id  = null; // It's null because of NoMilestone in assertThatWeBuildAcontentViewWith
        $view_builder = new MockPlanning_ViewBuilder();
        $expected_arguments = array(
            '*',
            $project,
            new EqualExpectation($expected_criteria),
            $already_linked_items,
            $backlog_tracker_id,
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
        $tracker = stub('Tracker')->userCanView()->returns(true);
        stub($artifact)->getTracker()->returns($tracker);
        return $artifact;
    }

    private function GivenAnArtifact($id, $title, $already_linked_items) {
        $artifact = new MockTracker_Artifact();
        $artifact->setReturnValue('getTitle', $title);
        $artifact->setReturnValue('fetchTitle', "#$id $title");
        $artifact->setReturnValue('getId', $id);
        $artifact->setReturnValue('fetchDirectLinkToArtifact', $id);
        $artifact->setReturnValue('getUniqueLinkedArtifacts', $already_linked_items);
        $artifact->setReturnValue('userCanView', true);
        $artifact->setReturnValue('getAllowedChildrenTypes', array());

        $tracker = stub('Tracker')->userCanView()->returns(true);
        stub($artifact)->getTracker()->returns($tracker);

        return $artifact;
    }

    private function GivenAnArtifactWithNoLinkedItem($id, $title) {
        return $this->GivenAnArtifactWithArtifactLinkField($id, $title, array());
    }

    private function GivenAMilestone($artifact) {
        $milestone = mock('Planning_Milestone');
        $root_node = new ArtifactNode($artifact);

        stub($milestone)->getArtifact()->returns($artifact);
        stub($milestone)->getPlannedArtifacts()->returns($root_node);
        stub($milestone)->userCanView()->returns(true);
        stub($milestone)->getPlanning()->returns($this->planning);
        stub($milestone)->getProject()->returns(mock('Project'));
        stub($milestone)->getAncestors()->returns(array());
        
        return $milestone;
    }

    private function GivenNoMilestone($project) {
        return new Planning_NoMilestone($project, $this->planning);
    }

    private function WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField() {
        $id    = 987;
        $title = 'Coin';

        $artifact = $this->GivenAnArtifact($id, $title, array());
        $request  = aRequest()->with('aid', $id)
                              ->with('group_id', $this->planning->getGroupId())
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        $milestone = $this->GivenAMilestone($artifact);
        $user = aUser()->build();
        
        return $this->WhenICaptureTheOutputOfShowAction($request, $milestone);
    }

    private function WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title) {
        $artifact = $this->GivenAnArtifactWithNoLinkedItem($id, $title);
        $request  = aRequest()->with('aid', $id)
                              ->with('group_id', $this->planning->getGroupId())
                              ->with('planning_id', $this->planning->getId())
                              ->withUri($this->request_uri)
                              ->build();
        $milestone = $this->GivenAMilestone($artifact);
        $user = aUser()->build();
                
        return $this->WhenICaptureTheOutputOfShowAction($request, $milestone);
    }

    private function WhenICaptureTheOutputOfShowActionWithoutArtifact() {
        $milestone = $this->GivenNoMilestone(mock('Project'));
        $request = aRequest()->withUri($this->request_uri)
                             ->with('group_id', $this->planning->getGroupId())
                             ->with('planning_id', $this->planning->getId())
                             ->withUser(aUser()->build())
                             ->build();
        return $this->WhenICaptureTheOutputOfShowAction($request, $milestone);
    }

    private function WhenICaptureTheOutputOfShowAction($request, $milestone) {
        $content_view = new MockTracker_CrossSearch_SearchContentView();
        $content_view->setReturnValue('fetch', 'stuff');
        $view_builder = new MockPlanning_ViewBuilder();
        $view_builder->setReturnValue('build', $content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $milestone, $view_builder, array(), new MockTracker_CrossSearch_Search());
    }

    private function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $milestone, $view_builder, array $projects, $search) {
        $project_manager = $this->GivenAProjectManagerThatReturns($projects);

        $tracker_factory = new MockTrackerFactory();
        TrackerFactory::setInstance($tracker_factory);

        stub($this->milestone_factory)->getMilestoneWithPlannedArtifactsAndSubMilestones($request->getCurrentUser(),
                                                                                        $project_manager->getProject($request->get('group_id')),
                                                                                        $request->get('planning_id'),
                                                                                        $request->get('aid'))
                                      ->returns($milestone);

        ob_start();
        $controller = new Planning_MilestoneController($request, $this->milestone_factory, $project_manager);

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

class MilestoneController_BreadcrumbsTest extends TuleapTestCase {
    private $plugin_path;
    private $product;
    private $release;
    private $sprint;

    public function setUp() {
        parent::setUp();

        $this->plugin_path = '/plugin/path';

        $this->product     = aMilestone()->withArtifact(aMockArtifact()->withId(1)->withTitle('Product X')->build())->build();
        $this->release     = aMilestone()->withArtifact(aMockArtifact()->withId(2)->withTitle('Release 1.0')->build())->build();
        $this->sprint      = aMilestone()->withArtifact(aMockArtifact()->withId(3)->withTitle('Sprint 1')->build())->build();
        
        $this->milestone_factory = mock('Planning_MilestoneFactory');
        $this->project_manager   = mock('ProjectManager');

        $this->current_user   = aUser()->build();
        $this->request        = aRequest()->withUser($this->current_user)->build();
    }

    public function itHasNoBreadCrumbWhenThereIsNoMilestone() {
        stub($this->milestone_factory)->getMilestoneWithPlannedArtifactsAndSubMilestones()->returns(mock('Planning_NoMilestone'));
        
        $controller = new Planning_MilestoneController($this->request, $this->milestone_factory, $this->project_manager);
        $breadcrumb = $controller->getBreadcrumbs($this->plugin_path);
        $this->assertIsA($breadcrumb, 'BreadCrumb_NoCrumb');
    }

    public function itIncludesBreadcrumbsForParentMilestones() {
        $this->sprint->setAncestors(array($this->release, $this->product));
        stub($this->milestone_factory)->getMilestoneWithPlannedArtifactsAndSubMilestones()->returns($this->sprint);

        $controller  = new Planning_MilestoneController($this->request, $this->milestone_factory, $this->project_manager);

        $breadcrumbs = $controller->getBreadcrumbs($this->plugin_path);
        $this->assertEqualToBreadCrumbWithAllMilestones($breadcrumbs);
    }

    public function assertEqualToBreadCrumbWithAllMilestones($breadcrumbs) {
        $expected_crumbs = new BreadCrumb_Merger(
            new BreadCrumb_Milestone($this->plugin_path, $this->product),
            new BreadCrumb_Milestone($this->plugin_path, $this->release),
            new BreadCrumb_Milestone($this->plugin_path, $this->sprint)
        );
        $this->assertEqual($expected_crumbs, $breadcrumbs);
    }
}

class Planning_MilestoneControllerTrapPresenter extends Planning_MilestoneController {
    public $template_name;
    public $presenter;
    
    protected function render($template_name, $presenter) {
        $this->template_name = $template_name;
        $this->presenter      = $presenter;
    }
}

class MilestoneController_AvailableMilestonesTest extends TuleapTestCase {

    private $sprint_1;
    private $sprint_2;
    private $milestone_factory;
    private $controller;
    private $request;
    private $project_manager;

    public function setUp() {
        parent::setUp();

        $this->sprint_1 = mock('Planning_Milestone');
        stub($this->sprint_1)->getArtifactId()->returns(1);
        stub($this->sprint_1)->getArtifactTitle()->returns('Sprint 1');
        stub($this->sprint_1)->getPlanning()->returns(aPlanning()->build());
        stub($this->sprint_1)->getLinkedArtifacts()->returns(array());
        stub($this->sprint_1)->hasAncestors()->returns(true);
        $this->sprint_2 = aMilestone()->withArtifact(aMockArtifact()->withId(2)->withTitle('Sprint 2')->build())->build();

        $this->milestone_factory = mock('Planning_MilestoneFactory');
        $this->project_manager   = stub('ProjectManager')->getProject()->returns(mock('Project'));

        $this->current_user = aUser()->build();
        $this->request = aRequest()->withUser($this->current_user)->build();

        Tracker_HierarchyFactory::setInstance(mock('Tracker_HierarchyFactory'));
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_HierarchyFactory::clearInstance();
    }

    public function itDisplaysOnlySiblingsMilestones() {
        stub($this->milestone_factory)->getMilestoneWithPlannedArtifactsAndSubMilestones()->returns($this->sprint_1);
        stub($this->milestone_factory)->getAllMilestones()->returns(array());
        stub($this->milestone_factory)->getSiblingMilestones()->returns(array($this->sprint_1, $this->sprint_2));
        $this->controller = new Planning_MilestoneControllerTrapPresenter($this->request, $this->milestone_factory, $this->project_manager);

        $selectable_artifacts = $this->getSelectableArtifacts();
        $this->assertCount($selectable_artifacts, 2);
        $this->assertEqual(array_shift($selectable_artifacts), array('id' => 1, 'title' => 'Sprint 1', 'selected' => 'selected="selected"'));
        $this->assertEqual(array_shift($selectable_artifacts), array('id' => 2, 'title' => 'Sprint 2', 'selected' => ''));
    }

    public function itDisplaysASelectorOfArtifactWhenThereAreNoMilestoneSelected() {
        $project = mock('Project');
        $planning = mock('Planning');
        $current_milstone = new Planning_NoMilestone($project, $planning);

        $milstone_1001 = aMilestone()->withArtifact(aMockArtifact()->withId(1001)->withTitle('An open artifact')->build())->build();
        $milstone_1002 = aMilestone()->withArtifact(aMockArtifact()->withId(1002)->withTitle('Another open artifact')->build())->build();

        stub($this->milestone_factory)->getMilestoneWithPlannedArtifactsAndSubMilestones()->returns($current_milstone);
        stub($this->milestone_factory)->getAllMilestones($this->current_user, $planning)->returns(array($milstone_1001, $milstone_1002));
        $this->controller = new Planning_MilestoneControllerTrapPresenter($this->request, $this->milestone_factory, $this->project_manager);

        $selectable_artifacts = $this->getSelectableArtifacts();
        $this->assertCount($selectable_artifacts, 2);
        $this->assertEqual(array_shift($selectable_artifacts), array('id' => 1001, 'title' => 'An open artifact', 'selected' => ''));
        $this->assertEqual(array_shift($selectable_artifacts), array('id' => 1002, 'title' => 'Another open artifact', 'selected' => ''));
    }

    private function getSelectableArtifacts() {
        $planning_view_builder = stub('Planning_ViewBuilder')->build()->returns(mock('Tracker_CrossSearch_SearchContentView'));
        $this->controller->show($planning_view_builder);
        return $this->controller->presenter->selectableArtifacts();
    }

}

?>
