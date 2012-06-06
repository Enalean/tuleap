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

require_once 'common/TreeNode/InjectSpanPaddingInTreeNodeVisitor.class.php';
require_once dirname(__FILE__).'/InjectSpanPadding.class.php';

class InjectSpanPaddingInTreeNodeVisitorTest extends InjectSpanPadding {
    
    /**
    * Return the Tree
     *
    * ROOT
    * |
    * +-Child 1 (id:6, al:8)
    * 	 |
    * 	 '-Child 2 (id:8)
    *
    */
    protected function given_AParentWithOneChildTreeNode() {
        return $this->buildBaseTree();
    }
    
    /**
     * 
     */
    public function itShouldSetDataToFirstChildThatMatches_IndentLast_leftTreeIndentMinus_treeAndChild() {
        $given = $this->given_AParentWithOneChildTreeNode();
        $this->when_VisitTreeNodeWith_InjectSpanPadding($given);
        
        $pattern = $this->getPatternSuite(" indent last-left tree indent minus-tree");
        $givenChild = $given->getChild(0);
        
        $this->then_GivenTreeNodeData_TreePadding_AssertPattern($givenChild, $pattern);
        $this->then_GivenTreeNodeData_ContentTemplate_AssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }
    
    /**
     * 
     */
    public function itShouldSetDataToSecondChildThatMatches_BlankBlankLast_LeftLast_Right() {
        $given      = $this->given_AParentWithOneChildTreeNode();
        $this->when_VisitTreeNodeWith_InjectSpanPadding($given);
        
        $pattern    = $this->getPatternSuite(" blank blank indent last-left indent last-right");
        $givenChild = $given->getChild(0)->getChild(0);
        
        $this->then_GivenTreeNodeData_TreePadding_AssertPattern($givenChild, $pattern);
    }
}
?>