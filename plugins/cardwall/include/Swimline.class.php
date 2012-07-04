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

/**
 * A swimline in the dashboard
 */
class Cardwall_Swimline {

    public $cardincell_presenter;

    /**
     * @var array
     */
    public $cells = array();

    /**
     * @var int
     */
    public $swimline_id;
    
    /**
     * @param string $title
     * @param array  $cells
     */
    public function __construct(TreeNode $node, array $cells) {
        if ($node instanceof Cardwall_CardInCellPresenterNode) {
            $this->cardincell_presenter = $node->getCardInCellPresenter();
        }
        $this->cells       = $cells;
        $this->swimline_id = $node->getId();
    }
    
    public function getCardPresenter() {
        return $this->cardincell_presenter->getCardPresenter();
    }
}
?>
