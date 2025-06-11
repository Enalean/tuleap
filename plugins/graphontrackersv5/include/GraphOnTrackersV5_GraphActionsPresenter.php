<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5;

use Tuleap\Dashboard\Project\ProjectDashboard;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_Chart;

class GraphOnTrackersV5_GraphActionsPresenter
{
    public $has_user_dashboard;
    public $has_one_user_dashboard;
    public $has_project_dashboard;
    public $has_one_project_dashboard;

    /** @var GraphOnTrackersV5_Chart */
    private $chart;
    public $can_be_updated;
    /**
     * @var array<array{name:string, value:string}>
     */
    public readonly array $my_dashboard_form_settings;
    /**
     * @var array<array{name:string, value:string}>
     */
    public readonly array $project_dashboard_form_settings;
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

    /**
     * @param array<string,string> $my_dashboard_form_settings
     * @param array<string,string> $project_dashboard_form_settings
     */
    public function __construct(
        GraphOnTrackersV5_Chart $chart,
        $can_be_updated,
        array $my_dashboard_form_settings,
        array $project_dashboard_form_settings,
        $delete_url,
        $edit_url,
        array $user_dashboards,
        array $project_dashboards,
    ) {
        $this->chart                           = $chart;
        $this->can_be_updated                  = $can_be_updated;
        $this->my_dashboard_form_settings      = self::prepareFormSettings($my_dashboard_form_settings);
        $this->project_dashboard_form_settings = self::prepareFormSettings($project_dashboard_form_settings);
        $this->delete_url                      = $delete_url;
        $this->edit_url                        = $edit_url;

        $this->user_dashboards           = $user_dashboards;
        $this->has_user_dashboard        = count($user_dashboards) > 0;
        $this->has_one_user_dashboard    = count($user_dashboards) === 1;
        $this->project_dashboards        = $project_dashboards;
        $this->has_project_dashboard     = count($project_dashboards) > 0;
        $this->has_one_project_dashboard = count($project_dashboards) === 1;
    }

    public function confirm_label()
    {
        return dgettext('tuleap-graphontrackersv5', '\'Are you sure that you want to delete this chart ?\'');
    }

    public function delete_title()
    {
        return dgettext('tuleap-graphontrackersv5', 'Delete Chart');
    }

    public function edit_title()
    {
        return dgettext('tuleap-graphontrackersv5', 'Edit Chart');
    }

    public function add_to_my_dashboard_label()
    {
        return dgettext('tuleap-graphontrackersv5', 'Add to my dashboard');
    }

    public function add_to_project_dashboard_label()
    {
        return dgettext('tuleap-graphontrackersv5', 'Add to project dashboard');
    }

    public function report_is_created()
    {
        return $this->chart->getId() > 0;
    }

    /**
     * @psalm-pure
     * @param array<string,string> $form_settings
     * @return array<array{name:string, value:string}>
     */
    private static function prepareFormSettings(array $form_settings): array
    {
        $name_value_form_settings = [];
        foreach ($form_settings as $key => $value) {
            $name_value_form_settings[] = ['name' => $key, 'value' => $value];
        }
        return $name_value_form_settings;
    }
}
