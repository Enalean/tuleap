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

require_once dirname(__FILE__).'/../../include/Planning/BacklogItemFilterVisitor.class.php';

class Planning_BacklogItemFilterVisitorTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();

        $this->epic_tracker_id  = 111;
        $this->story_tracker_id = 112;
        
        $this->root   = new TreeNode();
        $this->epic1  = new TreeNode(array('id' => 1, 'tracker_id' => $this->epic_tracker_id));
        $this->story1 = new TreeNode(array('id' => 2, 'tracker_id' => $this->story_tracker_id));
        $this->task   = new TreeNode(array('id' => 3, 'tracker_id' => 113));
        $this->bug    = new TreeNode(array('id' => 4, 'tracker_id' => 114));
        $this->epic2  = new TreeNode(array('id' => 5, 'tracker_id' => $this->epic_tracker_id));
        $this->story2 = new TreeNode(array('id' => 6, 'tracker_id' => $this->story_tracker_id));
        $this->story3 = new TreeNode(array('id' => 7, 'tracker_id' => $this->story_tracker_id));

        $this->root->addChildren(
            $this->epic1->addChildren(
                $this->story1->addChildren(
                    $this->task
                ),
                $this->story2
            ),
            $this->bug,
            $this->story3,
            $this->epic2
        );
    }
    
    public function itKeepsOnlyEpicsAtRoot() {
        $visitor  = new Planning_BacklogItemFilterVisitor($this->epic_tracker_id);
        $new_root = $this->root->accept($visitor);
                
        $this->assertEqual(count($new_root->getChildren()), 2);
        $this->assertEqual($new_root->getChild(0), $this->epic1);
        $this->assertEqual($new_root->getChild(1), $this->epic2);
    }

    public function itKeepsDescendantOfEpics() {
        $visitor  = new Planning_BacklogItemFilterVisitor($this->epic_tracker_id);
        $new_root = $this->root->accept($visitor);

        $epic1 = $new_root->getChild(0);
        $this->assertEqual($epic1->getChild(0), $this->story1);
        $this->assertEqual($epic1->getChild(1), $this->story2);
        $this->assertEqual($epic1->getChild(0)->getChild(0), $this->task);
    }
    
    public function itMovesTheDeepBacklogItemsToTheRoot() {
        $visitor = new Planning_BacklogItemFilterVisitor($this->story_tracker_id);
        $new_root = $this->root->accept($visitor);
                
        $this->assertEqual(count($new_root->getChildren()), 3);
        $this->assertEqual($new_root->getChild(0), $this->story1);
        $this->assertEqual($new_root->getChild(1), $this->story2);
        $this->assertEqual($new_root->getChild(2), $this->story3);
    }

}

class Planning_BacklogItemFilterVisitor_BigDeepTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();

        $this->epic_tracker_id  = 111;
        $this->story_tracker_id = 112;
        
        $this->root   = new TreeNode();
        $this->theme1 = new TreeNode(array('id' => 1, 'tracker_id' => $this->epic_tracker_id));
        $this->epic1  = new TreeNode(array('id' => 2, 'tracker_id' => $this->epic_tracker_id));
        $this->story1 = new TreeNode(array('id' => 3, 'tracker_id' => $this->story_tracker_id));
        $this->story2 = new TreeNode(array('id' => 4, 'tracker_id' => $this->story_tracker_id));
        $this->story3 = new TreeNode(array('id' => 5, 'tracker_id' => $this->story_tracker_id));
        $this->story4 = new TreeNode(array('id' => 6, 'tracker_id' => $this->story_tracker_id));

        $this->root->addChildren(
            $this->theme1->addChildren(
                $this->epic1->addChildren(
                    $this->story1
                ),
                $this->story2->addChildren(
                    $this->story3
                )
            ),
            $this->story4
        );
    }
    
    public function itMovesTheDeepBacklogItemsToTheRoot() {
        $visitor = new Planning_BacklogItemFilterVisitor($this->story_tracker_id);
        $new_root = $this->root->accept($visitor);
                
        $this->assertEqual(count($new_root->getChildren()), 3);
        $this->assertEqual($new_root->getChild(0), $this->story1);
        $this->assertEqual($new_root->getChild(1), $this->story2);
        $this->assertEqual($new_root->getChild(2), $this->story4);
    }

}

?>
