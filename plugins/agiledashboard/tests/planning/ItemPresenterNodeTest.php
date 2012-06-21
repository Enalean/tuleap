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

require_once 'tests/simpletest/common/include/builders/aTreeNode.php';
require_once dirname(__FILE__).'/../../include/Planning/ItemPresenterNode.class.php';
require_once dirname(__FILE__).'/../../include/Planning/ItemPresenter.class.php';

class Planning_ItemPresenterNodeTest extends TuleapTestCase {
    
    public function itCopiesAllPropertiesOfTheGivenNode() {
        $tree_node      = aNode()->withId(3)
                                 ->withArtifact(mock('Tracker_Artifact'))
                                 ->withChildren(aNode(), aNode())
                                 ->withObject(mock('Tracker_Artifact'))
                                 ->build();
        
        $presenter_node = Planning_ItemPresenterNode::build($tree_node, mock('Planning_ItemPresenter'));
        $this->assertEqual($tree_node->getId(), $presenter_node->getId());
        $this->assertIdentical($tree_node->getData(), $presenter_node->getData());
        $this->assertIdentical($tree_node->getChildren(), $presenter_node->getChildren());
        $this->assertIdentical($tree_node->getObject(), $presenter_node->getObject());
    }
}
?>
