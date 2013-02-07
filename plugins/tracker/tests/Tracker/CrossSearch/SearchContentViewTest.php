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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';
class Tracker_CrossSearch_SearchContentViewTest extends TuleapTestCase {
    
    //TODO this exercises the same code as itIncludesSearchResults but it documents something else, what to do?
    public function itDoesNotTryToRetrieveSharedFieldOriginForSemanticStatus() {
        $artifact          = $this->givenThereIsAnArtifact();
        $tree_of_artifacts = $this->buildTreeWithArtifact($artifact);
        $artifact_factory  = $this->buildAnArtifactFactoryThatReturns($artifact);
        
        $this->fetchViewContent($tree_of_artifacts, $artifact_factory);
    }
    
    public function itIncludesTheSearchCriteria() {
        $criteria_markup = 'some report markup';
        $report = stub('Tracker_Report')->fetchDisplayQuery()->returns($criteria_markup);

        $output = $this->fetchViewContent(new TreeNode(), mock('Tracker_ArtifactFactory'), $report);
        $this->assertStringContains($output, $criteria_markup);
    }
    
    public function itIncludesTheSearchResults() {
        $artifact          = $this->givenThereIsAnArtifact();
        $artifact_factory  = $this->buildAnArtifactFactoryThatReturns($artifact);
        $tree_of_artifacts = $this->buildTreeWithArtifact($artifact);
        
        $output = $this->fetchViewContent($tree_of_artifacts, $artifact_factory);
        $this->assertStringContains($output, $artifact->getId());
    }
    
    private function givenThereIsAnArtifact() {
        return anArtifact()->withId(123)->withTracker(mock('Tracker'))->build();
    }
    
    private function fetchViewContent($tree_of_artifacts, $artifact_factory, $report = null) {
        $report   = $report ? $report : mock('Tracker_Report');
        $criteria = $this->buildCriteria($report);
        $factory  = $this->buildAFormElementFactory();
        $user     = mock('User');
        $view     = new Tracker_CrossSearch_SearchContentView($report,
                                                              $criteria,
                                                              $tree_of_artifacts,
                                                              $artifact_factory,
                                                              $factory,
                                                              $user);
        return $view->fetch();
    }
    
    private function buildTreeWithArtifact($artifact) {
        $artifact_node = new TreeNode();
        $artifact_node->setId(1);
        $artifact_node->setData(array('id' => $artifact->getId(), 'title' => 'foo', 'last_changeset_id' => '567'));
        
        $root = new TreeNode();
        $root->setId(0);
        $root->addChild($artifact_node);
        
        return $root;
    }
    
    private function buildCriteria($report) {
        $status_field = mock('Tracker_CrossSearch_SemanticStatusReportField');
        $criterion    = new Tracker_Report_Criteria(null, $report, $status_field, 0, true);
        return array($criterion);
    }
    
    private function buildAFormElementFactory() {
        $factory = mock('Tracker_FormElementFactory');
        $factory->expectNever('getFieldFromTrackerAndSharedField');
        return $factory;
    }
    
    private function buildAnArtifactFactoryThatReturns($artifact) {
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactById($artifact->getId())->returns($artifact);
        $artifact_factory->expectOnce('getArtifactById', array($artifact->getId()));
        return $artifact_factory;
    }
}

class Tracker_CrossSearch_SearchContentView_ArtifactLinkTest extends TuleapTestCase {
    private $view;
    
    public function setUp() {
        parent::setUp();
        $report            = new MockTracker_Report();
        
        $release_tracker_id = 743;
        $release_tracker    = aTracker()->withId($release_tracker_id)->build();
        $art_link_release_field_id   = 131;
        $art_link_release_field      = mock('Tracker_CrossSearch_ArtifactReportField');
        stub($art_link_release_field)->getTracker()->returns($release_tracker);
        stub($art_link_release_field)->getArtifactLinkFieldName()->returns('art_link_'.$art_link_release_field_id);
        $art_link_release_criterion  = new Tracker_Report_Criteria(null, $report, $art_link_release_field, 0, true);
        
        $sprint_tracker_id = 365;
        $sprint_tracker    = aTracker()->withId($sprint_tracker_id)->build();
        $art_link_sprint_field_id   = 511;
        $art_link_sprint_field      = mock('Tracker_CrossSearch_ArtifactReportField');
        stub($art_link_sprint_field)->getTracker()->returns($sprint_tracker);
        stub($art_link_sprint_field)->getArtifactLinkFieldName()->returns('art_link_'.$art_link_sprint_field_id);
        $art_link_sprint_criterion  = new Tracker_Report_Criteria(null, $report, $art_link_sprint_field, 0, true);
        
        $criteria = array($art_link_release_criterion, $art_link_sprint_criterion);
        
        $this->sprint_id = '354';
        $this->sprint    = stub('Tracker_Artifact')->getTitle()->returns('The planning known as Sprint');
        
        $this->release_id = '666';
        $this->release    = stub('Tracker_Artifact')->getTitle()->returns('I release I can fly');
        
        $artifact_node = new TreeNode();
        $artifact_node->setId(1);
        $artifact_node->setData(array('id' => 123,
                                      'title' => 'foo',
                                      'last_changeset_id' => '567',
                                      'art_link_'.$art_link_sprint_field_id => $this->sprint_id,
                                      'art_link_'.$art_link_release_field_id => $this->release_id));
        
        $tree_of_artifacts = new TreeNode();
        $tree_of_artifacts->setId(0);
        $tree_of_artifacts->addChild($artifact_node);
        
        $this->artifact_factory  = new MockTracker_ArtifactFactory();
        $factory           = new MockTracker_FormElementFactory();
        $tracker           = new MockTracker();
        $artifact          = new MockTracker_Artifact();
        $this->user              = mock('User');
        
        $artifact->setReturnValue('getTracker', $tracker);
        
        stub($this->artifact_factory)->getArtifactById(123)->returns($artifact);

        $this->view = new Tracker_CrossSearch_SearchContentView($report,
                                                                $criteria,
                                                                $tree_of_artifacts,
                                                                $this->artifact_factory,
                                                                $factory,
                                                                $this->user);
    }
    
    public function itUsesExtraColumnsFromArtifactRow() {
        stub($this->artifact_factory)->getArtifactByIdUserCanView($this->user, $this->sprint_id)->returns($this->sprint);
        stub($this->artifact_factory)->getArtifactByIdUserCanView($this->user, $this->release_id)->returns($this->release);
        
        $html = $this->view->fetch();
        
        $this->assertPattern('/The planning known as Sprint/', $html);
        $this->assertPattern('/I release I can fly/', $html);
    }
    
    public function itDisplayNothingWhenThereAreNoArtifactToDisplay() {
        stub($this->artifact_factory)->getArtifactByIdUserCanView($this->user, $this->sprint_id)->returns($this->sprint);
        stub($this->artifact_factory)->getArtifactByIdUserCanView($this->user, $this->release_id)->returns(null);
        
        $html = $this->view->fetch();
        
        $this->assertPattern('/The planning known as Sprint/', $html);
        $this->assertNoPattern('/I release I can fly/', $html);
    }
}
?>
