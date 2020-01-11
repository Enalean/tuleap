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
class InjectSpanPaddingInTreeNodeVisitorTest extends InjectSpanPadding
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
    protected function givenAParentWithOneChildTreeNode()
    {
        return $this->buildBaseTree();
    }

    public function testItShouldSetDataToFirstChildThatMatchesIndentLastLeftTreeIndentMinusTreeAndChild(): void
    {
        $given = $this->givenAParentWithOneChildTreeNode();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern = $this->getPatternSuite(" indent last-left tree indent minus-tree");
        $givenChild = $given->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
        $this->thenGivenTreeNodeDataContentTemplateAssertPattern($givenChild, $this->getPatternSuite(" content child"));
    }

    public function testItShouldSetDataToSecondChildThatMatchesBlankBlankLastLeftLastRight(): void
    {
        $given      = $this->givenAParentWithOneChildTreeNode();
        $this->whenVisitTreeNodeWithInjectSpanPadding($given);

        $pattern    = $this->getPatternSuite(" blank blank indent last-left indent last-right");
        $givenChild = $given->getChild(0)->getChild(0);

        $this->thenGivenTreeNodeDataTreePaddingAssertPattern($givenChild, $pattern);
    }
}
