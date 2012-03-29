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

class TreeNode_InjectSpanPaddingInTreeNodeVisitor extends TreeNode_GetStateVisitor {

    /**
     * @var boolean
     */
    protected $collapsable;

    public function __construct($collapsable = false) {
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
    protected function setChildState(TreeNode $child, $state) {
        parent::setChildState($child, $state);
        $data = $child->getData();
        $data['tree-padding']      = $this->getHtmlPaddingFromStates($child, $state);
        $data['content-template']  = '<div class="node-content">%s';
        $data['content-template'] .= $child->hasChildren() ? '<span class="node-child">&nbsp;</span></div>' : '</div>'; 
        $child->setData($data);
    }
    
    protected function getHtmlPaddingFromStates($child, $states) {
        $showTree = $this->collapsable && $child->hasChildren();
        $html     = '';
        foreach ($states as $state_id) {
            switch($state_id) {
                case self::STATE_NODE: //0
                    if ($showTree) {
                        $minus = '<span class="node-indent node-minus-tree">&nbsp;</span>';
                        $content = '<a class="node-tree">&nbsp;</a>';
                    } else {
                        $minus = '<span class="node-indent node-minus">&nbsp;</span>';
                        $content = '&nbsp;';
                    }
                    $html.= '<span class="node-indent node-pipe">'.$content.'</span>'.$minus;
                    break;
                case self::STATE_LAST: //1
                    if ($showTree) {
                        $leftContent = '<a class="node-tree">&nbsp;</a>';
                        $right       = '<span class="node-indent node-minus-tree">&nbsp;</span>';
                    } else {
                        $leftContent = '&nbsp;';
                        $right       = '<span class="node-indent node-last-right">&nbsp;</span>';
                    }
                    $html.= '<span class="node-indent node-last-left">'.$leftContent.'</span>'.$right;
                    break;
                case self::STATE_BLANK: //2
                    $html.= '<span class="node-blank">&nbsp;</span><span class="node-blank">&nbsp;</span>'
                    .'<span class="node-indent node-last-left">&nbsp;</span>'
                    .'<span class="node-indent node-last-right">&nbsp;</span>';
                    break;
                case self::STATE_PIPE: //3
                    if ($showTree) {
                        $content = '<a class="node-tree">&nbsp;</a>';
                        $right   = '<span class="node-indent node-minus-tree">&nbsp;</span>';
                    } else {
                        $content = '&nbsp;';
                        $right   = '<span class="node-blank">&nbsp;</span>';
                    }
                    $html.= '<span class="node-indent node-pipe">'.$content.'</span>'.$right;
                    break;
            }
        }
        return $html;
    }
}
?>
