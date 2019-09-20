<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
class GraphOnTrackersV5_GraphActionsPresenter
{
    public $has_user_dashboard;
    public $has_one_user_dashboard;
    public $has_project_dashboard;
    public $has_one_project_dashboard;

    /** @var GraphOnTrackersV5_Chart */
    private $chart;
    public $can_be_updated;
    public $my_dashboard_url;
    public $project_dashboard_url;
    public $delete_url;
    public $edit_url;

    /**
     * @var WidgetMyDashboardPresenter[]
     */
    public $user_dashboards;

    /**
     * @var ProjectDashboard[]
     */
    public $project_dashboards;

    public function __construct(
        GraphOnTrackersV5_Chart $chart,
        $can_be_updated,
        $my_dashboard_url,
        $project_dashboard_url,
        $delete_url,
        $edit_url,
        array $user_dashboards,
        array $project_dashboards
    ) {
        $this->chart                 = $chart;
        $this->can_be_updated        = $can_be_updated;
        $this->my_dashboard_url      = $my_dashboard_url;
        $this->project_dashboard_url = $project_dashboard_url;
        $this->delete_url            = $delete_url;
        $this->edit_url              = $edit_url;

        $this->user_dashboards           = $user_dashboards;
        $this->has_user_dashboard        = count($user_dashboards) > 0;
        $this->has_one_user_dashboard    = count($user_dashboards) === 1;
        $this->project_dashboards        = $project_dashboards;
        $this->has_project_dashboard     = count($project_dashboards) > 0;
        $this->has_one_project_dashboard = count($project_dashboards) === 1;
    }

    public function confirm_label()
    {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'confirm_del');
    }

    public function delete_title()
    {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'tooltip_del');
    }

    public function edit_title()
    {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'tooltip_edit');
    }

    public function add_to_my_dashboard_label()
    {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'add_chart_dashboard');
    }

    public function add_to_project_dashboard_label()
    {
        return $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'add_chart_project_dashboard');
    }

    public function report_is_created()
    {
        return $this->chart->getId() > 0;
    }
}
