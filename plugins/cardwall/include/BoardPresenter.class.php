<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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
 * A board to display
 */
abstract class Cardwall_BoardPresenter
{

    /**
     * @var Cardwall_Board
     */
    public $board;

    /**
     * @var string
     */
    public $planning_redirect_parameter;

    /**
     * @var string
     */
    public $swimline_title = '';

    /**
     * Say if the swimlines should display their header on top of them
     *
     * @var bool
     */
    public $has_swimline_header = true;

    /**
     * @param Cardwall_Board $board The board
     * @param string         $redirect_parameter the redirect paramter to add to various url
     */
    public function __construct(Cardwall_Board $board, $redirect_parameter)
    {
        $this->board                       = $board;
        $this->planning_redirect_parameter = $redirect_parameter;
    }

    /**
     *@var int
     */
    public function column_width()
    {
        return round(100 / (count($this->board->columns) + ($this->has_swimline_header ? 1 : 0)));
    }
}
