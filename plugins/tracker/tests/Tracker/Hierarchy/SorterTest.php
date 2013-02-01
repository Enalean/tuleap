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

class Tracker_Hierarchy_Sorter_BuildTreeWithCompleteListTest extends TuleapTestCase {
    
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
    
    function itReturnsArtifactFromTrackersOutsidesHierarchy() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $trackerIds = array(111, 112, 113, 666);
        $artifact_factory = new MockedArtifactFactory();
        $sorter = new Tracker_Hierarchy_Sorter($artifact_factory);
        $artifacts_dar = $this->getResultsForTrackerOutsideHierarchy();

        $artifacts = $sorter->buildTreeWithCompleteList($artifacts_dar, $trackerIds, $tracker_hierarchy);
        
        $expected  = $this->getExpectedForTrackerOutsideHierarchy($artifact_factory);
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
    
    private function getExpectedForTrackerOutsideHierarchy($artifact_factory) {
        $root    = new ArtifactNode($artifact_factory->getArtifactById(0));
        $node_7  = new ArtifactNode($artifact_factory->getArtifactById(7));
        $node_5  = new ArtifactNode($artifact_factory->getArtifactById(5));
        $node_6  = new ArtifactNode($artifact_factory->getArtifactById(6));
        $node_8  = new ArtifactNode($artifact_factory->getArtifactById(8));
        $node_11 = new ArtifactNode($artifact_factory->getArtifactById(11));
        $node_9  = new ArtifactNode($artifact_factory->getArtifactById(9));
        $node_10 = new ArtifactNode($artifact_factory->getArtifactById(10));
        $node_66 = new ArtifactNode($artifact_factory->getArtifactById(66));

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
class MockedArtifactFactory extends Tracker_ArtifactFactory {


    public function __construct() {
        // super constructor is protected so we need this public constructor
    }
    
    public function getArtifactById($id) {
        return stub('Tracker_Artifact')->getId()->returns($id);
    }
}

class Tracker_Hierarchy_Sorter_BuildTreeWithMissingChildrenTest extends TuleapTestCase {
    // TODO : test this function
        public function itAddsTheArtifactToTheTreeNode() {
//            $tracker_hierarchy = $this->GivenATrackerHierarchy();
//            $trackerIds = array(111, 112, 113, 666);
//            $artifact = stub('Tracker_Artifact')->getLastChangeSet()->returns(mock('Tracker_Artifact_Changeset'));
//            stub($artifact)->getHierarchyLinkedArtifacts()->returns($artifact);
//            $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns($artifact);
//            $sorter = new Tracker_Hierarchy_Sorter($artifact_factory);
//            $artifacts_dar = $this->getResultsForTrackerOutsideHierarchy();
//
//            $user = mock('PFUser');
//            $artifacts = $sorter->buildTreeWithMissingChildren($user, $artifacts_dar);
//            $all_artifact_nodes = $artifacts->flattenChildren();
//
//            $this->assertArrayNotEmpty($all_artifact_nodes);
//            foreach ($all_artifact_nodes as $artifact_node) {
//                $this->assertIsA($artifact_node->getObject(), 'Tracker_Artifact');
//            }
    }

}
?>
