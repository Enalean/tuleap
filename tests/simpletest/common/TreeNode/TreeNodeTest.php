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

class TreeNode_FlattenChildrenTest extends TuleapTestCase {
    
    public function itReturnsAnEmptyArrayWhenNoChildren() {
        $node = new TreeNode();
        $this->assertIdentical(array(), $node->flattenChildren());
    }
    
    public function itReturnsTheChildrenWhenNoSubChildren() {
        $child1 = new TreeNode();
        $child2 = new TreeNode();
        $node = new TreeNode();
        
        $node->addChild($child1);
        $node->addChild($child2);
        
        $this->assertEqual(array($child1, $child2), $node->flattenChildren());
    }

    public function itReturnsTheChildrenAndSubChildrenAsAFlatList() {
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
?>
