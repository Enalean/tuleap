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
/**
 * Extends GetStateVisitor to display a table as a tree
 * with expand/collapse and indentation.
 *
 */
class TreeNode_InjectSpanPaddingInTreeNodeVisitor extends TreeNode_GetStateVisitor
{

    /**
     * @var bool
     */
    protected $collapsable;

    protected $showTreeTpl = array(
        self::STATE_NODE => '
        	<span class="node-indent node-pipe"><a class="node-tree">&nbsp;</a></span>
        	<span class="node-indent node-minus-tree">&nbsp;</span>',
        self::STATE_LAST => '
        	<span class="node-indent node-last-left"><a class="node-tree">&nbsp;</a></span>
        	<span class="node-indent node-minus-tree">&nbsp;</span>',
        self::STATE_BLANK => '
        	<span class="node-blank">&nbsp;</span>
        	<span class="node-blank">&nbsp;</span>',
        self::STATE_PIPE => '
        	<span class="node-indent node-pipe"><a class="node-tree">&nbsp;</a></span>
        	<span class="node-indent node-minus-tree">&nbsp;</span>'
    );

    protected $showNormalTpl = array(
        self::STATE_NODE => '
        	<span class="node-indent node-pipe">&nbsp;</span>
        	<span class="node-indent node-minus">&nbsp;</span>',
        self::STATE_LAST => '
        	<span class="node-indent node-last-left">&nbsp;</span>
        	<span class="node-indent node-last-right">&nbsp;</span>',
        self::STATE_BLANK => '
        	<span class="node-blank">&nbsp;</span>
        	<span class="node-blank">&nbsp;</span>',
        self::STATE_PIPE => '
        	<span class="node-indent node-pipe">&nbsp;</span>
        	<span class="node-blank">&nbsp;</span>'
    );

    public function __construct($collapsable = false)
    {
        $this->collapsable = $collapsable;
    }

    /**
     * Set states of a TreeNodein it's data
     *
     * @var TreeNode $child the TreeNode to set
     * @var array    $state states of spaces
     *
     * @see TreeNode_GetStateVisitor::setChildState()
     */
    protected function setChildState(TreeNode $child, $state)
    {
        parent::setChildState($child, $state);
        $data = $child->getData();
        $data['tree-padding']      = $this->getHtmlPaddingFromStates($child, $state);
        $data['content-template']  = '<div class="node-content">%s';
        $data['content-template'] .= $child->hasChildren() ? '<span class="node-child">&nbsp;</span></div>' : '</div>';
        $child->setData($data);
    }

    protected function getHtmlPaddingFromStates($child, $states)
    {
        $showTree  = $this->collapsable && $child->hasChildren();
        $html      = '';
        $curIndex  = 0;
        $lastIndex = count($states) - 1;
        foreach ($states as $state_id) {
            $isLastState = $curIndex == $lastIndex;
            $html .= $this->getPaddingForAState($state_id, $showTree && $isLastState);
            $curIndex++;
        }
        return $html;
    }

    protected function getPaddingForAState($state, $showTree)
    {
        if ($showTree) {
            return $this->showTreeTpl[$state];
        } else {
            return $this->showNormalTpl[$state];
        }
    }
}
