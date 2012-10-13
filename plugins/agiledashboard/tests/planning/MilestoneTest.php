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

require_once dirname(__FILE__).'/../../include/Planning/ArtifactMilestone.class.php';
require_once dirname(__FILE__).'/../../include/Planning/NoMilestone.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../builders/aMilestone.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aTreeNode.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';

abstract class Planning_MilestoneTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->project   = stub('Project')->getID()->returns(123);
        $this->planning  = aPlanning()->withId(9999)->build();
    }
    
    public function itHasAPlanning() {
        $this->assertEqual($this->planning, $this->milestone->getPlanning());
        $this->assertEqual($this->planning->getId(), $this->milestone->getPlanningId());
    }
    
    public function itHasAProject() {
        $this->assertEqual($this->project, $this->milestone->getProject());
        $this->assertEqual($this->project->getID(), $this->milestone->getGroupId());
    }

}

class Planning_NoMilestoneTest extends Planning_MilestoneTest {
    public function setUp() {
        parent::setUp();
        $this->milestone = new Planning_NoMilestone($this->project, $this->planning);
    }
}

class Planning_ArtifactMilestoneTest extends Planning_MilestoneTest {
    
    protected $project;
    protected $planning;
    private $artifact;
    
    /**
     * @var Planning_Milestone
     */
    protected $milestone;
    
    public function setUp() {
        parent::setUp();
        
        $this->project   = stub('Project')->getID()->returns(123);
        $this->planning  = aPlanning()->build();
        $this->artifact  = aMockArtifact()->withTitle('Foo')
                                          ->build();
        $this->milestone = new Planning_ArtifactMilestone($this->project,
                                                  $this->planning,
                                                  $this->artifact);
    }
    
    public function itRepresentsAnArtifact() {
        $this->assertEqual($this->milestone->getArtifact(), $this->artifact);
    }
    
    public function itDelegatesArtifactIdRetrieval() {
        $this->assertEqual($this->milestone->getArtifactId(), $this->artifact->getId());
    }
    
    public function itDelegatesArtifactTitleRetrieval() {
        $artifact = aMockArtifact()->withTitle('a simple little artifact')->build();
        $milestone = new Planning_ArtifactMilestone($this->project, mock('Planning'), $artifact);
        $this->assertEqual($milestone->getArtifactTitle(), $artifact->getTitle());
    }

    public function itMayHavePlannedArtifacts() {
        $this->assertEqual($this->milestone->getPlannedArtifacts(), null);
        
        $planned_artifacts = new TreeNode();
        $this->milestone   = new Planning_ArtifactMilestone($this->project,
                                                     $this->planning,
                                                     $this->artifact,
                                                     $planned_artifacts);
        
        $this->assertEqual($this->milestone->getPlannedArtifacts(), $planned_artifacts);
    }
    
    public function itMayBeNull() {
        $this->milestone = new Planning_NoMilestone($this->project, $this->planning);
        
        $this->assertNull($this->milestone->getArtifact());
        $this->assertNull($this->milestone->getArtifactId());
        $this->assertNull($this->milestone->getArtifactTitle());
        $this->assertTrue($this->milestone->userCanView(mock('User')), "any user should be able to read an empty milstone");
    }

    public function itHasATitle() {
        $this->milestone = new Planning_ArtifactMilestone($this->project,
                                                  $this->planning,
                                                  $this->artifact);
        $this->assertEqual($this->milestone->getArtifactTitle(), 'Foo');
    }
    
}

class Milestone_linkedArtifactTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->project = mock('Project');
    }
    public function itGetsLinkedArtifactsOfTheRootLevelArtifact() {
        $artifact      = aMockArtifact()->withId(1111)->withUniqueLinkedArtifacts(array(aMockArtifact()->build()))->build();

        $milestone     = new Planning_ArtifactMilestone($this->project, mock('Planning'), $artifact);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 1);
    }
    
    public function itGetsTheArtifactsChildNodes() {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $child1_artifact   = aMockArtifact()->withId(1111)->withTitle('child artifact 1')->build();
        $child2_artifact   = aMockArtifact()->withId(2222)->withTitle('child artifact 2')->build();
        $planned_artifacts = aNode()->withObject($root_artifact)
                                    ->withChildren(
                                        aNode()->withObject($child1_artifact),
                                        aNode()->withObject($child2_artifact))
                                    ->build();

        
        $milestone = new Planning_ArtifactMilestone($this->project, mock('Planning'), $root_artifact, $planned_artifacts);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 2);
    }
    
    public function itGetsTheArtifactsOfNestedChildNodes() {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $depth1_artifact   = aMockArtifact()->withId(1111)->withTitle('depth 1 artifact')->build();
        $depth2_artifact   = aMockArtifact()->withId(2222)->withTitle('depth 2 artifact')->build();
        $planned_artifacts = aNode()->withObject($root_artifact)
                                    ->withChild(
                                        aNode()->withObject($depth1_artifact)
                                               ->withChild(aNode()->withObject($depth2_artifact)))
                                    ->build();

        
        $milestone = new Planning_ArtifactMilestone($this->project, mock('Planning'), $root_artifact, $planned_artifacts);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 2);
    }
    
    public function itGetsTheLinkedArtifactsOfChildNodes() {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $linked_artifact_1 = aMockArtifact()->build();
        $linked_artifact_2 = aMockArtifact()->build();
        $artifact          = aMockArtifact()->withId(1111)
                                            ->withUniqueLinkedArtifacts(array($linked_artifact_1, $linked_artifact_2))
                                            ->build();
        $planned_artifacts = aNode()->withObject($root_artifact)
                                    ->withChild(
                                        aNode()->withObject($artifact))
                                    ->build();

        
        $milestone = new Planning_ArtifactMilestone($this->project, mock('Planning'), $root_artifact, $planned_artifacts);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 3);
    }
}

class Planning_Milestone_WhenFirstCreatedTest extends TuleapTestCase {
    
    public function setUp() {
        $this->project  = stub('Project')->getID()->returns(123);
        $this->planning  = mock('Planning');
        $this->artifact  = mock('Tracker_Artifact');
        $this->milestone = new Planning_ArtifactMilestone($this->project,
                                                  $this->planning,
                                                  $this->artifact);
    }
    
    public function itHasNoSubMilestones() {
        $this->assertIdentical($this->milestone->getSubMilestones(), array());
    }
    
    public function itAcceptsNewSubMilestones() {
        $sub_milestone_1 = aMilestone()->withinTheSameProjectAs($this->milestone)->build();
        $sub_milestone_2 = aMilestone()->withinTheSameProjectAs($this->milestone)->build();
        
        $this->milestone->addSubMilestones(array($sub_milestone_1, $sub_milestone_2));
        $this->assertIdentical($this->milestone->getSubMilestones(),
                               array($sub_milestone_1, $sub_milestone_2));
    }
}

class Planning_Milestone_WithSubMilestones extends TuleapTestCase {
    public function itCanBeAddedNewSubMilestones() {
        $sub_milestone_1 = aMilestone()->build();
        $sub_milestone_2 = aMilestone()->withinTheSameProjectAs($sub_milestone_1)->build();
        $sub_milestone_3 = aMilestone()->withinTheSameProjectAs($sub_milestone_1)->build();
        $sub_milestone_4 = aMilestone()->withinTheSameProjectAs($sub_milestone_1)->build();
        $this->milestone = aMilestone()->withinTheSameProjectAs($sub_milestone_1)
                                       ->withSubMilestones(array($sub_milestone_1, $sub_milestone_2))->build();
        
        $this->milestone->addSubMilestones(array($sub_milestone_3, $sub_milestone_4));
        
        $this->assertIdentical($this->milestone->getSubMilestones(),
                               array($sub_milestone_1, $sub_milestone_2, $sub_milestone_3, $sub_milestone_4));
    }
}
?>
