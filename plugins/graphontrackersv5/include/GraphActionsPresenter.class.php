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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class GraphOnTrackersV5_GraphActionsPresenter {

    /** @var GraphOnTrackersV5_Chart */
    private $chart;
    public $can_be_updated;
    public $my_dashboard_url;
    public $project_dashboard_url;
    public $delete_url;
    public $edit_url;

    public function __construct(
        GraphOnTrackersV5_Chart $chart,
        $can_be_updated,
        $my_dashboard_url,
        $project_dashboard_url,
        $delete_url,
        $edit_url
    ) {
        $this->chart                 = $chart;
        $this->can_be_updated        = $can_be_updated;
        $this->my_dashboard_url      = $my_dashboard_url;
        $this->project_dashboard_url = $project_dashboard_url;
        $this->delete_url            = $delete_url;
        $this->edit_url              = $edit_url;
    }

    public function confirm_label() {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','confirm_del');
    }

    public function delete_title() {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'tooltip_del');
    }

    public function edit_title() {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'tooltip_edit');
    }

    public function add_to_my_dashboard_label() {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'add_chart_dashboard');
    }

    public function add_to_project_dashboard_label() {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'add_chart_project_dashboard');
    }

    public function report_is_created() {
        return $this->chart->getId() > 0;
    }
}