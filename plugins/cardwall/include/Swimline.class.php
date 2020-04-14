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
class Cardwall_Swimline
{

    /**
     * @var Cardwall_CardInCellPresenter
     */
    private $card_in_cell_presenter;

    /**
     * @var array
     */
    public $cells = array();

    /**
     * @var int
     */
    public $swimline_id;

    /**
     * @var bool
     */
    public $is_no_matching_column = false;

    /**
     * @param string $title
     * @param array  $cells
     */
    public function __construct(Cardwall_CardInCellPresenter $swimline_artifact_presenter, array $cells)
    {
        $this->cells                  = $cells;
        $this->card_in_cell_presenter = $swimline_artifact_presenter;
        $this->swimline_id            = $swimline_artifact_presenter->getId();
    }

    /**
     * @return Tracker_CardPresenter|null
     */
    public function getCardPresenter()
    {
        return $this->card_in_cell_presenter->getCardPresenter();
    }

    /**
     *
     * @return Cardwall_CardInCellPresenter
     */
    public function getCardInCellPresenter()
    {
        return $this->card_in_cell_presenter;
    }

    public function stack_cards_title()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'cell_stack');
    }

    public function expand_cards_title()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'cell_unstack');
    }

    public function getCells()
    {
        return $this->cells;
    }
}
