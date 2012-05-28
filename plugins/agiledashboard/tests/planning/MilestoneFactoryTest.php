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

require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../../include/Planning/MilestoneFactory.class.php';

class MilestoneFactoryTest extends TuleapTestCase {
    public function setUp() {
        $this->group_id    = 12;
        $this->planning_id = 34;
        $this->artifact_id = 56;

        $this->user              = mock('User');
        $planning                = aPlanning()->withId($this->planning_id)->build();
        $planning_factory        = mock('PlanningFactory');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->artifact          = stub('Tracker_Artifact')->getUniqueLinkedArtifacts($this->user)->returns(array());
        $this->milestone_factory = new Planning_MilestoneFactory($planning_factory, $this->artifact_factory);

        stub($planning_factory)->getPlanningWithTrackers($this->planning_id)->returns($planning);
    }

    public function itCanRetrievesAMilestoneWithItsPlanningItsArtifactAndItsPlannedItems() {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($this->artifact);

        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->group_id, $this->planning_id, $this->artifact_id);

        $this->assertEqual($milestone->getArtifact(), $this->artifact);

        // TODO: merge tree-related presenter tests in factory tests
    }

    public function itReturnsNoMilestoneWhenThereIsNoArtifact() {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns(null);

        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->group_id, $this->planning_id, $this->artifact_id);

        $this->assertIsA($milestone, 'Planning_NoMilestone');
    }

    public function itCanSetMilestonesWithaHierarchyDepthGreaterThan2() {
        $artifact_id   = 100;
        $root_artifact = stub('Tracker_Artifact')->getId()->returns($artifact_id);
        stub($this->artifact_factory)->getArtifactById($artifact_id)->returns($root_artifact);

        $depth3_artifact = stub('Tracker_Artifact')->getUniqueLinkedArtifacts()->returns(array());
        stub($depth3_artifact)->getId()->returns(3);
        $depth2_artifact = stub('Tracker_Artifact')->getUniqueLinkedArtifacts()->returns(array($depth3_artifact));
        stub($depth2_artifact)->getId()->returns(2);
        $depth1_artifact = stub('Tracker_Artifact')->getUniqueLinkedArtifacts()->returns(array($depth2_artifact));
        stub($depth1_artifact)->getId()->returns(1);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array($depth1_artifact));

        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->group_id, $this->planning_id, $artifact_id);
        $tree_node = $milestone->getPlannedArtifacts();
        $this->assertTrue($tree_node->hasChildren());
        $tree_node1 = $tree_node->getChild(0);
        $this->assertTrue($tree_node1->hasChildren());
        $tree_node2 = $tree_node1->getChild(0);
        $this->assertTrue($tree_node2->hasChildren());
        $tree_node3 = $tree_node2->getChild(0);
        $this->assertEqual(3, $tree_node3->getId());
    }
}
?>
