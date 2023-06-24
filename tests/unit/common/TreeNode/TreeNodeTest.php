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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class TreeNodeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItMayWrapAnObject(): void
    {
        $object = new stdClass();
        $node   = new TreeNode();

        $node->setObject($object);
        self::assertEquals($object, $node->getObject());
    }

    public function testItReturnsAnEmptyArrayWhenNoChildren(): void
    {
        $node = new TreeNode();
        self::assertSame([], $node->flattenChildren());
    }

    public function testItReturnsTheChildrenWhenNoSubChildren(): void
    {
        $child1 = new TreeNode();
        $child2 = new TreeNode();
        $node   = new TreeNode();

        $node->addChild($child1);
        $node->addChild($child2);

        self::assertEquals([$child1, $child2], $node->flattenChildren());
    }

    public function testItReturnsTheChildrenAndSubChildrenAsAFlatList(): void
    {
        $child1    = new TreeNode();
        $subchild1 = new TreeNode();
        $child1->addChild($subchild1);
        $child2 = new TreeNode();
        $node   = new TreeNode();

        $node->addChild($child1);
        $node->addChild($child2);
        self::assertEquals([$child1, $subchild1, $child2], $node->flattenChildren());
    }

    public function testItBuildsATreeInline(): void
    {
        $root       = new TreeNode();
        $node_1     = new TreeNode();
        $node_1_1   = new TreeNode();
        $node_1_1_1 = new TreeNode();
        $node_1_2   = new TreeNode();
        $node_2     = new TreeNode();

        $root->addChildren(
            $node_1->addChildren(
                $node_1_1->addChildren(
                    $node_1_1_1
                ),
                $node_1_2
            ),
            $node_2
        );

        self::assertEquals($node_1, $root->getChild(0));
        self::assertEquals($node_1_1, $root->getChild(0)->getChild(0));
        self::assertEquals($node_1_1_1, $root->getChild(0)->getChild(0)->getChild(0));
        self::assertEquals($node_1_2, $root->getChild(0)->getChild(1));
        self::assertEquals($node_2, $root->getChild(1));
    }

    public function testItAddsTheGivenChildren(): void
    {
        $root     = new TreeNode();
        $children = [new TreeNode(), new TreeNode()];
        $root->setChildren($children);
        self::assertEquals($children, $root->getChildren());
    }

    public function testItSetsTheParentNodeOfTheChildren(): void
    {
        $root     = new TreeNode();
        $node_1   = new TreeNode();
        $node_2   = new TreeNode();
        $children = [$node_1, $node_2];
        $root->setChildren($children);
        self::assertEquals($root, $node_2->getParentNode());
        self::assertEquals($root, $node_1->getParentNode());
    }
}
