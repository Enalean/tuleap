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
class TreeNodeInjectPaddingInTreeNodeVisitorTest extends TestCase
{
    public function testItInjectsPadding(): void
    {
        $root  = new TreeNode();
        $node1 = new TreeNode();
        $node2 = new TreeNode();
        $root->addChild($node1);
        $node1->addChild($node2);

        $visitor = new TreeNode_InjectPaddingInTreeNodeVisitor();
        $root->accept($visitor);

        $data = $node2->getData();
        $this->assertMatchesRegularExpression('%div class="tree-blank" >[^<]*</div><div class="tree-last"%', $data['tree-padding']);
    }
}
