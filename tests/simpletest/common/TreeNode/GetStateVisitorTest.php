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

class TreeNode_GetStateVisitorVisitorTest extends TuleapTestCase
{

    public const STATE_NODE  = TreeNode_GetStateVisitor::STATE_NODE;
    public const STATE_LAST  = TreeNode_GetStateVisitor::STATE_LAST;
    public const STATE_BLANK = TreeNode_GetStateVisitor::STATE_BLANK;
    public const STATE_PIPE  = TreeNode_GetStateVisitor::STATE_PIPE;

    public function testOneNodeShouldHaveStateLast()
    {
        $root    = new TreeNode();
        $node    = $this->GivenANodeInAparent($root);
        $visitor = $this->GivenAVisitor($root);

        $this->assertNodeHasState($visitor, $node, array(self::STATE_LAST));
    }

    public function testTwoNodesOnSameHierarchyShouldHaveStatesNodeThenLast()
    {
        $root   = new TreeNode();
        $node1  = $this->GivenANodeInAparent($root);
        $node2  = $this->GivenANodeInAparent($root);

        $visitor = $this->GivenAVisitor($root);

        $this->assertNodeHasState($visitor, $node1, array(self::STATE_NODE));
        $this->assertNodeHasState($visitor, $node2, array(self::STATE_LAST));
    }

    public function testDeeperHierarchyShouldReturnLastThenEmptyLast()
    {
        $root   = new TreeNode();
        $node1  = $this->GivenANodeInAparent($root);
        $node2  = $this->GivenANodeInAparent($node1);

        $visitor = $this->GivenAVisitor($root);

        $this->assertNodeHasState($visitor, $node1, array(self::STATE_LAST));
        $this->assertNodeHasState($visitor, $node2, array(self::STATE_BLANK, self::STATE_LAST));
    }

    public function testComplexHierarchy()
    {
        /*

        +- story 7
        |  `- task 5
        |     `- risk 11
        `- story6

        */
        $root    = new TreeNode();
        $story_7 = $this->GivenANodeInAparent($root);
        $task_5  = $this->GivenANodeInAparent($story_7);
        $risk_11 = $this->GivenANodeInAparent($task_5);
        $story_6 = $this->GivenANodeInAparent($root);

        $visitor = $this->GivenAVisitor($root);

        $this->assertNodeHasState($visitor, $story_7, array(self::STATE_NODE));
        $this->assertNodeHasState($visitor, $task_5, array(self::STATE_PIPE, self::STATE_LAST));
        $this->assertNodeHasState($visitor, $risk_11, array(self::STATE_PIPE, self::STATE_BLANK, self::STATE_LAST));
        $this->assertNodeHasState($visitor, $story_6, array(self::STATE_LAST));
    }

    private function GivenAVisitor($node)
    {
        $visitor = new TreeNode_GetStateVisitor();
        $node->accept($visitor);
        return $visitor;
    }

    private function GivenANodeInAparent(TreeNode $parent)
    {
        $node = new TreeNode();
        $parent->addChild($node);
        return $node;
    }

    private function assertNodeHasState($visitor, TreeNode $node, array $expected_state)
    {
        $state = $visitor->getState($node);
        //var_dump($state);
        $this->assertEqual($expected_state, $state);
    }
}
