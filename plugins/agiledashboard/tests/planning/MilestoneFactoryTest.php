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
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../builders/aMilestone.php';

class Planning_MilestoneFactoryTest extends TuleapTestCase {
    private $project;
    
    public function setUp() {
        $this->project    = mock('Project');
        $this->planning_id = 34;
        $this->artifact_id = 56;

        $this->user              = mock('User');
        $this->planning          = aPlanning()->withId($this->planning_id)->build();
        $this->artifact          = mock('Tracker_Artifact');
        $this->planning_factory  = mock('PlanningFactory');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->milestone_factory = new Planning_MilestoneFactory($this->planning_factory, $this->artifact_factory);
        
        stub($this->artifact)->getUniqueLinkedArtifacts($this->user)->returns(array());
        stub($this->artifact)->getHierarchyLinkedArtifacts($this->user)->returns(array());
        stub($this->planning_factory)->getPlanningWithTrackers($this->planning_id)->returns($this->planning);
    }
    
    public function itCanRetrieveMilestoneWithItsPlanningItsArtifactItsPlannedItemsAndItsSubMilestones() {
        $milestone_factory = TestHelper::getPartialMock('Planning_MilestoneFactory', array('getMilestoneWithPlannedArtifacts',
                                                                                           'getSubMilestones'));
        $milestone_factory->__construct($this->planning_factory, $this->artifact_factory);
        
        $milestone_with_planned_artifacts = aMilestone()->build();
        stub($milestone_factory)->getMilestoneWithPlannedArtifacts($this->user,
                                                                   $this->project,
                                                                   $this->planning_id,
                                                                   $this->artifact_id)
                                ->returns($milestone_with_planned_artifacts);
        
        $sub_milestones = array(aMilestone()->build(),
                                aMilestone()->build());
        stub($milestone_factory)->getSubMilestones($this->user, $milestone_with_planned_artifacts)
                                ->returns($sub_milestones);
        
        $milestone = $milestone_factory->getMilestoneWithPlannedArtifactsAndSubMilestones($this->user,
                                                                                          $this->project,
                                                                                          $this->planning_id,
                                                                                          $this->artifact_id);
        $this->assertIsA($milestone, 'Planning_Milestone');
        $this->assertEqual($milestone->getPlannedArtifacts(), $milestone_with_planned_artifacts->getPlannedArtifacts());
        $this->assertEqual($milestone->getSubMilestones(), $sub_milestones);
    }
    
    public function itCanRetrieveSubMilestonesOfAGivenMilestone() {
        $sprints_tracker   = mock('Tracker');
        $hackfests_tracker = mock('Tracker');
        
        $sprint_planning   = mock('Planning');
        $hackfest_planning = mock('Planning');
        
        $release_1_0   = mock('Tracker_Artifact');
        $sprint_1      = aMockArtifact()->withTracker($sprints_tracker)->build();
        $sprint_2      = aMockArtifact()->withTracker($sprints_tracker)->build();
        $hackfest_2012 = aMockArtifact()->withTracker($hackfests_tracker)->build();
        
        stub($release_1_0)->getHierarchyLinkedArtifacts($this->user)
                          ->returns(array($sprint_1, $sprint_2, $hackfest_2012));
        
        stub($this->planning_factory)->getPlanningByPlanningTracker($sprints_tracker)->returns($sprint_planning);
        stub($this->planning_factory)->getPlanningByPlanningTracker($hackfests_tracker)->returns($hackfest_planning);
        
        $milestone      = aMilestone()->withArtifact($release_1_0)->build();
        $sub_milestones = $this->milestone_factory->getSubMilestones($this->user, $milestone);
        
        $this->assertEqual(count($sub_milestones), 3);
        $this->assertIsA($sub_milestones[0], 'Planning_Milestone');
        $this->assertIsA($sub_milestones[1], 'Planning_Milestone');
        $this->assertIsA($sub_milestones[2], 'Planning_Milestone');
        $this->assertEqual($sub_milestones[0]->getArtifact(), $sprint_1);
        $this->assertEqual($sub_milestones[1]->getArtifact(), $sprint_2);
        $this->assertEqual($sub_milestones[2]->getArtifact(), $hackfest_2012);
    }

    public function itCanRetrievesAMilestoneWithItsPlanningItsArtifactAndItsPlannedItems() {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($this->artifact);

        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $this->artifact_id);

        $this->assertEqual($milestone->getArtifact(), $this->artifact);

        // TODO: merge tree-related presenter tests in factory tests
    }

    public function itReturnsNoMilestoneWhenThereIsNoArtifact() {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns(null);

        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $this->artifact_id);

        $this->assertIsA($milestone, 'Planning_NoMilestone');
    }

    public function itCanSetMilestonesWithaHierarchyDepthGreaterThan2() {
        $artifact_id   = 100;

        $depth3_artifact = $this->anArtifactWithId(3);
        $depth2_artifact = $this->anArtifactWithIdAndUniqueLinkedArtifacts(2, array($depth3_artifact));
        $depth1_artifact = $this->anArtifactWithIdAndUniqueLinkedArtifacts(1, array($depth2_artifact));
        $root_artifact   = $this->anArtifactWithIdAndUniqueLinkedArtifacts($artifact_id, array($depth1_artifact));
        stub($this->artifact_factory)->getArtifactById($artifact_id)->returns($root_artifact);

        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $artifact_id);
        $tree_node = $milestone->getPlannedArtifacts();
        $this->assertTrue($tree_node->hasChildren());
        $tree_node1 = $tree_node->getChild(0);
        $this->assertTrue($tree_node1->hasChildren());
        $tree_node2 = $tree_node1->getChild(0);
        $this->assertTrue($tree_node2->hasChildren());
        $tree_node3 = $tree_node2->getChild(0);
        $this->assertEqual(3, $tree_node3->getId());
    }
    
    public function itAddsTheArtifactsToTheRootNode() {
        $root_aid   = 100;
        $root_artifact = stub('Tracker_Artifact')->getId()->returns($root_aid);
        stub($this->artifact_factory)->getArtifactById($root_aid)->returns($root_artifact);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array());
        stub($root_artifact)->getHierarchyLinkedArtifacts()->returns(array());
        
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $root_aid);
        
        $root_node = $milestone->getPlannedArtifacts();
        $root_note_data = $root_node->getData();
        $this->assertEqual($root_artifact, $root_note_data['artifact']);
    }
    
    public function itAddsTheArtifactsToTheChildNodes() {
        $root_aid   = 100;
        $root_artifact = stub('Tracker_Artifact')->getId()->returns($root_aid);
        stub($this->artifact_factory)->getArtifactById($root_aid)->returns($root_artifact);
        $depth1_artifact = $this->anArtifactWithId(9999);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array($depth1_artifact));
        stub($root_artifact)->getHierarchyLinkedArtifacts()->returns(array());
        
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $root_aid);
        
        $child_node_data = $milestone->getPlannedArtifacts()->getChild(0)->getData();
        $this->assertEqual($depth1_artifact, $child_node_data['artifact']);
    }

    public function anArtifactWithId($id) {
        $artifact = aMockArtifact()->withId($id)->build();
        stub($artifact)->getHierarchyLinkedArtifacts()->returns(array());
        return $artifact;
    }

    public function anArtifactWithIdAndUniqueLinkedArtifacts($id, $linked_artifacts) {
        $artifact = aMockArtifact()->withId($id)
                              ->withUniqueLinkedArtifacts($linked_artifacts)
                              ->build();
        stub($artifact)->getHierarchyLinkedArtifacts()->returns(array());
        return $artifact;
        
    }
}

class MileStoneFactory_getOpenMilestonesTest extends TuleapTestCase {
    private $project;
  
    public function itReturnsAnEmptyArrayWhenAllItemsAreClosed() {
        $artifact_factory = stub('Tracker_ArtifactFactory')->getOpenArtifactsByTrackerIdUserCanView()->returns(array());
        $planning_factory = mock('PlanningFactory');
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $this->assertIdentical(array(), $factory->getOpenMilestones($this->user, $this->project, $this->planning));
    }
    
    public function itReturnsAsManyMileStonesAsThereAreArtifacts() {
        $artifacts        = array(anArtifact()->build(),
                                  anArtifact()->build());
        $artifact_factory = stub('Tracker_ArtifactFactory')->getOpenArtifactsByTrackerIdUserCanView()->returns($artifacts);
        $planning_factory = mock('PlanningFactory');
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $this->assertEqual(2, count($factory->getOpenMilestones($this->user, $this->project, $this->planning)));
    }
    
    public function itReturnsMileStones() {
        $artifact         = anArtifact()->build();
        $artifact_factory = stub('Tracker_ArtifactFactory')->getOpenArtifactsByTrackerIdUserCanView()->returns(array($artifact));
        $planning_factory = mock('PlanningFactory');
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $mile_stone       = new Planning_Milestone($this->project, $this->planning, $artifact);
        $this->assertIdentical(array($mile_stone), $factory->getOpenMilestones($this->user, $this->project, $this->planning));
    }
    
    public function itReturnsMileStonesWithPlannedArtifacts() {
        $artifact         = anArtifact()->build();
        $tracker_id       = 7777777;
        $planning         = aPlanning()->withPlanningTrackerId($tracker_id)->build();
        $artifact_factory = stub('Tracker_ArtifactFactory')->getOpenArtifactsByTrackerIdUserCanView($this->user, $tracker_id)->returns(array($artifact));
        $planning_factory = mock('PlanningFactory');
        
        $planned_artifacts= new TreeNode('sdfkjasf');   
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        stub($factory)->getPlannedArtifacts()->returns($planned_artifacts);
        
        $mile_stone       = new Planning_Milestone($this->project, $planning, $artifact, $planned_artifacts);
        $milestones       = $factory->getOpenMilestones($this->user, $this->project, $planning);
        $this->assertEqual($mile_stone, $milestones[0]);
    }
    
    public function setUp() {
        parent::setUp();
        $this->user             = mock('User');
        $this->project          = stub('Project')->getID()->returns(99);
        $this->planning_id      = 3333;
        $this->planning         = mock('Planning');
    }

    public function newMileStoneFactory($planning_factory, $artifact_factory) {
        $factory          = TestHelper::getPartialMock('Planning_MilestoneFactory', array('getPlannedArtifacts'));
        $factory->__construct($planning_factory, $artifact_factory);
        return $factory;
    }
}
?>
