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
    
//    public function itDelegatesLinkedArtifactsRetrieval() {
//        $artifact1 = anArtifact()->withId(1111)->build();
//        $artifact2 = anArtifact()->withId(2222)->build();
//        $linkedArtifacts = array($artifact1, $artifact2);
//        $artifact = aMockArtifact()->withLinkedArtifacts($linkedArtifacts)->build();
//        $milestone = new Planning_Milestone(0, mock('Planning'), $artifact);
//        
//        $this->assertEqual($milestone->getLinkedArtifacts(mock('User')), $linkedArtifacts);
//    }
//    
    public function itGetsLinkedArtifactsTheRootLevelArtifact() {
        $artifact = aMockArtifact()->withId(1111)->withUniqueLinkedArtifacts(array(aMockArtifact()->build()))->build();
//        $planned_artifacts = aNode()->withArtifact($artifact)->build();

        $milestone = new Planning_Milestone(0, mock('Planning'), $artifact);
        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
        $this->assertEqual(count($all_artifacts), 1);
    }
    
    public function itGetsTheArtifactsOfAChildNode() {
//        $root_artifact = aMockArtifact()->withId(1111)->withTitle('root artifact')->build();
//        $child_artifact = aMockArtifact()->withId(1111)->withTitle('child artifact')->build();
//        $planned_artifacts = aNode()->withArtifact($root_artifact)
//                                    ->withChild(
//                                        aNode()->withArtifact($child_artifact))
//                             ->build();
//
//        
//        $milestone = new Planning_Milestone(0, mock('Planning'), $root_artifact, $planned_artifacts);
//        $all_artifacts = $milestone->getLinkedArtifacts(mock('User'));
//        $this->assertEqual(count($all_artifacts), 1);
    }
    
    public function itGetsLinkedArtifactsOfThePlannedOnes4() {
//        $root_node = aNode()->withChildren(
//                                aNode()->withChild(aNode()),
//                                aNode())
//                     ->build();
//        $all_artifacts = $milestone->getLinkedArtifacts();
//        $this->assertEqual(count($all_artifacts), 4);
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
