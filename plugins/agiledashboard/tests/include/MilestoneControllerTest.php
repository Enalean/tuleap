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

require_once dirname(__FILE__).'/../common.php';

class Planning_MilestoneController4Tests extends Planning_MilestoneController {
    public $output = null;

    public function render($template_name, $presenter) {
        $this->output = $this->renderer->renderToString($template_name, $presenter);
    }
}

abstract class Planning_MilestoneController_Common extends TuleapTestCase {
    protected $planning_tracker_id;
    protected $planning;
    protected $milestone_factory;

    public function setUp() {
        parent::setUp();

        Config::store();
        Config::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $this->planning_tracker_id = 66;
        $this->planning = new Planning(123, 'Stuff Backlog', $group_id = 103, 'Release Backlog', 'Sprint Plan', null, $this->planning_tracker_id);

        $this->milestone_factory = mock('Planning_MilestoneFactory');
        stub($this->milestone_factory)->getSiblingMilestones()->returns(array());
        stub($this->milestone_factory)->getAllMilestones()->returns(array());

        EventManager::setInstance(mock('EventManager'));
    }

    public function tearDown() {
        Config::restore();
        EventManager::clearInstance();
        parent::tearDown();
    }

    protected function WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $milestone, $view_builder) {
        stub($this->milestone_factory)->getMilestonePlan()->returns(aMilestonePlan()->withMilestone($milestone)->build());

        stub($this->milestone_factory)->getBareMilestone()
                                      ->returns($milestone);

        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getHierarchy()->returns(new Tracker_Hierarchy());

        $view_builder_factory = stub('Planning_ViewBuilderFactory')->getViewBuilder()->returns($view_builder);

        $legacy_planning_pane_factory = new Planning_MilestoneLegacyPlanningPaneFactory(
            $request,
            $this->milestone_factory,
            $hierarchy_factory,
            $view_builder_factory,
            ''
        );

        $pane_factory = new Planning_MilestonePaneFactory(
            $request,
            $this->milestone_factory,
            mock('AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory'),
            $legacy_planning_pane_factory,
            mock('AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder'),
            ''
        );

        $controller = new Planning_MilestoneController4Tests(
            $request,
            $this->milestone_factory,
            mock('ProjectManager'),
            $pane_factory,
            mock('AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory'),
            ''
        );
        $controller->show();
        return $controller->output;
    }
}

class Planning_MilestoneController_EmptyBacklogTest extends Planning_MilestoneController_Common {

    public function setUp() {
        parent::setUp();
        $this->setText('The artifact doesn\'t have an artifact link field, please reconfigure your tracker', array('plugin_tracker', 'must_have_artifact_link_field'));
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

    private function WhenICaptureTheOutputOfShowActionForAnEmptyArtifact($id, $title) {
        $artifact = $this->GivenAnArtifactWithNoLinkedItem($id, $title);
        $request  = aRequest()->with('aid', $id)
                              ->with('group_id', $this->planning->getGroupId())
                              ->with('planning_id', $this->planning->getId())
                              ->build();
        $milestone = $this->GivenAMilestone($artifact);

        return $this->WhenICaptureTheOutputOfShowAction($request, $milestone);
    }

    private function WhenICaptureTheOutputOfShowActionForAnArtifactWithoutArtifactLinkField() {
        $id    = 987;
        $title = 'Coin';

        $artifact = $this->GivenAnArtifact($id, $title, array());
        $request  = aRequest()->with('aid', $id)
                              ->with('group_id', $this->planning->getGroupId())
                              ->with('planning_id', $this->planning->getId())
                              ->build();
        $milestone = $this->GivenAMilestone($artifact);

        return $this->WhenICaptureTheOutputOfShowAction($request, $milestone);
    }


    private function GivenAnArtifactWithNoLinkedItem($id, $title) {
        $field    = stub('Tracker_FormElement_Field_ArtifactLink')->userCanUpdate()->returns(true);
        $artifact = $this->GivenAnArtifact($id, $title, array());
        stub($artifact)->getAnArtifactLinkField()->returns($field);
        return $artifact;
    }

     private function GivenAnArtifact($id, $title, $already_linked_items) {
        $tracker  = stub('Tracker')->userCanView()->returns(true);
        
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getTitle()->returns($title);
        stub($artifact)->fetchTitle()->returns("#$id $title");
        stub($artifact)->getId()->returns($id);
        stub($artifact)->fetchDirectLinkToArtifact()->returns($id);
        stub($artifact)->getUniqueLinkedArtifacts()->returns($already_linked_items);
        stub($artifact)->userCanView()->returns(true);
        stub($artifact)->getAllowedChildrenTypes()->returns(array());
        stub($artifact)->getTracker()->returns($tracker);

        return $artifact;
    }

     private function GivenAMilestone($artifact) {
        $milestone = mock('Planning_ArtifactMilestone');
        $root_node = new ArtifactNode($artifact);

        stub($milestone)->getArtifact()->returns($artifact);
        stub($milestone)->getPlannedArtifacts()->returns($root_node);
        stub($milestone)->userCanView()->returns(true);
        stub($milestone)->getPlanning()->returns($this->planning);
        stub($milestone)->getProject()->returns(mock('Project'));
        stub($milestone)->getAncestors()->returns(array());

        return $milestone;
    }

    private function WhenICaptureTheOutputOfShowAction($request, $milestone) {
        $content_view = mock('Tracker_CrossSearch_SearchContentView');
        $view_builder = stub('Planning_ViewBuilder')->build()->returns($content_view);
        return $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $milestone, $view_builder);
    }
}


class Planning_MilestoneController_CrossTrackerSearchTest extends Planning_MilestoneController_Common {

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
        $artifact_id               = 987;
        $a_list_of_draggable_items = 'A list of draggable items';
        $project                   = stub('Project')->getId()->returns($project_id);
        $already_linked_items      = array();
        $view_builder              = $this->GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, $expected_criteria, $already_linked_items, $a_list_of_draggable_items);
        $request                   = $this->buildRequest($artifact_id, $project_id, $shared_field_criteria, $semantic_criteria);
        $milestone                 = mock('Planning_ArtifactMilestone');
        stub($milestone)->userCanView()->returns(true);
        stub($milestone)->getPlanning()->returns($this->planning);
        stub($milestone)->getProject()->returns($project);

        $content = $this->WhenICaptureTheOutputOfShowActionWithViewBuilder($request, $milestone, $view_builder);
        $this->assertPattern("/$a_list_of_draggable_items/", $content);
    }

    private function GivenAViewBuilderThatBuildAPlanningSearchContentViewThatFetchContent($project, Tracker_CrossSearch_Query $expected_criteria, $already_linked_items, $content) {
        $content_view = stub('Tracker_CrossSearch_SearchContentView')->fetch()->returns($content);
        $backlog_tracker_ids  = array(); // It's null because of NoMilestone in assertThatWeBuildAcontentViewWith
        $view_builder = new MockPlanning_ViewBuilder();
        $expected_arguments = array(
            '*',
            $project,
            new EqualExpectation($expected_criteria),
            $already_linked_items,
            $backlog_tracker_ids,
            $this->planning,
            '*',
            '*'
        );
        $view_builder->expectOnce('build', $expected_arguments);
        $view_builder->setReturnValue('build', $content_view);

        return $view_builder;
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
                             ->build();
        return $request;
    }
}

class MilestoneController_BreadcrumbsTest extends TuleapTestCase {
    private $plugin_path;
    private $product;
    private $release;
    private $sprint;

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $this->plugin_path = '/plugin/path';

        $this->product     = aMilestone()->withArtifact(aMockArtifact()->withId(1)->withTitle('Product X')->build())->build();
        $this->release     = aMilestone()->withArtifact(aMockArtifact()->withId(2)->withTitle('Release 1.0')->build())->build();
        $this->sprint      = aMilestone()->withArtifact(aMockArtifact()->withId(3)->withTitle('Sprint 1')->build())->build();
        $this->nomilestone = stub('Planning_NoMilestone')->getPlanning()->returns(mock('Planning'));

        $this->milestone_factory = mock('Planning_MilestoneFactory');
        $this->project_manager   = mock('ProjectManager');

        $this->current_user   = aUser()->build();
        $this->request        = aRequest()->withUser($this->current_user)->build();
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

    public function itHasNoBreadCrumbWhenThereIsNoMilestone() {
        stub($this->milestone_factory)->getBareMilestone()->returns($this->nomilestone);

        $breadcrumbs = $this->getBreadcrumbs();
        $this->assertIsA($breadcrumbs, 'BreadCrumb_NoCrumb');
    }

    public function itIncludesBreadcrumbsForParentMilestones() {
        $this->sprint->setAncestors(array($this->release, $this->product));
        stub($this->milestone_factory)->getBareMilestone()->returns($this->sprint);

        $breadcrumbs = $this->getBreadcrumbs();
        $this->assertEqualToBreadCrumbWithAllMilestones($breadcrumbs);
    }

    private function getBreadcrumbs() {
        $controller = partial_mock(
            'Planning_MilestoneController',
            array('buildContentView'),
            array(
                $this->request,
                $this->milestone_factory,
                $this->project_manager,
                mock('Planning_MilestonePaneFactory'),
                mock('AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory'),
                ''
            )
        );
        return $controller->getBreadcrumbs($this->plugin_path);
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


?>
