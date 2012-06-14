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

require_once 'BoardPresenter.class.php';

/**
 * A board to display in agiledashboard
 */
class Cardwall_PaneContentPresenter extends Cardwall_BoardPresenter {

    /**
     * @param Cardwall_Board  $board  The board
     * @param Cardwall_QrCode $qrcode QrCode to display. false if no qrcode (thus no typehinting)
     */
    public function __construct($swimline_title, Cardwall_Board $board, $qrcode) {
        parent::__construct($board, $qrcode);
        $this->nifty               = '';
        $this->swimline_title      = $swimline_title;
        $this->has_swimline_header = true;
    }
}
?>
