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

/**
 * This class crawl a TreeNode data structure and produce another TreeNode that
 * contains only Backlog items and their children.
 *
 * Given the following tree
 * .
 * `-- Epic 1
 *     `-- Story 1.1
 *         `-- Task 1.1
 *
 * And we want to filter for a Sprint plan (Story -> Sprint)
 * Then this class produce
 * .
 * `-- Story 1.1
 *     `-- Task 1.1
 */
class Planning_BacklogItemFilterVisitor {

    /**
     * @var int
     */
    private $backlog_tracker_id;

    /**
     * @var Tracker_Hierarchy
     */
    private $hierarchy;

    /**
     * @var array of artifact ids
     */
    private $already_planned_ids;

    public function __construct($backlog_tracker_id, Tracker_HierarchyFactory $hierarchy_factory, array $already_planned_ids) {
        $this->backlog_tracker_id  = $backlog_tracker_id;
        $this->hierarchy           = $hierarchy_factory->getHierarchy(array($backlog_tracker_id));
        $this->already_planned_ids = array_flip($already_planned_ids);
    }

    public function visit(TreeNode $tree_node) {
        $new_children = array();

        foreach ($tree_node->getChildren() as $child_node) {
            $child_artifact = $child_node->getData();

            if ($this->hierarchy->exists($child_artifact['tracker_id'])) {
                if ($this->isBacklogPlannableArtifact($child_artifact)) {
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

    private function isBacklogPlannableArtifact($child_artifact) {
        return ($child_artifact['tracker_id'] == $this->backlog_tracker_id
                && !isset($this->already_planned_ids[$child_artifact['id']]));
    }
}

?>
