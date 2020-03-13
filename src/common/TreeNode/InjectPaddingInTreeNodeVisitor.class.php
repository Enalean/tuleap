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

require_once 'GetStateVisitor.class.php';

class TreeNode_InjectPaddingInTreeNodeVisitor extends TreeNode_GetStateVisitor
{

    private static $state_classes = array(
        TreeNode_GetStateVisitor::STATE_BLANK => 'tree-blank',
        TreeNode_GetStateVisitor::STATE_NODE  => 'tree-node',
        TreeNode_GetStateVisitor::STATE_PIPE  => 'tree-pipe',
        TreeNode_GetStateVisitor::STATE_LAST  => 'tree-last',
    );

    /**
     * @var bool
     */
    private $collapsable;

    public function __construct($collapsable = false)
    {
        $this->collapsable = $collapsable;
    }

    protected function setChildState(TreeNode $child, $state)
    {
        parent::setChildState($child, $state);
        $data = $child->getData();
        $data['tree-padding'] = $this->convertStateToDivs($child, $state);
        $child->setData($data);
    }

    private function convertStateToDivs(TreeNode $node, $state)
    {
        $html = '';
        $template = '<div class="%s" %s>&nbsp;</div>';
        foreach ($state as $state_id) {
            $id    = '';
            $class = self::$state_classes[$state_id];
            if ($this->collapsable && $node->hasChildren() && ($state_id == self::STATE_LAST || $state_id == self::STATE_NODE)) {
                $class .= ' tree-collapsable';
                $id = 'id="tree-node-' . $node->getId() . '"';
            }
            $html .= sprintf($template, $class, $id);
        }
        return $html;
    }
}
