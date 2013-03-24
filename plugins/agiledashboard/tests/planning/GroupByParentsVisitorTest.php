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

require_once dirname(__FILE__).'/../../include/constants.php';
require_once AGILEDASHBOARD_BASE_DIR .'/Planning/GroupByParentsVisitor.class.php';

class Planning_GroupByParentsVisitorTest extends TuleapTestCase {

    private function buildTreeNode() {
        $root = new TreeNode();
        $root->setId(0);
        foreach (func_get_args() as $arg) {
            $node = new TreeNode();
            $node->setObject($arg);
            $node->setId($arg->getId());
            $root->addChild($node);
        }
        return $root;
    }

    public function visit(TreeNode $node) {
        $output   = (string)$node->getId();
        $children = $node->getChildren();
        if ($children) {
            $children_output = array();
            foreach ($children as $child) {
                $children_output[] = $child->accept($this);
            }
            $output .= ' ('. implode(', ', $children_output) .')';
        }
        return $output;
    }

    public function setUp() {
        $user = mock('PFUser');

        $this->epic = mock('Tracker_Artifact');
        stub($this->epic)->getId()->returns(123);
        stub($this->epic)->getAllAncestors($user)->returns(array());

        $this->userstory = mock('Tracker_Artifact');
        stub($this->userstory)->getId()->returns(456);
        stub($this->userstory)->getAllAncestors($user)->returns(array($this->epic));

        $this->otheruserstory = mock('Tracker_Artifact');
        stub($this->otheruserstory)->getId()->returns(457);
        stub($this->otheruserstory)->getAllAncestors($user)->returns(array($this->epic));

        $this->task = mock('Tracker_Artifact');
        stub($this->task)->getId()->returns(789);
        stub($this->task)->getAllAncestors($user)->returns(array($this->userstory, $this->epic));

        $this->othertask = mock('Tracker_Artifact');
        stub($this->othertask)->getId()->returns(101);
        stub($this->othertask)->getAllAncestors($user)->returns(array($this->otheruserstory, $this->epic));

        $this->anothertask = mock('Tracker_Artifact');
        stub($this->anothertask)->getId()->returns(102);
        stub($this->anothertask)->getAllAncestors($user)->returns(array($this->otheruserstory, $this->epic));

        $this->visitor = new Planning_GroupByParentsVisitor($user);
    }

    public function itRetrievesTheParentOfAnItem() {
        $root = $this->buildTreeNode($this->userstory, $this->otheruserstory);
        $root->accept($this->visitor);
        $this->assertEqual('0 (123 (456, 457))', $root->accept($this));
    }

    public function itRetrievesAllParentsOfAnItem() {
        $root = $this->buildTreeNode($this->task);
        $root->accept($this->visitor);
        $this->assertEqual('0 (123 (456 (789)))', $root->accept($this));
    }

    public function itRetrievesAllParentsOfItemsInDifferentHierarchy() {
        $root = $this->buildTreeNode($this->task, $this->othertask);
        $root->accept($this->visitor);
        $this->assertEqual('0 (123 (456 (789), 457 (101)))', $root->accept($this));
    }

    public function itDoesNothingIfNoParent() {
        $root = $this->buildTreeNode($this->epic);
        $root->accept($this->visitor);
        $this->assertEqual('0 (123)', $root->accept($this));
    }

    public function itDoesntAddManyTimesTheSameItem() {
        $root = $this->buildTreeNode($this->othertask, $this->anothertask);
        $root->accept($this->visitor);
        $this->assertEqual('0 (123 (457 (101, 102)))', $root->accept($this));
    }
}
?>
