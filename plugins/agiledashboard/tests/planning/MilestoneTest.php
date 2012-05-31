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

require_once dirname(__FILE__).'/../../include/Planning/Milestone.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';

class MilestoneTest extends TuleapTestCase {
    
    private $group_id;
    private $planning;
    private $artifact;
    
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    
    public function setUp() {
        parent::setUp();
        
        $this->group_id  = 123;
        $this->planning  = aPlanning()->build();
        $this->artifact  = anArtifact()
                            ->withId(6666)
                            ->build();
        $this->milestone = new Planning_Milestone($this->group_id,
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
        $milestone = new Planning_Milestone(0, mock('Planning'), $artifact);
        $this->assertEqual($milestone->getArtifactTitle(), $artifact->getTitle());
    }

    public function itMayHavePlannedArtifacts() {
        $this->assertEqual($this->milestone->getPlannedArtifacts(), null);
        
        $planned_artifacts = new TreeNode();
        $this->milestone   = new Planning_Milestone($this->group_id,
                                                     $this->planning,
                                                     $this->artifact,
                                                     $planned_artifacts);
        
        $this->assertEqual($this->milestone->getPlannedArtifacts(), $planned_artifacts);
    }
    
    public function itMayBeNull() {
        $this->milestone = new Planning_NoMilestone($this->group_id, $this->planning);
        
        $this->assertNull($this->milestone->getArtifact());
        $this->assertNull($this->milestone->getArtifactId());
        $this->assertNull($this->milestone->getArtifactTitle());
        $this->assertTrue($this->milestone->userCanView(mock('User')), "any user should be able to read an empty milstone");
    }
}

class Milestone_linkedArtifactTest extends TuleapTestCase {
    
    public function itGetsLinkedArtifactsTheRootLevelArtifact() {
        $artifact      = aMockArtifact()->withId(1111)->withUniqueLinkedArtifacts(array(aMockArtifact()->build()))->build();

        $milestone     = new Planning_Milestone(0, mock('Planning'), $artifact);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 1);
    }
    
    public function itGetsTheArtifactsChildNodes() {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $child1_artifact   = aMockArtifact()->withId(1111)->withTitle('child artifact 1')->build();
        $child2_artifact   = aMockArtifact()->withId(2222)->withTitle('child artifact 2')->build();
        $planned_artifacts = aNode()->withArtifact($root_artifact)
                                    ->withChildren(
                                        aNode()->withArtifact($child1_artifact),
                                        aNode()->withArtifact($child2_artifact))
                                    ->build();

        
        $milestone = new Planning_Milestone(0, mock('Planning'), $root_artifact, $planned_artifacts);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 2);
    }
    
    public function itGetsTheArtifactsOfNestedChildNodes() {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $depth1_artifact   = aMockArtifact()->withId(1111)->withTitle('depth 1 artifact')->build();
        $depth2_artifact   = aMockArtifact()->withId(2222)->withTitle('depth 2 artifact')->build();
        $planned_artifacts = aNode()->withArtifact($root_artifact)
                                    ->withChild(
                                        aNode()->withArtifact($depth1_artifact)
                                               ->withChild(aNode()->withArtifact($depth2_artifact)))
                                    ->build();

        
        $milestone = new Planning_Milestone(0, mock('Planning'), $root_artifact, $planned_artifacts);
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
        $planned_artifacts = aNode()->withArtifact($root_artifact)
                                    ->withChild(
                                        aNode()->withArtifact($artifact))
                                    ->build();

        
        $milestone = new Planning_Milestone(0, mock('Planning'), $root_artifact, $planned_artifacts);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 3);
    }
}
    

class Test_TreeNode_Builder {

    private $children;
    private $data;
    
    public function __construct() {
        $this->children = array();
    }
    
    /**
     * @return \Test_TreeNode_Builder 
     */
    public function withChildren() {
        $args = func_get_args();
        foreach ($args as $node_builder) {
            $this->children[] = $node_builder->build();
        }
        return $this;
    }

    /**
     * @return \Test_TreeNode_Builder 
     */
    public function withChild(Test_TreeNode_Builder $child_node_builder) {
        $this->children[] = $child_node_builder->build();
        return $this;
    }

    /**
     * @return \Test_TreeNode_Builder 
     */
    public function withArtifact($artifact) {
        $this->data['artifact'] = $artifact;
        return $this;
    }
    
    public function build() {
        $node = new TreeNode();
        $node->setChildren($this->children);
        $node->setData($this->data);
        return $node;
    }


}

/**
 * @return \Test_TreeNode_Builder 
 */
function aNode() {
    return new Test_TreeNode_Builder();
}
?>
