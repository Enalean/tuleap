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

require_once dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Sorter.class.php';
require_once dirname(__FILE__).'/../../builders/aCrossSearchCriteria.php';
require_once dirname(__FILE__) .'/../../../include/Tracker/CrossSearch/Query.class.php';

class Tracker_Hierarchy_SorterTest extends TuleapTestCase {
    
    public function itAddsTheArtifactToTheTreeNode() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $trackerIds = array(111, 112, 113, 666);
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns(mock('Tracker_Artifact'));
        $sorter = new Tracker_Hierarchy_Sorter($artifact_factory);
        $artifacts_dar = $this->getResultsForTrackerOutsideHierarchy();

        $artifacts = $sorter->buildTreeWithCompleteList($artifacts_dar, $trackerIds, $tracker_hierarchy);
        $all_artifact_nodes = $artifacts->flattenChildren();
        
        $this->assertArrayNotEmpty($all_artifact_nodes);
        foreach ($all_artifact_nodes as $artifact_node) {
            $this->assertIsA($artifact_node->getObject(), 'Tracker_Artifact');
        }
    }
    public function it2AddsTheArtifactToTheTreeNode() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $trackerIds = array(111, 112, 113, 666);
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns(mock('Tracker_Artifact'));
        $sorter = new Tracker_Hierarchy_Sorter($artifact_factory);
        $artifacts_dar = $this->getResultsForTrackerOutsideHierarchy();

        $user = mock('User');
        $artifacts = $sorter->buildTreeWithMissingChildren($user, $artifacts_dar);
        $all_artifact_nodes = $artifacts->flattenChildren();
        
        $this->assertArrayNotEmpty($all_artifact_nodes);
        foreach ($all_artifact_nodes as $artifact_node) {
            $this->assertIsA($artifact_node->getObject(), 'Tracker_Artifact');
        }
    }
    
    function itReturnsArtifactFromTrackersOutsidesHierarchy() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $trackerIds = array(111, 112, 113, 666);
        $sorter = new Tracker_Hierarchy_Sorter(mock('Tracker_ArtifactFactory'));
        $artifacts_dar = $this->getResultsForTrackerOutsideHierarchy();

        $artifacts = $sorter->buildTreeWithCompleteList($artifacts_dar, $trackerIds, $tracker_hierarchy);
        
        $expected  = $this->getExpectedForTrackerOutsideHierarchy();
        $this->assertEqual($artifacts->__toString(), $expected->__toString());
    }
    
    private function getResultsForTrackerOutsideHierarchy() {
        return TestHelper::arrayToDar(
            array('id' => 66, 'tracker_id' => 666, 'artifactlinks' => '',),
            array('id' => 8, 'tracker_id' => 111, 'artifactlinks' => '11,9,34',),
            array('id' => 11, 'tracker_id' => 113, 'artifactlinks' => '',),
            array('id' => 7, 'tracker_id' => 112, 'artifactlinks' => '5',),
            array('id' => 6, 'tracker_id' => 112, 'artifactlinks' => '8',),
            array('id' => 5, 'tracker_id' => 111, 'artifactlinks' => '',),
            array('id' => 9, 'tracker_id' => 113, 'artifactlinks' => '',),
            array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '66',)
        );
    }
    
    private function getExpectedForTrackerOutsideHierarchy() {
        $root    = new TreeNode(null, 0);
        $node_7  = new TreeNode(array('id' => 7,  'tracker_id' => 112, 'artifactlinks' => '5'), 7);
        $node_5  = new TreeNode(array('id' => 5,  'tracker_id' => 111, 'artifactlinks' => ''), 5);
        $node_6  = new TreeNode(array('id' => 6,  'tracker_id' => 112, 'artifactlinks' => '8'), 6);
        $node_8  = new TreeNode(array('id' => 8,  'tracker_id' => 111, 'artifactlinks' => '11,9,34'), 8);
        $node_11 = new TreeNode(array('id' => 11, 'tracker_id' => 113, 'artifactlinks' => ''), 11);
        $node_9  = new TreeNode(array('id' => 9,  'tracker_id' => 93,  'artifactlinks' => ''), 9);
        $node_10 = new TreeNode(array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '66'), 10);
        $node_66 = new TreeNode(array('id' => 66, 'tracker_id' => 666, 'artifactlinks' => ''), 66);

        $root->addChildren(
            $node_7->addChildren(
                $node_5
            ),
            $node_6->addChildren(
                $node_8->addChildren(
                    $node_11,
                    $node_9
                )
            ),
            $node_10,
            $node_66
        );
        return $root;
    }
    
    private function GivenATrackerHierarchy() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(111, 113);
        $hierarchy->addRelationship(201, 202);
        return $hierarchy;
    }


}
?>
