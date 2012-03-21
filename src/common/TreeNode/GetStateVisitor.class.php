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

require_once 'TreeNode.class.php';

class TreeNode_GetStateVisitor {
    
    const STATE_NODE  = 0;
    const STATE_LAST  = 1;
    const STATE_BLANK = 2;
    const STATE_PIPE  = 3;
    
    private $states = array();
    
    public function getState(TreeNode $node) {
        return $this->states[$node->getId()];
    }
    
    private function setState(TreeNode $node, $prefix) {
        $children    = $node->getChildren();
        $nb_children = count($children);
        $i = 0;
        $children_prefix = $this->getDefaultChildrenPrefix($prefix);
        $child_state     = $this->getDefaultState($prefix);
        foreach ($children as $child) {
            $child_id = $child->getId();
            if ($this->isLastChildren($i, $nb_children)) {
                $children_prefix = $this->getChildrenPrefixForLastChild($prefix);
                $child_state     = $this->getStateWhenChildIsTheLastOne($prefix);
            }
            $this->setChildState($child, $child_state);
            $child->accept($this, $children_prefix);
            $i++;
        }
    }

    protected function setChildState(TreeNode $child, $state) {
        $this->states[$child->getId()] = $state;
    }

    private function getDefaultChildrenPrefix($prefix) {
        return array_merge($prefix, array(self::STATE_PIPE));
    }
    
    private function getChildrenPrefixForLastChild($prefix) {
        return array_merge($prefix, array(self::STATE_BLANK));
    }
    
    private function getDefaultState($prefix) {
        return array_merge($prefix, array(self::STATE_NODE));
    }
    
    private function getStateWhenChildIsTheLastOne($prefix) {
        return array_merge($prefix, array(self::STATE_LAST));
    }
    
    private function isLastChildren($i, $nb_children) {
        return $i == $nb_children - 1;
    }
    
    public function visit(TreeNode $node, $prefix = null) {
        if (!$prefix) {
            $prefix = array();
        }
        $this->setState($node, $prefix);
    }
}
?>
