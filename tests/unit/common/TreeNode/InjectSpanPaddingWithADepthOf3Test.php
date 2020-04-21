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
class InjectSpanPaddingWithADepthOf3Test extends InjectSpanPadding
{

    /**
     * Return this Tree
     *
     * ROOT
     * |
     * +-Child 1 (id: 6, al:8, 14, 16)
     * | |
     * | |-Child 2 (id:8, al:10, 12)
     * | | |
     * | | |-Child 3 (id:10)
     * | | |
     * | | '-Child 4 (id:12)
     * | |
     * | |-Child 5 (id:14)
     * | |
     * | '-Child 6 (id:16)
     * |
     * |-Child 7 (id:18)
     * |
     * '-Child 8 (id:20, al:22, 24)
     *      |
     *      |-Child 9 (id:22)
     *      |
     *   '-Child 10 (id:24)
     *
     */
    protected function given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children()
    {
        $parent  = $this->buildBaseTree();
        $child1  = $parent->getChild(0);
        $child2  = $child1->getChild(0);
        $child3  = $this->getTreeNode(10, 'Child 3');
        $child4  = $this->getTreeNode(12, 'Child 4');
        $child5  = $this->getTreeNode(14, 'Child 5');
        $child6  = $this->getTreeNode(16, 'Child 6');
        $child7  = $this->getTreeNode(18, 'Child 7');
        $child8  = $this->getTreeNode(20, 'Child 8', '22, 24');
        $child9  = $this->getTreeNode(22, 'Child 9');
        $child10 = $this->getTreeNode(24, 'Child 10');

        $this->setArtifactLinks($child1, '8, 14, 16');
        $this->setArtifactLinks($child2, '10, 12');

        $child1->addChild($child5);
        $child1->addChild($child6);

        $child2->addChild($child3);
        $child2->addChild($child4);

        $child8->addChild($child9);
        $child8->addChild($child10);

        $parent->addChild($child7);
        $parent->addChild($child8);

        return $parent;
    }
    /**
     * Child 1
     */
    public function testItShouldSetDataToChild1ThatMatchesIndentPipeTreeIndentMinusTreeAndChild(): void
    {
        $given = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern = $this->getPatternSuite(" indent pipe tree indent minus-tree");
        $givenChild = $given->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }

    /**
     * Child 2
     */
    public function testItShouldSetDataToChild2ThatMatchesIndentPipeBlankIndentPipeTreeIndentMinusTreeAndChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent pipe tree indent minus-tree");
        $givenChild = $given->getChild(0)->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }

    /**
     * Child 3
     */
    public function testItShouldSetDataToChild3ThatMatchesIndentPipeBlankIndentPipeBlankIndentPipeIndentMinusAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent pipe blank indent pipe indent minus");
        $givenChild = $given->getChild(0)->getChild(0)->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }

    /**
     * Child 4
     */
    public function testItShouldSetDataToChild4ThatMatchesIndentPipeBlankIndentPipeBlankIndentLastLeftIndentLastRightAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent pipe blank indent last-left indent last-right");
        $givenChild = $given->getChild(0)->getChild(0)->getChild(1);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }

    /**
     * Child 5
     */
    public function testItShouldSetDataToChild5ThatMatchesIndentPipeBlankIndentPipeIdentMinusAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent pipe indent minus");
        $givenChild = $given->getChild(0)->getChild(1);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }

    /**
     * Child 6
     */
    public function testItShouldSetDataToChild6ThatMatchesIndentPipeBlankIndentLastLeftIndentLastRightAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe blank indent last-left indent last-right");
        $givenChild = $given->getChild(0)->getChild(2);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }

    /**
     * Child 7
     */
    public function testItShouldSetDataToChild7ThatMatchesIndentPipeIndentMinusAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent pipe indent minus");
        $givenChild = $given->getChild(1);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }
    /**
     * Child 8
     */
    public function testItShouldSetDataToChild8ThatMatchesIndentLastLeftTreeIndentMinusTreeAndChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" indent last-left tree indent minus-tree");
        $givenChild = $given->getChild(2);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }

    /**
     * Child 9
     */
    public function testItShouldSetDataToChild9ThatMatchesBlankBlankIndentPipeIndentMinusAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" blank blank indent pipe indent minus");
        $givenChild = $given->getChild(2)->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }

    /**
     * Child 10
     */
    public function testItShouldSetDataToChild10ThatMatchesBlankBlankIndentLastLeftIdentLastRightAndNoChild(): void
    {
        $given      = $this->given3ChildrenWithTheFirstHavingAChildAndTheLastHaving2Children();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" blank blank indent last-left indent last-right");
        $givenChild = $given->getChild(2)->getChild(1);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content"));
    }
}
