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
     * @var string
     */
    public $planning_redirect_parameter = '';

    /**
     * @param array $swimlines Array of TreeNode
     * @param array $columns   Array of Cardwall_Column
     */
    public function __construct(array $swimlines, array $columns) {
        $this->swimlines = $swimlines;
        $this->columns   = $columns;
    }

    public function hasSwimlineHeader() {
        return true;
    }
}
?>
