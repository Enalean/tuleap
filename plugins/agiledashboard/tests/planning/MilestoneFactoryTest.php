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
require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../../include/Planning/MilestoneFactory.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';
require_once dirname(__FILE__).'/../builders/aMilestone.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/aMockTracker.php';

abstract class Planning_MilestoneBaseTest extends TuleapTestCase {

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

abstract class Planning_MilestoneFactory_GetMilestoneBaseTest extends Planning_MilestoneBaseTest {
    protected $project;
    protected $planning_factory;
    protected $artifact_factory;
    protected $formelement_factory;
    protected $milestone_tracker_id;
    protected $milestone_tracker;
    protected $user;
    
    public function setUp() {
        parent::setUp();
        
        $this->project    = mock('Project');
        $this->planning_id = 34;
        $this->artifact_id = 56;
        
        $this->milestone_tracker_id = 112;
        $this->milestone_tracker    = stub('Tracker')->getId()->returns($this->milestone_tracker_id);

        $this->user              = mock('User');
        $this->planning          = aPlanning()->withId($this->planning_id)->build();
        $this->artifact          = mock('Tracker_Artifact');
        $this->planning_factory  = mock('PlanningFactory');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->milestone_factory = new Planning_MilestoneFactory($this->planning_factory, $this->artifact_factory, $this->formelement_factory);
        
        stub($this->artifact)->getUniqueLinkedArtifacts($this->user)->returns(array());
        stub($this->artifact)->getHierarchyLinkedArtifacts($this->user)->returns(array());
        stub($this->artifact)->getTracker()->returns($this->milestone_tracker);
        stub($this->planning_factory)->getPlanningWithTrackers($this->planning_id)->returns($this->planning);
    }
}

class Planning_MilestoneFactory_getMilestoneTest extends Planning_MilestoneFactory_GetMilestoneBaseTest {

    public function itCanRetrieveMilestoneWithItsPlanningItsArtifactItsPlannedItemsAndItsSubMilestones() {
        $milestone_factory = partial_mock(
            'Planning_MilestoneFactory',
             array('getMilestoneWithPlannedArtifacts', 'getSubMilestones', 'getMilestoneAncestors'),
             array($this->planning_factory, $this->artifact_factory, $this->formelement_factory)
        );
        
        $milestone_with_planned_artifacts = aMilestone()->build();
        stub($milestone_factory)->getMilestoneWithPlannedArtifacts($this->user,
                                                                   $this->project,
                                                                   $this->planning_id,
                                                                   $this->artifact_id)
                                ->returns($milestone_with_planned_artifacts);

        $sub_milestones = array(aMilestone()->build(),
                                aMilestone()->build());
        stub($milestone_factory)
            ->getSubMilestones($this->user, $milestone_with_planned_artifacts)
            ->returns($sub_milestones);

        $parent_milestones = array(aMilestone()->build());
        stub($milestone_factory)
            ->getMilestoneAncestors($this->user, $milestone_with_planned_artifacts)
            ->returns($parent_milestones);

        $milestone = $milestone_factory->getMilestoneWithPlannedArtifactsAndSubMilestones($this->user,
                                                                                          $this->project,
                                                                                          $this->planning_id,
                                                                                          $this->artifact_id);
        $this->assertIsA($milestone, 'Planning_ArtifactMilestone');
        $this->assertEqual($milestone->getPlannedArtifacts(), $milestone_with_planned_artifacts->getPlannedArtifacts());
        $this->assertEqual($milestone->getSubMilestones(), $sub_milestones);
        $this->assertTrue($milestone->hasAncestors());
        $this->assertEqual($milestone->getAncestors(), $parent_milestones);
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
        $this->assertIsA($sub_milestones[0], 'Planning_ArtifactMilestone');
        $this->assertIsA($sub_milestones[1], 'Planning_ArtifactMilestone');
        $this->assertIsA($sub_milestones[2], 'Planning_ArtifactMilestone');
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
        $root_artifact = mock('Tracker_Artifact');
        stub($this->artifact_factory)->getArtifactById($root_aid)->returns($root_artifact);
        stub($root_artifact)->getId()->returns($root_aid);
        stub($root_artifact)->getTracker()->returns($this->milestone_tracker);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array());
        stub($root_artifact)->getHierarchyLinkedArtifacts()->returns(array());
        
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $root_aid);
        
        $root_node = $milestone->getPlannedArtifacts();
        $root_note_data = $root_node->getObject();
        $this->assertEqual($root_aid, $root_node->getId());
        $this->assertIdentical($root_artifact, $root_note_data);
    }
    
    public function itAddsTheArtifactsToTheChildNodes() {
        $root_aid   = 100;
        $root_artifact = mock('Tracker_Artifact');
        stub($this->artifact_factory)->getArtifactById($root_aid)->returns($root_artifact);
        stub($root_artifact)->getId()->returns($root_aid);
        stub($root_artifact)->getTracker()->returns($this->milestone_tracker);
        $depth1_artifact = $this->anArtifactWithId(9999);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array($depth1_artifact));
        stub($root_artifact)->getHierarchyLinkedArtifacts()->returns(array());
        
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $root_aid);
        
        $child_node      = $milestone->getPlannedArtifacts()->getChild(0);
        $child_node_data = $child_node->getObject();
        $this->assertEqual(9999, $child_node->getId());
        $this->assertIdentical($depth1_artifact, $child_node_data);
    }

}

class MilestoneFactory_MilestoneComesWithRemainingEffortTest extends Planning_MilestoneFactory_GetMilestoneBaseTest {

    public function testRemainingEffortIsNullWhenThereIsNoRemainingEffortField() {
        $this->assertEqual($this->getMilestoneRemainingEffort(), null);
    }

    public function itRetrievesMilestoneWithRemainingEffortWithComputedValue() {
        $remaining_effort = 225;

        $remaining_effort_field = stub('Tracker_FormElement_Field_Computed')->getComputedValue($this->user, $this->artifact)->returns($remaining_effort);
        stub($this->formelement_factory)->getComputableFieldByNameForUser($this->milestone_tracker_id, Planning_Milestone::REMAINING_EFFORT_FIELD_NAME, $this->user)->returns($remaining_effort_field);

        $this->assertEqual($this->getMilestoneRemainingEffort(), $remaining_effort);
    }

    private function getMilestoneRemainingEffort() {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($this->artifact);
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $this->artifact_id);
        return $milestone->getRemainingEffort();
    }
}

class MilestoneFactory_MilestoneComesWithCapacityTest extends Planning_MilestoneFactory_GetMilestoneBaseTest {

    public function testCapacityIsNullWhenThereIsNoCapacityField() {
        $this->assertEqual($this->getMilestoneCapacity(), null);
    }

    public function itRetrievesMilestoneWithCapacityWithActualValue() {
        $capacity = 225;

        $capacity_field = stub('Tracker_FormElement_Field_Float')->getComputedValue($this->user, $this->artifact)->returns($capacity);
        stub($this->formelement_factory)->getComputableFieldByNameForUser($this->milestone_tracker_id, Planning_Milestone::CAPACITY_FIELD_NAME, $this->user)->returns($capacity_field);

        $this->assertEqual($this->getMilestoneCapacity(), $capacity);
    }

    private function getMilestoneCapacity() {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($this->artifact);
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifacts($this->user, $this->project, $this->planning_id, $this->artifact_id);
        return $milestone->getCapacity();
    }
}

class MilestoneFactory_GetAllMilestonesTest extends TuleapTestCase {
    private $project;
      
    public function setUp() {
        parent::setUp();
        $this->user             = mock('User');
        $this->project          = stub('Project')->getID()->returns(99);
        $this->planning_tracker = stub('Tracker')->getProject()->returns($this->project);
        $this->planning_id      = 3333;
        $this->planning         = stub('Planning')->getPlanningTracker()->returns($this->planning_tracker);
    }

    public function itReturnsAnEmptyArrayWhenAllItemsAreClosed() {
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactsByTrackerIdUserCanView()->returns(array());
        $planning_factory = mock('PlanningFactory');
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $this->assertIdentical(array(), $factory->getAllMilestones($this->user, $this->planning));
    }
    
    public function itReturnsAsManyMilestonesAsThereAreArtifacts() {
        $artifacts        = array(anArtifact()->build(),
                                  anArtifact()->build());
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactsByTrackerIdUserCanView()->returns($artifacts);
        $planning_factory = mock('PlanningFactory');
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $this->assertEqual(2, count($factory->getAllMilestones($this->user, $this->planning)));
    }
    
    public function itReturnsMilestones() {
        $artifact         = anArtifact()->build();
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactsByTrackerIdUserCanView()->returns(array($artifact));
        $planning_factory = mock('PlanningFactory');
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $mile_stone       = new Planning_ArtifactMilestone($this->project, $this->planning, $artifact);
        $this->assertIdentical(array($mile_stone), $factory->getAllMilestones($this->user, $this->planning));
    }
    
    public function itReturnsMilestonesWithPlannedArtifacts() {
        $artifact         = anArtifact()->build();
        $tracker_id       = 7777777;
        stub($this->planning_tracker)->getId()->returns($tracker_id);
        $planning         = aPlanning()->withPlanningTracker($this->planning_tracker)->build();
        $artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactsByTrackerIdUserCanView($this->user, $tracker_id)->returns(array($artifact));
        $planning_factory = mock('PlanningFactory');
        
        $planned_artifacts= new TreeNode('sdfkjasf');   
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        stub($factory)->getPlannedArtifacts()->returns($planned_artifacts);
        
        $mile_stone       = new Planning_ArtifactMilestone($this->project, $planning, $artifact, $planned_artifacts);
        $milestones       = $factory->getAllMilestones($this->user, $planning);
        $this->assertEqual($mile_stone, $milestones[0]);
    }

    public function newMileStoneFactory($planning_factory, $artifact_factory) {
        $factory = TestHelper::getPartialMock('Planning_MilestoneFactory', array('getPlannedArtifacts'));
        $factory->__construct($planning_factory, $artifact_factory, mock('Tracker_FormElementFactory'));
        return $factory;
    }
}

class MilestoneFactory_PlannedArtifactsTest extends Planning_MilestoneBaseTest {
    
    public function itReturnsATreeOfPlanningItems() {
        $depth3_artifact  = $this->anArtifactWithId(3);
        $depth2_artifact  = $this->anArtifactWithIdAndUniqueLinkedArtifacts(2, array($depth3_artifact));
        $depth1_artifact  = $this->anArtifactWithIdAndUniqueLinkedArtifacts(1, array($depth2_artifact));
        $root_artifact    = $this->anArtifactWithIdAndUniqueLinkedArtifacts(100, array($depth1_artifact));

        $factory = new Planning_MileStoneFactory(mock('PlanningFactory'), mock('Tracker_ArtifactFactory'), mock('Tracker_FormElementFactory'));
        $planning_items_tree = $factory->getPlannedArtifacts(mock('User'), $root_artifact);

        $children = $planning_items_tree->flattenChildren();

        $this->assertFalse(empty($children));
        foreach($children as $tree_node) {
            $this->assertIsA($tree_node->getObject(), 'Tracker_Artifact');
        }
    }
}

class MilestoneFactory_GetMilestoneFromArtifactTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->project          = mock('Project');
        $this->release_planning = mock('Planning');
        $this->release_tracker  = aTracker()->withId(2)->withProject($this->project)->build();
        $this->release_artifact = aMockArtifact()->withTracker($this->release_tracker)->build();

        $planning_factory        = stub('PlanningFactory')->getPlanningByPlanningTracker($this->release_tracker)->returns($this->release_planning);
        $this->milestone_factory = new Planning_MilestoneFactory($planning_factory, mock('Tracker_ArtifactFactory'), mock('Tracker_FormElementFactory'));
    }

    public function itCreateMilestoneFromArtifact() {
        $release_milestone = $this->milestone_factory->getMilestoneFromArtifact($this->release_artifact);
        $this->assertEqualToReleaseMilestone($release_milestone);
    }

    private function assertEqualToReleaseMilestone($actual_release_milestone) {
        $expected_release_milestone = new Planning_ArtifactMilestone($this->project, $this->release_planning, $this->release_artifact);
        $this->assertEqual($actual_release_milestone, $expected_release_milestone);
    }
}

class MilestoneFactory_GetMilestoneWithAncestorsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->current_user     = aUser()->build();
        $this->milestone_factory = partial_mock('Planning_MilestoneFactory', array('getMilestoneFromArtifact'));

        $this->sprint_artifact  = mock('Tracker_Artifact');
        $this->sprint_milestone = aMilestone()->withArtifact($this->sprint_artifact)->build();
    }

    public function itReturnsEmptyArrayIfThereIsNoArtifactInMilestone() {
        $empty_milestone = new Planning_NoMilestone(mock('Project'), mock('Planning'));

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $empty_milestone);
        $this->assertEqual($milestones, array());
    }

    public function itBuildTheMilestonesWhenNoParents() {
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array());

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array());
    }

    public function itBuildTheMilestoneForOneParent() {
        $release_artifact = aMockArtifact()->build();
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array($release_artifact));

        $release_milestone = mock('Planning_ArtifactMilestone');
        stub($this->milestone_factory)->getMilestoneFromArtifact($release_artifact)->returns($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array($release_milestone));
    }

    public function itBuildTheMilestoneForSeveralParents() {
        $release_artifact = aMockArtifact()->withId(1)->build();
        $product_artifact = aMockArtifact()->withId(2)->build();
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array($release_artifact, $product_artifact));

        $product_milestone = aMilestone()->withArtifact($product_artifact)->build();
        $release_milestone = aMilestone()->withArtifact($release_artifact)->build();
        stub($this->milestone_factory)->getMilestoneFromArtifact($product_artifact)->returns($product_milestone);
        stub($this->milestone_factory)->getMilestoneFromArtifact($release_artifact)->returns($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array($release_milestone, $product_milestone));
    }
}

class MilestoneFactory_GetSiblingsMilestonesTest extends TuleapTestCase {
    private $current_user;
    private $milestone_factory;
    private $sprint_1_artifact;
    private $sprint_1_milestone;
            
    public function setUp() {
        parent::setUp();
        $this->current_user      = aUser()->build();
        $this->milestone_factory = partial_mock('Planning_MilestoneFactory', array('getMilestoneFromArtifact'));

        $this->sprint_1_artifact   = aMockArtifact()->withId(1)->build();
        $this->sprint_1_milestone  = aMilestone()->withArtifact($this->sprint_1_artifact)->build();
    }

    public function itReturnsEmptyArrayWhenThereAreNoArtifacts() {
        $empty_milestone = new Planning_NoMilestone(mock('Project'), mock('Planning'));

        $milestones = $this->milestone_factory->getSiblingMilestones($this->current_user, $empty_milestone);
        $this->assertEqual($milestones, array());
    }

    public function itReturnsTheMilestoneWhenThereAreNoSiblings() {
        stub($this->sprint_1_artifact)->getSiblings($this->current_user)->returns(array($this->sprint_1_artifact));
        
        $milestones = $this->milestone_factory->getSiblingMilestones($this->current_user, $this->sprint_1_milestone);
        
        $this->assertEqual($milestones, array($this->sprint_1_milestone));
    }
    
    public function itReturnsAnArrayWithTheMilestonesWhenOneSibling() {
        $sprint_2_artifact = aMockArtifact()->withId(2)->build();
        stub($this->sprint_1_artifact)->getSiblings($this->current_user)->returns(array($this->sprint_1_artifact, $sprint_2_artifact));
        
        $sprint_2_milestone = aMilestone()->withArtifact($sprint_2_artifact)->build();
        stub($this->milestone_factory)->getMilestoneFromArtifact($sprint_2_artifact)->returns($sprint_2_milestone);
        
        $milestones = $this->milestone_factory->getSiblingMilestones($this->current_user, $this->sprint_1_milestone);
        
        $this->assertEqual($milestones, array($this->sprint_1_milestone, $sprint_2_milestone));
    }    
}

class MilestoneFactory_GetCurrentMilestonesTest extends TuleapTestCase {
    private $current_user;
    private $milestone_factory;
    private $sprint_1_artifact;
    private $sprint_1_milestone;
    private $planning_factory;
    private $artifact_factory;
    
    public function setUp() {
        parent::setUp();
        $this->current_user      = aUser()->build();
        $this->planning_factory  = mock('PlanningFactory');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->milestone_factory = partial_mock(
            'Planning_MilestoneFactory',
            array('getMilestoneFromArtifact'),
            array($this->planning_factory, $this->artifact_factory, mock('Tracker_FormElementFactory'))
        );

        $this->sprint_1_artifact   = aMockArtifact()->withId(1)->build();
        $this->sprint_1_milestone  = aMilestone()->withArtifact($this->sprint_1_artifact)->build();

        $this->planning_id = 12;
        $this->planning_tracker_id = 123;
        $this->planning_tracker = aTracker()->withId($this->planning_tracker_id)->withProject(mock('Project'))->build();
        $this->planning    = aPlanning()->withId($this->planning_id)->withPlanningTracker($this->planning_tracker)->build();
        stub($this->planning_factory)->getPlanningWithTrackers($this->planning_id)->returns($this->planning);
    }
    
    public function itReturnsEmptyMilestoneWhenNothingMatches() {
        stub($this->artifact_factory)->getOpenArtifactsByTrackerIdUserCanView()->returns(array());
        $milestone = $this->milestone_factory->getCurrentMilestone($this->current_user, $this->planning_id);
        $this->assertIsA($milestone, 'Planning_NoMilestone');
    }
    
    public function itReturnsTheLastOpenArtifactOfPlanningTracker() {
        stub($this->artifact_factory)->getOpenArtifactsByTrackerIdUserCanView(
            $this->current_user,
            $this->planning_tracker_id
        )->returns(array('115' => $this->sprint_1_artifact, '104' => aMockArtifact()));
        
        stub($this->milestone_factory)->getMilestoneFromArtifact($this->sprint_1_artifact)->returns($this->sprint_1_milestone);
        
        $milestone = $this->milestone_factory->getCurrentMilestone($this->current_user, $this->planning_id);
        $this->assertEqual($milestone, $this->sprint_1_milestone);
    }
}
?>
