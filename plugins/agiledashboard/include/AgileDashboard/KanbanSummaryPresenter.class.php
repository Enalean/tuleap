<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_Presenter_KanbanSummaryPresenter {

    /** @var AgileDashboard_Kanban */
    private $kanban;

    public function __construct(AgileDashboard_Kanban $kanban) {
        $this->kanban = $kanban;
    }

    public function name() {
        return $this->kanban->getName();
    }

    public function tracker_id() {
        return $this->kanban->getTrackerId();
    }

    public function count_open_kanban_items() {
        return 0;
    }

    public function count_closed_kanban_items() {
        return 0;
    }

    public function open() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','open');
    }

    public function closed() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','closed');
    }

    public function cardwall() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cardwall');
    }
}