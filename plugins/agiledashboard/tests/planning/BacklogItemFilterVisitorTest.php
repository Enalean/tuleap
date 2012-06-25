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
require_once dirname(__FILE__).'/../../include/Planning/BacklogItemFilterVisitor.class.php';

class Planning_BacklogItemFilterVisitorTest extends Planning_BacklogItemFilterVisitorBaseTest {

    public function setUp() {
        parent::setUp();

        $this->already_planned_ids = array();

        $this->epic_tracker_id  = 111;
        $this->story_tracker_id = 112;

        $this->hierarchy         = mock('Tracker_Hierarchy');
        stub($this->hierarchy)->isChild($this->epic_tracker_id, $this->story_tracker_id)->returns(true);
        stub($this->hierarchy)->exists($this->epic_tracker_id)->returns(true);
        stub($this->hierarchy)->exists($this->story_tracker_id)->returns(true);
        $this->hierarchy_factory = stub('Tracker_HierarchyFactory')->getHierarchy()->returns($this->hierarchy);

        $this->root   = new TreeNode();
        $this->epic1  = $this->newTreeNode(1, $this->epic_tracker_id);
        $this->story1 = $this->newTreeNode(2, $this->story_tracker_id);
        $this->task   = $this->newTreeNode(3, 113);
        $this->bug    = $this->newTreeNode(4, 114);
        $this->epic2  = $this->newTreeNode(5, $this->epic_tracker_id);
        $this->story2 = $this->newTreeNode(6, $this->story_tracker_id);
        $this->story3 = $this->newTreeNode(7, $this->story_tracker_id);

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
        $visitor  = new Planning_BacklogItemFilterVisitor($this->epic_tracker_id, $this->hierarchy_factory, $this->already_planned_ids);
        $new_root = $this->root->accept($visitor);

        $this->assertEqual(count($new_root->getChildren()), 2);
        $this->assertEqual($new_root->getChild(0), $this->epic1);
        $this->assertEqual($new_root->getChild(1), $this->epic2);
    }

    public function itKeepsDescendantOfEpics() {
        $visitor  = new Planning_BacklogItemFilterVisitor($this->epic_tracker_id, $this->hierarchy_factory, $this->already_planned_ids);
        $new_root = $this->root->accept($visitor);

        $epic1 = $new_root->getChild(0);
        $this->assertEqual($epic1->getChild(0), $this->story1);
        $this->assertEqual($epic1->getChild(1), $this->story2);
        $this->assertEqual($epic1->getChild(0)->getChild(0), $this->task);
    }

    public function itMovesTheDeepBacklogItemsToTheRoot() {
        $visitor = new Planning_BacklogItemFilterVisitor($this->story_tracker_id, $this->hierarchy_factory, $this->already_planned_ids);
        $new_root = $this->root->accept($visitor);

        $this->assertEqual(count($new_root->getChildren()), 3);
        $this->assertEqual($new_root->getChild(0), $this->story1);
        $this->assertEqual($new_root->getChild(1), $this->story2);
        $this->assertEqual($new_root->getChild(2), $this->story3);
    }

}

class Planning_BacklogItemFilterVisitor_HierarchyTest extends Planning_BacklogItemFilterVisitorBaseTest {

    public function itDoesNotKeepItemsWhichDoesNotMatchTheTrackerHierarchy() {
        $this->already_planned_ids = array();

        $this->epic_tracker_id   = 111;
        $this->story_tracker_id  = 112;
        $this->sprint_tracker_id = 113;

        $this->root    = new TreeNode();
        $this->epic1   = $this->newTreeNode(1, $this->epic_tracker_id);
        $this->story1  = $this->newTreeNode(2, $this->story_tracker_id);
        $this->sprint1 = $this->newTreeNode(3, $this->sprint_tracker_id);
        $this->story2  = $this->newTreeNode(4, $this->story_tracker_id);

        $this->root->addChildren(
            $this->epic1->addChildren(
                $this->story1
            ),
            $this->sprint1->addChildren(
                $this->story2
            )
        );

        $hierarchy         = mock('Tracker_Hierarchy');
        $hierarchy_factory = mock('Tracker_HierarchyFactory');

        stub($hierarchy_factory)->getHierarchy(array($this->story_tracker_id))->returns($hierarchy);
        stub($hierarchy)->isChild($this->epic_tracker_id, $this->story_tracker_id)->returns(true);
        stub($hierarchy)->exists($this->epic_tracker_id)->returns(true);
        stub($hierarchy)->exists($this->story_tracker_id)->returns(true);

        $visitor  = new Planning_BacklogItemFilterVisitor($this->story_tracker_id, $hierarchy_factory, $this->already_planned_ids);
        $new_root = $this->root->accept($visitor);

        $this->assertEqual(count($new_root->getChildren()), 1);
        $this->assertEqual($new_root->getChild(0), $this->story1);
    }
}

class Planning_BacklogItemFilterVisitor_AlreadyPlannedItemsTest extends Planning_BacklogItemFilterVisitorBaseTest {

    public function itDoesNotKeepItemsThatAreAlreadyPlannedSomewhereElse() {
        $this->epic_tracker_id   = 111;
        $this->story_tracker_id  = 112;
        $this->sprint_tracker_id = 113;

        $this->story2_id = 3;

        $this->already_planned_id = array($this->story2_id);

        $this->root    = new TreeNode();
        $this->epic1   = $this->newTreeNode(1, $this->epic_tracker_id);
        $this->story1  = $this->newTreeNode(2, $this->story_tracker_id);
        $this->story2  = $this->newTreeNode($this->story2_id, $this->story_tracker_id);

        $this->root->addChildren(
            $this->epic1->addChildren(
                $this->story1,
                $this->story2
            )
        );

        $hierarchy         = mock('Tracker_Hierarchy');
        $hierarchy_factory = mock('Tracker_HierarchyFactory');

        stub($hierarchy_factory)->getHierarchy(array($this->story_tracker_id))->returns($hierarchy);
        stub($hierarchy)->exists()->returns(true);

        $visitor  = new Planning_BacklogItemFilterVisitor($this->story_tracker_id, $hierarchy_factory, $this->already_planned_id);
        $new_root = $this->root->accept($visitor);

        $this->assertEqual(count($new_root->getChildren()), 1);
        $this->assertEqual($new_root->getChild(0), $this->story1);
    }
}

class Planning_BacklogItemFilterVisitorBaseTest extends TuleapTestCase {
    
    protected function newTreeNode($id, $tracker_id) {
        $node = new TreeNode();
        $node->setData(array('id' => $id, 
                             'tracker_id' => $tracker_id));
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getId()->returns($id);
        stub($artifact)->getTrackerId()->returns($tracker_id);
        $node->setObject($artifact);
        return $node;
    }
    
}

?>
