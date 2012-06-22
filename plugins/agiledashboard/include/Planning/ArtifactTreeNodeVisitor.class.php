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

require_once 'Planning.class.php';
require_once 'Item.class.php';
require_once 'ItemPresenter.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/TreeNode/CardPresenterNode.class.php';

/**
 * This visitor injects various artifact related data in a TreeNode to be used in mustache
 */
class Planning_ArtifactTreeNodeVisitor {
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var string the css class name
     */
    private $classname;
    
    public function __construct(Planning $planning, $classname) {
        $this->planning         = $planning;
        $this->classname        = $classname;
    }
    
    /**
     * @param string $classname The css classname to inject in TreeNode
     *
     * @return Planning_ArtifactTreeNodeVisitor
     */
    public static function build(Planning $planning, $classname) {
        return new Planning_ArtifactTreeNodeVisitor($planning, $classname);
    }

    /**
     * Makes a new TreeNode hierarchy identical to the given one, but changes the types
     * @param TreeNode $node
     * @return \Tracker_TreeNode_CardPresenterNode or \TreeNode
     */
    public function visit(TreeNode $node) {
        $new_node = $this->decorate($node);
        $new_node->setChildren($this->visitChildren($node));
        return $new_node;
    }
    
    /**
     * TODO something is wrong since we return different types here
     * 
     * Makes a CardPresenterNode out of $node if $node contains an artifact
     * @param TreeNode $node
     * @return \Tracker_TreeNode_CardPresenterNode or \TreeNode
     */
    private function decorate(TreeNode $node) {
        $artifact = $node->getObject();
        
        if ($artifact) {
            $planning_item = new Planning_Item($artifact, $this->planning);
            $presenter     = new Planning_ItemPresenter($planning_item, $this->classname);
            $presenter_node = Tracker_TreeNode_CardPresenterNode::build($node, $presenter);
            return $presenter_node;
        }
        return $node;
    }
    
    private function visitChildren(TreeNode $node) {
        $children = array();
        foreach ($node->getChildren() as $child) {
            $children[] = $child->accept($this);
        }
        return $children;
    }
}

?>
