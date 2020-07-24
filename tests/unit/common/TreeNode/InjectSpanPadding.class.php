<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
abstract class InjectSpanPadding extends TestCase
{

    /**
    * Return the Tree
    *
    * ROOT
    * |
    * +-Child 1 (id:6, al:8)
    *      |
    *      '-Child 2 (id:8)
    *
    */
    protected function buildBaseTree(): TreeNode
    {
        $parent = new TreeNode();
        $child1 = $this->getTreeNode(6, 'Child 1', '8');
        $child2 = $this->getTreeNode(8, 'Child 2');
        $parent->addChild($child1);
        $child1->addChild($child2);
        return $parent;
    }

    /**
     * When visit a given tree node with an InjectSpanPadding visitor
     */
    protected function whenVisitTreeNodeWithInjectSpanPadding(TreeNode &$givenTreeNode)
    {
        $visitor = new TreeNode_InjectSpanPaddingInTreeNodeVisitor(true);
        $givenTreeNode->accept($visitor);
        return $givenTreeNode;
    }

    protected function assertMatchesEqualDataAtIndex($index, TreeNode $givenTreeNode, $expected)
    {
        $givenData            = $givenTreeNode->getData();
        $treePaddingIsDefined = isset($givenData[$index]);
        $this->assertTrue($treePaddingIsDefined);
        preg_match_all('%node-[a-z\-]+%sm', $givenData[$index], $actual);
        $this->assertEquals($expected, $actual[0]);
    }

    protected function thenGivenTreeNodeDataTreePaddingAssertPattern(TreeNode $givenTreeNode, $expected)
    {
        $this->assertMatchesEqualDataAtIndex('tree-padding', $givenTreeNode, $expected);
    }

    protected function thenGivenTreeNodeDataContentTemplateAssertPattern(TreeNode $givenTreeNode, $expected)
    {
        $this->assertMatchesEqualDataAtIndex('content-template', $givenTreeNode, $expected);
    }

    /**
     * Build a regexp pattern from a more suitable user langage
     */
    protected function getPatternSuite($string): array
    {
        $string = str_replace(' ', ' node-', $string);
        return explode(' ', trim($string));
    }

    protected function getTreeNode($id, $title, $artifactLinks = ''): TreeNode
    {
        if (is_array($artifactLinks)) {
            $artifactLinks = implode(', ', $artifactLinks);
        }
        $nodeData = [
            'id'            => $id,
            'title'         => $title,
            'artifactlinks' => $artifactLinks,
        ];

        $node = new TreeNode($nodeData);
        $node->setId($id);
        return $node;
    }

    protected function setArtifactLinks(TreeNode $node, $artifactLinks): TreeNode
    {
        if (is_array($artifactLinks)) {
            $artifactLinks = implode(', ', $artifactLinks);
        }
        $nodeData = $node->getData();
        $nodeData['artifactlinks'] = $artifactLinks;
        $node->setData($nodeData);
        return $node;
    }
}
