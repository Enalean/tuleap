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
require_once __DIR__.'/../bootstrap.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aTreeNode.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/TreeNode/NodeDuplicatorContractTest.class.php';

class Tracker_TreeNode_CardPresenterNodeTest extends NodeDuplicatorContractTest {
    
    public function itHoldsTheGivenPresenter() {
        $presenter      = mock('Tracker_CardPresenter');
        $presenter_node = new Tracker_TreeNode_CardPresenterNode(new TreeNode(), $presenter);
        $this->assertEqual($presenter, $presenter_node->getCardPresenter());
    }

    protected function newNode(TreeNode $tree_node) {
        return new Tracker_TreeNode_CardPresenterNode($tree_node, mock('Tracker_CardPresenter'));
    }
}

?>
