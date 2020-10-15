<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_TreeNode_CardPresenterNodeTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItCopiesAllPropertiesOfTheGivenNode(): void
    {
        $node_1 = new TreeNode([], 1);
        $node_2 = new TreeNode([], 2);

        $data['artifact'] = Mockery::mock(Artifact::class);
        $tree_node  = new TreeNode($data, 3);
        $tree_node->setChildren([$node_1, $node_2]);
        $tree_node->setObject($data['artifact']);


        $presenter_node = $this->newNode($tree_node);
        $this->assertEquals($presenter_node->getId(), $tree_node->getId());
        $this->assertSame($presenter_node->getData(), $tree_node->getData());
        $this->assertEquals($presenter_node->getChildren(), $tree_node->getChildren());
        $this->assertEquals($presenter_node->getObject(), $tree_node->getObject());
    }

    public function testItHoldsTheGivenPresenter(): void
    {
        $presenter      = Mockery::mock(Tracker_CardPresenter::class);
        $presenter_node = new Tracker_TreeNode_CardPresenterNode(new TreeNode(), $presenter);
        $this->assertEquals($presenter_node->getCardPresenter(), $presenter);
    }

    private function newNode(TreeNode $tree_node): Tracker_TreeNode_CardPresenterNode
    {
        return new Tracker_TreeNode_CardPresenterNode($tree_node, Mockery::mock(Tracker_CardPresenter::class));
    }
}
