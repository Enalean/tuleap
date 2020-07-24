<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GetStateVisitorTest extends TestCase
{
    public const STATE_NODE  = TreeNode_GetStateVisitor::STATE_NODE;
    public const STATE_LAST  = TreeNode_GetStateVisitor::STATE_LAST;
    public const STATE_BLANK = TreeNode_GetStateVisitor::STATE_BLANK;
    public const STATE_PIPE  = TreeNode_GetStateVisitor::STATE_PIPE;

    public function testOneNodeShouldHaveStateLast(): void
    {
        $root    = new TreeNode();
        $node    = $this->givenANodeInAparent($root);
        $visitor = $this->givenAVisitor($root);

        $this->assertNodeHasState($visitor, $node, [self::STATE_LAST]);
    }

    public function testTwoNodesOnSameHierarchyShouldHaveStatesNodeThenLast(): void
    {
        $root   = new TreeNode();
        $node1  = $this->givenANodeInAparent($root);
        $node2  = $this->givenANodeInAparent($root);

        $visitor = $this->givenAVisitor($root);

        $this->assertNodeHasState($visitor, $node1, [self::STATE_NODE]);
        $this->assertNodeHasState($visitor, $node2, [self::STATE_LAST]);
    }

    public function testDeeperHierarchyShouldReturnLastThenEmptyLast(): void
    {
        $root   = new TreeNode();
        $node1  = $this->givenANodeInAparent($root);
        $node2  = $this->givenANodeInAparent($node1);

        $visitor = $this->givenAVisitor($root);

        $this->assertNodeHasState($visitor, $node1, [self::STATE_LAST]);
        $this->assertNodeHasState($visitor, $node2, [self::STATE_BLANK, self::STATE_LAST]);
    }

    public function testComplexHierarchy(): void
    {
        /*

        +- story 7
        |  `- task 5
        |     `- risk 11
        `- story6

        */
        $root    = new TreeNode();
        $story_7 = $this->givenANodeInAparent($root);
        $task_5  = $this->givenANodeInAparent($story_7);
        $risk_11 = $this->givenANodeInAparent($task_5);
        $story_6 = $this->givenANodeInAparent($root);

        $visitor = $this->givenAVisitor($root);

        $this->assertNodeHasState($visitor, $story_7, [self::STATE_NODE]);
        $this->assertNodeHasState($visitor, $task_5, [self::STATE_PIPE, self::STATE_LAST]);
        $this->assertNodeHasState($visitor, $risk_11, [self::STATE_PIPE, self::STATE_BLANK, self::STATE_LAST]);
        $this->assertNodeHasState($visitor, $story_6, [self::STATE_LAST]);
    }

    private function givenAVisitor($node): TreeNode_GetStateVisitor
    {
        $visitor = new TreeNode_GetStateVisitor();
        $node->accept($visitor);
        return $visitor;
    }

    private function givenANodeInAparent(TreeNode $parent): TreeNode
    {
        $node = new TreeNode();
        $parent->addChild($node);
        return $node;
    }

    private function assertNodeHasState($visitor, TreeNode $node, array $expected_state)
    {
        $state = $visitor->getState($node);
        //var_dump($state);
        $this->assertEquals($expected_state, $state);
    }
}
