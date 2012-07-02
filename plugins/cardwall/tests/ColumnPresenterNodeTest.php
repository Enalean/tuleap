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

require_once dirname(__FILE__).'/../../../tests/simpletest/common/TreeNode/NodeDuplicatorContract.php';
require_once dirname(__FILE__).'/../include/ColumnPresenter.class.php';

class Cardwall_ColumnPresenterNodeTest extends NodeDuplicatorContract {

    public function itHoldsTheGivenPresenter() {
        $presenter      = mock('ColumnPresenter');
        $presenter_node = new Cardwall_ColumnPresenterNode(new TreeNode(), $presenter);
        $this->assertEqual($presenter, $presenter_node->getColumnPresenter());
    }

    protected function newNode(TreeNode $tree_node) {
        return new Cardwall_ColumnPresenterNode($tree_node, mock('ColumnPresenter'));
    }
}
?>
