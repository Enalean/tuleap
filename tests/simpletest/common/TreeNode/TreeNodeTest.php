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

class TreeNodeTest extends TuleapTestCase
{
    public function itMayWrapAnObject()
    {
        $object = mock('stdClass');
        $node   = new TreeNode();

        $node->setObject($object);
        $this->assertEqual($object, $node->getObject());
    }
}

class TreeNode_FlattenChildrenTest extends TuleapTestCase
{

    public function itReturnsAnEmptyArrayWhenNoChildren()
    {
        $node = new TreeNode();
        $this->assertIdentical(array(), $node->flattenChildren());
    }

    public function itReturnsTheChildrenWhenNoSubChildren()
    {
        $child1 = new TreeNode();
        $child2 = new TreeNode();
        $node = new TreeNode();

        $node->addChild($child1);
        $node->addChild($child2);

        $this->assertEqual(array($child1, $child2), $node->flattenChildren());
    }

    public function itReturnsTheChildrenAndSubChildrenAsAFlatList()
    {
        $child1 = new TreeNode();
        $subchild1 = new TreeNode();
        $child1->addChild($subchild1);
        $child2 = new TreeNode();
        $node = new TreeNode();

        $node->addChild($child1);
        $node->addChild($child2);
        $this->assertEqual(array($child1,  $subchild1, $child2), $node->flattenChildren());
    }
}

class TreeNode_AddChildrenTest extends TuleapTestCase
{

    public function itBuildsATreeInline()
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

        $this->assertEqual($node_1, $root->getChild(0));
        $this->assertEqual($node_1_1, $root->getChild(0)->getChild(0));
        $this->assertEqual($node_1_1_1, $root->getChild(0)->getChild(0)->getChild(0));
        $this->assertEqual($node_1_2, $root->getChild(0)->getChild(1));
        $this->assertEqual($node_2, $root->getChild(1));
    }
}

class TreeNode_SetChildrenTest extends TuleapTestCase
{

    public function itAddsTheGivenChildren()
    {
        $root       = new TreeNode();
        $children   = array(new TreeNode(), new TreeNode());
        $root->setChildren($children);
        $this->assertEqual($children, $root->getChildren());
    }

    public function itSetsTheParentNodeOfTheChildren()
    {
        $root       = new TreeNode();
        $node_1     = new TreeNode();
        $node_2     = new TreeNode();
        $children   = array($node_1, $node_2);
        $root->setChildren($children);
        $this->assertEqual($root, $node_2->getParentNode());
        $this->assertEqual($root, $node_1->getParentNode());
    }
}
