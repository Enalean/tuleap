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

/**
 * Visit a tree node of artifacts and inject parents for top items
 *
 * Eg:
 * `+ story #123
 *  + story #456
 *  ` story #789
 *
 * becomes =>
 * `- theme #1
 *    +- epic #12
 *    |  `- story #123
 *    `- epic #13
 *       +- story #456
 *       `- story #789
 */
class Planning_GroupByParentsVisitor {

    /**
     * @var PFUser
     */
    private $user;

    public function __construct(PFUser $user) {
        $this->user = $user;
    }

    public function visit(TreeNode $node) {
        $top_parents                  = array();
        $cache_alreaydy_built_parents = array();
        foreach ($node->getChildren() as $child) {
            $top = $this->buildParentHierarchy($child, $cache_alreaydy_built_parents);
            $top_parents[$top->getId()] = $top;
        }
        $node->setChildren($top_parents);
    }

    private function buildParentHierarchy(TreeNode $node, array &$cache_alreaydy_built_parents) {
        $parent_node = $node;
        $ancestors   = $node->getObject()->getAllAncestors($this->user);
        foreach ($ancestors as $parent) {
            $previous    = $parent_node;
            $parent_node = $this->getParentNode($parent, $cache_alreaydy_built_parents);
            $parent_node->addSingularChild($previous);
        }
        return $parent_node;
    }

    private function getParentNode(Tracker_Artifact $parent, array &$cache_alreaydy_built_parents) {
        if (!isset($cache_alreaydy_built_parents[$parent->getId()])) {
            $parent_node = new TreeNode();
            $parent_node->setId($parent->getId());
            $parent_node->setObject($parent);
            $cache_alreaydy_built_parents[$parent->getId()] = $parent_node;
        }
        return $cache_alreaydy_built_parents[$parent->getId()];
    }
}
?>
