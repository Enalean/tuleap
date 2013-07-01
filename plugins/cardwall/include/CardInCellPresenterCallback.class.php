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

require_once 'common/TreeNode/TreeNodeCallback.class.php';

/**
 * Create a Cardwall_ColumnPresnterNode given a Tracker_TreeNode_CardPresenterNode
 */
class Cardwall_CardInCellPresenterCallback implements TreeNodeCallback {

    /** @var Cardwall_CardInCellPresenterFactory */
    private $card_in_cell_presenter_factory;

    public function __construct(Cardwall_CardInCellPresenterFactory $card_in_cell_presenter_factory) {
        $this->card_in_cell_presenter_factory = $card_in_cell_presenter_factory;
    }
    
    /**
     * @see TreeNodeCallback and class comment
     */
    public function apply(TreeNode $node) {
        if (!$node instanceof Tracker_TreeNode_CardPresenterNode) {
            return clone $node;
        }
        return new Cardwall_CardInCellPresenterNode(
            $node,
            $this->card_in_cell_presenter_factory->getCardInCellPresenter($node->getCardPresenter())
        );
    }
}


?>
