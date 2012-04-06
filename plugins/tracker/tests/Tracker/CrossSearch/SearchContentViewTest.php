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
Mock::generate('Tracker_Report_Criteria');
Mock::generate('Tracker');

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
}
?>
