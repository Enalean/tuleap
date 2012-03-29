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
abstract class InjectSpanPadding extends TuleapTestCase {

    /**
    * Return this Tree
    *
    * ROOT
    * |
    * +-Child 1
    * 	 |
    * 	 '-Child 2
    *
    */
    protected function buildBaseTree() {
        $parent  = new TreeNode();
        $child1Data = array(
            	'id'                => '6',
            	'last_changeset_id' => '12345',
            	'title'             => 'As a user I want to search on shared fields',
            	'artifactlinks'     => '8',
        );
    
        $child1 = new TreeNode($child1Data);
        $child1->setId($child1Data['id']);
    
        $child2Data = array(
            	'id'                => '8',
            	'last_changeset_id' => '56789',
            	'title'             => 'Add the form',
            	'artifactlinks'     => '',
        );
        $child2 = new TreeNode($child2Data);
        $child2->setId($child2Data['id']);
    
    
        $parent->addChild($child1);
        $child1->addChild($child2);
        return $parent;
    }
    
    /**
     * When visit a given tree node with an InjectSpanPadding visitor
     */
    protected function when_VisitTreeNodeWith_InjectSpanPadding( TreeNode &$givenTreeNode) {
        $visitor = new TreeNode_InjectSpanPaddingInTreeNodeVisitor(true);
        $givenTreeNode->accept($visitor);
        return $givenTreeNode;
    }

    protected function then_GivenTreeNodeData_TreePadding_AssertPattern(TreeNode $givenTreeNode, $pattern) {
        $givenData            = $givenTreeNode->getData();
        $treePaddingIsDefined = isset($givenData['tree-padding']);
        $this->assertTrue($treePaddingIsDefined);
        $this->assertPattern($pattern, $givenData['tree-padding']);
    }

    protected function then_GivenTreeNodeData_ContentTemplate_AssertPattern(TreeNode $givenTreeNode, $pattern) {
        $givenData                = $givenTreeNode->getData();
        $contentIsDefined = isset($givenData['content-template']);
        $this->assertTrue($contentIsDefined);
        $this->assertPattern($pattern, $givenData['content-template']);
    }
    
    /**
     * Build a regexp pattern from a more suitable user langage
     */    
    protected function getPatternSuite($string) {
        return str_replace(
            array(
                '_blank',
            	'_indent', 
            	'_pipe',
            	'_tree',
            	'_minusTree',
            	'_minus',
            	'_child',
            	'_lastLeft',
            	'_lastRight'
            ),
            array(
                '(node-blank)(.*)?',
            	'(node-indent)(.*)?',
            	'(node-pipe)(.*)?',
            	'(node-tree)(.*)?',
            	'(node-minus-tree)(.*)?',
            	'(node-minus)(.*)?',
            	'(node-child)(.*)?',
            	'(node-last-left)(.*)?',
            	'(node-last-right)(.*)?'
            ),
            $string
        );
    }
}
?>