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
require_once AGILEDASHBOARD_BASE_DIR .'/AgileDashboard/Pane.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';

class Cardwall_PaneContentPresenter {

    /**
     * @var array of TreeNode
     */
    public $swimlines;

    /**
     * @var array of Cardwall_Column
     */
    public $columns;

    /**
     * @var Cardwall_MappingCollection
     */
    public $mappings;

    /**
     * @var string
     */
    public $planning_redirect_parameter = '';

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
     * Say if the a qr code should be displayed next to the cardwall
     *
     * @var bool
     */
    public $qrcode = true;

    /**
     * @param array                      $swimlines Array of TreeNode
     * @param array                      $columns   Array of Cardwall_Column
     * @param Cardwall_MappingCollection $mappings  Collection of Cardwall_Mapping
     * @param Cardwall_QrCode            $qrcode    QrCode to display. false if no qrcode (thus no typehinting)
     */
    public function __construct($swimline_title, array $swimlines, array $columns, Cardwall_MappingCollection $mappings, $qrcode) {
        $this->swimline_title = $swimline_title;
        $this->swimlines      = $swimlines;
        $this->columns        = $columns;
        $this->mappings       = $mappings;
        $this->qrcode         = $qrcode;
    }
}
?>
