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

require_once dirname(__FILE__).'/../../common.php';

abstract class AgileDashboard_MilestonePlanningPresenter_Common extends TuleapTestCase {

    protected function getAnArtifact($artifact_id, $children = array(), $tracker = null) {
        if (!$tracker) {
            $tracker = stub('Tracker')->userCanView()->returns(true);
        }

        $artifact = stub('Tracker_Artifact')->getUniqueLinkedArtifacts()->returns($children);
        stub($artifact)->getId()->returns($artifact_id);
        stub($artifact)->getTitle()->returns('Artifact ' . $artifact_id);
        stub($artifact)->fetchDirectLinkToArtifact()->returns('');
        stub($artifact)->getTracker()->returns($tracker);
        return $artifact;
    }

}

class AgileDashboard_MilestonePlanningPresenterTest extends AgileDashboard_MilestonePlanningPresenter_Common {

    public function setUp() {
        parent::setUp();
        $this->user                = mock('PFUser');
        $this->planning_tracker_id = 191;
        $this->planning_tracker    = mock('Tracker');
        $this->planning            = mock('Planning');
        $this->content_view        = mock('Tracker_CrossSearch_SearchContentView');
        $this->artifacts_to_select = array();
        $this->artifact            = null;

        $factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($factory);

        $this->generateABunchOfArtifacts($factory);

        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        Tracker_Hierarchy_HierarchicalTrackerFactory::setInstance($hierarchy_factory);


        stub($this->planning)->getPlanningTrackerId()->returns($this->planning_tracker_id);
        stub($this->planning)->getPlanningTracker()->returns($this->planning_tracker);
        stub($this->planning_tracker)->getId()->returns($this->planning_tracker_id);
    }

    private function generateABunchOfArtifacts($factory) {
        for ($i = 30 ; $i < 40 ; ++$i) {
            $artifact = mock('Tracker_Artifact');
            stub($artifact)->getId()->returns($i);
            stub($artifact)->getTitle()->returns('Artifact '. $i);
            stub($artifact)->getUri()->returns('/bar');
            stub($artifact)->getXRef()->returns('art #'. $i);
            stub($factory)->getArtifactById($i)->returns($artifact);
        }
    }

    public function tearDown() {
        Tracker_ArtifactFactory::clearInstance();
        Tracker_Hierarchy_HierarchicalTrackerFactory::clearInstance();
    }

    protected function getAPresenter(ArtifactNode $planned_artifacts_tree = null) {
        $milestone = new Planning_ArtifactMilestone(
            mock('Project'),
            $this->planning,
            $this->artifact,
            $planned_artifacts_tree
        );
        $milestone_plan = new Planning_MilestonePlan(
            $milestone,
            array(),
            0,
            0
        );

        return new AgileDashboard_MilestonePlanningPresenter(
            $this->content_view,
            $milestone_plan,
            $this->user,
            'planning['. (int)$this->planning->getId() .']='
        );
    }

    protected function getATreeNode(Tracker_Artifact $artifact, $artifact_links = array(), $classname = "planning-draggable-alreadyplanned") {
        $node = new ArtifactNode($artifact);
        //$node->setId($tree_node_id);
        foreach($artifact_links as $node_child) {
            $node->addChild($node_child);
        }
        return $node;
    }

    protected function assertEqualTreeNodes($node1, $node2) {
        $this->assertEqual($node1->getObject(), $node2->getObject());
        $this->assertEqual($node1->getId(), $node2->getId());
        $children1 = $node1->getChildren();
        $children2 = $node2->getChildren();
        $this->assertEqual(count($children1), count($children2));
        foreach($children1 as $child_num => $child) {
            $this->assertEqualTreeNodes($child, $children2[$child_num]);
        }
    }

    /**
     * artifacct parent 30
     * 	- artifact 33
     * 	- artifact 34
     * 		- artifact 35
     * 	- artifact 36
     * 		- artifact 37
     * 		- artifact 38
     */
    public function itCanReturnLinkedItemsForADepthOfOne() {
        $artifact30 = $this->getAnArtifact(30);
        $artifact33 = $this->getAnArtifact(33);
        $artifact35 = $this->getAnArtifact(35);
        $artifact34 = $this->getAnArtifact(34, array($artifact35));
        $artifact37 = $this->getAnArtifact(37);
        $artifact38 = $this->getAnArtifact(38);
        $artifact36 = $this->getAnArtifact(36, array($artifact37, $artifact38));

        $this->artifact = $this->getAnArtifact(30, array($artifact33, $artifact34, $artifact36));

        $node33 = $this->getATreeNode($artifact33);
        $node34 = $this->getATreeNode($artifact34, array($this->getATreeNode($artifact35)));
        $node36 = $this->getATreeNode($artifact36, array($this->getATreeNode($artifact37), $this->getATreeNode($artifact38)));
        $node_parent = $this->getATreeNode($artifact30, array($node33, $node34, $node36));

        $presenter = $this->getAPresenter($node_parent);

        $result = $presenter->getPlannedArtifactsTree();
        $this->assertEqualTreeNodes($node_parent, $result);
    }

    /**
    * artifacct parent 30
    * 	- artifact 36
    * 		- artifact 37
    * 		- artifact 38
    * 	      - artifact 39
    */
    public function itReturnsOnlyOneLevelOnLinkedItems() {
        $artifact30 = $this->getAnArtifact(30);
        $artifact39 = $this->getAnArtifact(39);
        $artifact37 = $this->getAnArtifact(37);
        $artifact38 = $this->getAnArtifact(38, array($artifact39));
        $artifact36 = $this->getAnArtifact(36, array($artifact37, $artifact38));

        $this->artifact = $this->getAnArtifact(30, array($artifact36));

        $node36 = $this->getATreeNode($artifact36, array($this->getATreeNode($artifact37), $this->getATreeNode($artifact38)));
        $node_parent = $this->getATreeNode($artifact30, array($node36));

        $presenter = $this->getAPresenter($node_parent);

        $result = $presenter->getPlannedArtifactsTree();
        $this->assertEqualTreeNodes($node_parent, $result);
    }
}

class AgileDashboard_MilestonePlanningPresenter_AssertPermissionsTest extends AgileDashboard_MilestonePlanningPresenter_Common {
    private $sprint_artifact;

    public function setUp() {
        parent::setUp();
        $this->user                = mock('PFUser');
        $this->planning            = mock('Planning');
        $this->content_view        = mock('Tracker_CrossSearch_SearchContentView');
        $this->artifacts_to_select = array();
        $this->sprint_artifact     = null;

    }

    public function itDisplaysDestinationOnlyIfUserCanAccessTheTracker() {
        $sprint_tracker            = stub('Tracker')->userCanView()->returns(false);

        $this->sprint_artifact = $this->getAnArtifact(30, array($this->getAnArtifact(37)), $sprint_tracker);
        $milestone = aMilestone()->withArtifact($this->sprint_artifact)->withGroup(mock('Project'))->withPlanning($this->planning)->build();
        $this->presenter = new AgileDashboard_MilestonePlanningPresenter(
                        $this->content_view,
                        aMilestonePlan()->withMilestone($milestone)->build(),
                        $this->user,
                        ''
        );

        $this->assertNull($this->presenter->getPlannedArtifactsTree());
    }
}

class AgileDashboard_MilestonePlanningPresenter_OverCapacityTest extends AgileDashboard_MilestonePlanningPresenter_Common {
    private $presenter;
    private $sprint_milestone_plan;

    public function setUp() {
        parent::setUp();
        $this->user                = mock('PFUser');
        $this->planning            = mock('Planning');
        $this->content_view        = mock('Tracker_CrossSearch_SearchContentView');
        $this->artifacts_to_select = array();
        $this->sprint_milestone    = stub('Planning_ArtifactMilestone')->getPlanning()->returns($this->planning);

    }

    private function isOverCapacity($sprint_milestone_plan) {
        $this->presenter = new AgileDashboard_MilestonePlanningPresenter(
            $this->content_view,
            $sprint_milestone_plan,
            $this->user,
            ''
        );
        return $this->presenter->isOverCapacity();
    }

    public function itIsOverCapacityIfRemainingEffortIsGreaterThanCapacity() {
        $sprint_milestone_plan = aMilestonePlan()
            ->withMilestone($this->sprint_milestone)
            ->withRemainingEffort(5)
            ->withCapacity(3)
            ->build();
        $this->assertTrue($this->isOverCapacity($sprint_milestone_plan));
    }

    public function itIsNotOverCapacityIfRemainingEffortIsEqualTo0() {
        $sprint_milestone_plan = aMilestonePlan()
            ->withMilestone($this->sprint_milestone)
            ->withRemainingEffort(0)
            ->withCapacity(3)
            ->build();
        $this->assertFalse($this->isOverCapacity($sprint_milestone_plan));
    }

    public function itIsNotOverCapacityIfNoRemainingEffort() {
        $sprint_milestone_plan = aMilestonePlan()
            ->withMilestone($this->sprint_milestone)
            ->withRemainingEffort(null)
            ->withCapacity(3)
            ->build();
        $this->assertFalse($this->isOverCapacity($sprint_milestone_plan));
    }

    public function itIsNotOverCapacityIfNoCapacity() {
        $sprint_milestone_plan = aMilestonePlan()
            ->withMilestone($this->sprint_milestone)
            ->withRemainingEffort(5)
            ->withCapacity(null)
            ->build();
        $this->assertFalse($this->isOverCapacity($sprint_milestone_plan));
    }

    public function itIsNotOverCapacityCapacityIsNegative() {
        $sprint_milestone_plan = aMilestonePlan()
            ->withMilestone($this->sprint_milestone)
            ->withRemainingEffort(0)
            ->withCapacity(-5)
            ->build();
        $this->assertTrue($this->isOverCapacity($sprint_milestone_plan));
    }
}
?>
