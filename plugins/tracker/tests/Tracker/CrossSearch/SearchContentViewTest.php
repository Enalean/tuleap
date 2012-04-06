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

require_once dirname(__FILE__).'/../../../include/Tracker/TrackerManager.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Tracker.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Report/Tracker_Report.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Report/Tracker_Report_Criteria.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Artifact/Tracker_Artifact.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElementFactory.class.php';

Mock::generate('Tracker_Report');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_FormElement_Field_ArtifactLink');
Mock::generate('Tracker_Report_Criteria');
Mock::generate('Tracker');
Mock::generate('Tracker_CrossSearch_ArtifactReportField');
Mock::generate('Tracker_CrossSearch_SemanticStatusReportField');

class Tracker_CrossSearch_SearchContentViewTest extends TuleapTestCase {
    
    public function itDoesNotTryToRetrieveSharedFieldOriginForSemanticStatus() {
        $artifact          = $this->givenThereIsAnArtifact();
        $tree_of_artifacts = $this->buildTreeWithArtifact($artifact);
        $artifact_factory  = $this->buildAnArtifactFactoryThatReturns($artifact);
        
        $this->thenItFetchsTheSearchContentView($tree_of_artifacts, $artifact_factory);
    }
    
    private function givenThereIsAnArtifact() {
        return anArtifact()->withId(123)->withTracker(mock('Tracker'))->build();
    }
    
    private function thenItFetchsTheSearchContentView($tree_of_artifacts, $artifact_factory) {
        $report   = mock('Tracker_Report');
        $criteria = $this->buildCriteria($report);
        $factory  = $this->buildAFormElementFactory();
        $view     = new Tracker_CrossSearch_SearchContentView($report,
                                                              $criteria,
                                                              $tree_of_artifacts,
                                                              $artifact_factory,
                                                              $factory);
        $html = $view->fetch();
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
    
    public function itUsesExtraColumnsFromArtifactRow() {
        $report            = new MockTracker_Report();
        
        $release_tracker_id = 743;
        $release_tracker    = aTracker()->withId($release_tracker_id)->build();
        $art_link_release_field_id   = 131;
        $art_link_release_field      = new MockTracker_CrossSearch_ArtifactReportField();
        $art_link_release_field->setReturnValue('getTracker', $release_tracker);
        $art_link_release_field->setReturnValue('getArtifactLinkFieldName', 'art_link_'.$art_link_release_field_id);
        $art_link_release_criterion  = new Tracker_Report_Criteria(null, $report, $art_link_release_field, 0, true);
        
        $sprint_tracker_id = 365;
        $sprint_tracker    = aTracker()->withId($sprint_tracker_id)->build();
        $art_link_sprint_field_id   = 511;
        $art_link_sprint_field      = new MockTracker_CrossSearch_ArtifactReportField();
        $art_link_sprint_field->setReturnValue('getTracker', $sprint_tracker);
        $art_link_sprint_field->setReturnValue('getArtifactLinkFieldName', 'art_link_'.$art_link_sprint_field_id);
        $art_link_sprint_criterion  = new Tracker_Report_Criteria(null, $report, $art_link_sprint_field, 0, true);
        
        $criteria          = array($art_link_release_criterion, $art_link_sprint_criterion);
        
        $sprint_id = '354';
        $sprint = mock('Tracker_Artifact');
        stub($sprint)->getTitle()->returns('The planning known as Sprint');
        
        $release_id = '666';
        $release = mock('Tracker_Artifact');
        stub($release)->getTitle()->returns('I release I can fly');
        
        $artifact_node = new TreeNode();
        $artifact_node->setId(1);
        $artifact_node->setData(array('id' => 123,
                                      'title' => 'foo',
                                      'last_changeset_id' => '567',
                                      'art_link_'.$art_link_sprint_field_id => $sprint_id,
                                      'art_link_'.$art_link_release_field_id => $release_id));
        
        $tree_of_artifacts = new TreeNode();
        $tree_of_artifacts->setId(0);
        $tree_of_artifacts->addChild($artifact_node);
        
        $artifact_factory  = new MockTracker_ArtifactFactory();
        $factory           = new MockTracker_FormElementFactory();
        $tracker           = new MockTracker();
        $artifact          = new MockTracker_Artifact();
        
        $artifact->setReturnValue('getTracker', $tracker);
        
        $artifact_factory->setReturnValue('getArtifactById', $artifact, array(123));
        $artifact_factory->setReturnValue('getArtifactById', $sprint, array($sprint_id));
        stub($artifact_factory)->getArtifactById($release_id)->returns($release);
        

        $view = new Tracker_CrossSearch_SearchContentView($report,
                                                          $criteria,
                                                          $tree_of_artifacts,
                                                          $artifact_factory,
                                                          $factory);
        $html = $view->fetch();
        
        $this->assertPattern('/The planning known as Sprint/', $html);
        $this->assertPattern('/I release I can fly/', $html);
    }
}
?>
