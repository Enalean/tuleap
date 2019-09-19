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

require_once __DIR__ . '/InjectSpanPadding.class.php';

class InjectSpanPaddingWith2ChildrenAndTheFirstWithOneChildTest extends InjectSpanPadding
{

    /**
     * Return the Tree
     *
     * ROOT
     * |
     * +-Child 1 (id:6, al:8)
     * | |
     * | '-Child 2 (id:8)
     * |
     * '-Child 3 (id:10)
     */
    protected function given_TwoChildrenWithTheFirstHavingAChild()
    {
        $parent = $this->buildBaseTree();
        $child3 = $this->getTreeNode(10, 'Child 3');

        $parent->addChild($child3);

        return $parent;
    }

    public function itShouldSetDataToChild1ThatMatches_IndentPipeTreeIndentMinus_treeAndChild()
    {
        $given = $this->given_TwoChildrenWithTheFirstHavingAChild();
        $this->when_VisitTreeNodeWith_InjectSpanPadding($given);

        $pattern = $this->getPatternSuite(" indent pipe tree indent minus-tree");
        $givenChild = $given->getChild(0);

        $this->then_GivenTreeNodeData_TreePadding_AssertPattern($givenChild, $pattern);
        $this->then_GivenTreeNodeData_ContentTemplate_AssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }

    public function itShouldSetDataToChild2ThatMatches_IndentPipeBlankIndentLastLeftIndentLastRight()
    {
        $given      = $this->given_TwoChildrenWithTheFirstHavingAChild();
        $this->when_VisitTreeNodeWith_InjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent last-left indent last-right");
        $givenChild = $given->getChild(0)->getChild(0);

        $this->then_GivenTreeNodeData_TreePadding_AssertPattern($givenChild, $pattern);
    }

    public function itShouldSetDataToChild3ThatMatches_Last_LeftLast_Right()
    {
        $given      = $this->given_TwoChildrenWithTheFirstHavingAChild();
        $this->when_VisitTreeNodeWith_InjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent last-left indent last-right");
        $givenChild = $given->getChild(1);

        $this->then_GivenTreeNodeData_TreePadding_AssertPattern($givenChild, $pattern);
    }
}
