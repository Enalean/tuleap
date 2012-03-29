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

class TreeNode_InjectSpanPaddingInTreeNodeVisitorTest extends TuleapTestCase {

    protected $treeNode;
    
    protected function given_AParentWithOneChildTreeNodes() {
        $artifacts = array(
        array(
                        'id'                => '6',
                        'last_changeset_id' => '12345',
                        'title'             => 'As a user I want to search on shared fields',
                        'artifactlinks'     => '8',
        ),
        array(
                        'id'                => '8',
                        'last_changeset_id' => '56789',
                        'title'             => 'Add the form',
                        'artifactlinks'     => '',
        )
        );
        
        $root  = new TreeNode();
        $node0 = new TreeNode($artifacts[0]);
        $node0->setId($artifacts[0]['id']);
        $node1 = new TreeNode($artifacts[1]);
        $node1->setId($artifacts[1]['id']);
        
        $root->addChild($node0);
        $node0->addChild($node1);
        return $root;
    }
    
    protected function when_VisitTreeNodeWith_InjectSpanPadding( TreeNode &$treeNode) {
        $visitor = new TreeNode_InjectSpanPaddingInTreeNodeVisitor(true);
        $treeNode->accept($visitor);
        return $treeNode;
    }
    
    protected function then_ParentData_TreePadding_AssertPattern(TreeNode $parent, $pattern) {
        $parentData = $parent->getData();
        $this->assertPattern($pattern, $parentData['tree-padding']);
    }
    
    protected function then_ParentData_ContentTemplate_AssertPattern(TreeNode $parent, $pattern) {
        $parentData = $parent->getData();
        $this->assertPattern($pattern, $parentData['content-template']);
    }
    
    public function itShouldSetDataThatMatchesIndentLast_leftTreeIndentMinus_treeAndChild() {
        $given = $this->given_AParentWithOneChildTreeNodes();
        $this->when_VisitTreeNodeWith_InjectSpanPadding($given);
        $pattern = '%^(.*)'.$this->getPatternSuite("_indent_lastLeft_tree_indent_minusTree").'$%ism';
        
        $this->then_ParentData_TreePadding_AssertPattern($given->getChild(0), $pattern);
        $this->then_ParentData_ContentTemplate_AssertPattern($given->getChild(0), '%^(.*)'.$this->getPatternSuite("_child").'$%ism');
    }
    
    protected function getPatternSuite($string) {
        return str_replace(
            array(
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