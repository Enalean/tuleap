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

require_once TRACKER_BASE_DIR .'/Tracker/Hierarchy/HierarchyFactory.class.php';

class Planning_BacklogItemFilterVisitor {

    /**
     * @var int
     */
    private $backlog_tracker_id;

    /**
     * @var Tracker_Hierarchy
     */
    private $hierarchy;

    public function __construct($backlog_tracker_id, Tracker_HierarchyFactory $hierarchy_factory) {
        $this->backlog_tracker_id = $backlog_tracker_id;
        $this->hierarchy          = $hierarchy_factory->getHierarchy(array($backlog_tracker_id));
    }

    public function visit(TreeNode $tree_node) {
        $new_children = array();
        
        foreach ($tree_node->getChildren() as $child_node) {
            $child_artifact = $child_node->getData();

            if ($this->hierarchy->exists($child_artifact['tracker_id'])) {
                if ($child_artifact['tracker_id'] == $this->backlog_tracker_id) {
                    $new_children[] = $child_node;
                } else {
                    $new_subchildren = $child_node->accept($this)->getChildren();
                    $new_children    = array_merge($new_children, $new_subchildren);
                }
            }
        }

        $new_tree_node = new TreeNode();
        $new_tree_node->setChildren($new_children);

        return $new_tree_node;
    }
}

?>
