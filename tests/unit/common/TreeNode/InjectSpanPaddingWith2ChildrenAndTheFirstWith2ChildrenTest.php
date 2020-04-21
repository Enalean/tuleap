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

require_once __DIR__ . '/InjectSpanPadding.class.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class InjectSpanPaddingWith2ChildrenAndTheFirstWith2ChildrenTest extends InjectSpanPadding
{

    /**
     * Return this Tree
     *
     * ROOT
     * |
     * +-Child 1 (id:6, al:8, 10)
     * | |
     * | |-Child 2 (id:8)
     * | |
     * | '-Child 3 (id:10)
     * |
     * '-Child 4 (id:12)
     */
    protected function givenTwoChildrenWithTheFirstHaving2Children()
    {
        $parent = $this->buildBaseTree();
        $child1 = $parent->getChild(0);
        $child3 = $this->getTreeNode(10, 'Child 3');

        $child1->addChild($child3);
        $this->setArtifactLinks($child1, '8, 10');

        $child4 = $this->getTreeNode(12, 'Child 4');
        $parent->addChild($child4);

        return $parent;
    }

    public function testItShouldSetDataToFirstChildThatMatchesIndentPipeTreeIndentMinusTreeAndChild(): void
    {
        $given = $this->givenTwoChildrenWithTheFirstHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern = $this->getPatternSuite(" indent pipe tree indent minus-tree");
        $givenChild = $given->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }

    public function testItShouldSetDataToChild2ThatMatchesIndentPipeBlankIndentPipeIndentMinus(): void
    {
        $given      = $this->givenTwoChildrenWithTheFirstHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent pipe indent minus");
        $givenChild = $given->getChild(0)->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
    }

    public function testItShouldSetDataToChild3ThatMatchesIndentPipeBlankIndentLastLeftIndentLastRight(): void
    {
        $given      = $this->givenTwoChildrenWithTheFirstHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent last-left indent last-right");
        $givenChild = $given->getChild(0)->getChild(1);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
    }

    public function testItShouldSetDataToChild4ThatMatchesIndentLastLeftIndentLastRight(): void
    {
        $given      = $this->givenTwoChildrenWithTheFirstHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent last-left indent last-right");
        $givenChild = $given->getChild(1);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
    }
}
