<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Statistics;

use DateInterval;
use DateTime;

class DiskUsageSearchFieldsPresenterBuilder
{
    public function buildSearchFieldsForServices(
        $services_with_selected,
        $group_by_date_with_selected,
        $start_date_value,
        $end_date_value,
        $relative_y_axis_value
    ) {
        if (! $start_date_value) {
            $start_date = new DateTime();
            $start_date_value = $start_date->sub(new DateInterval('P5W'))->format('Y-m-d');
        }

        if (! $end_date_value) {
            $end_date = new DateTime();
            $end_date_value = $end_date->format('Y-m-d');
        }

        return new DiskUsageServicesSearchFieldsPresenter(
            $services_with_selected,
            $this->buildUrlParam('disk_usage_graph.php', '', $services_with_selected, $start_date_value, $end_date_value),
            $group_by_date_with_selected,
            $start_date_value,
            $end_date_value,
            $relative_y_axis_value
        );
    }

    public function buildSearchFieldsForTopProjects(
        $services_with_selected,
        $start_date_value,
        $end_date_value
    ) {
        if (! $start_date_value) {
            $start_date = new DateTime();
            $start_date_value = $start_date->sub(new DateInterval('P1W'))->format('Y-m-d');
        }

        if (! $end_date_value) {
            $end_date = new DateTime();
            $end_date_value = $end_date->format('Y-m-d');
        }

        return new DiskUsageTopProjectsSearchFieldsPresenter(
            $services_with_selected,
            $this->buildUrlParam('disk_usage.php', 'top_projects', $services_with_selected, $start_date_value, $end_date_value),
            $start_date_value,
            $end_date_value
        );
    }

    protected function buildUrlParam($page, $menu, array $service_values, $start_date_value, $end_date_value)
    {
        $url_param = $page;
        $params    = array(
            'services' => array()
        );

        if ($menu) {
            $params['menu'] = $menu;
        }

        foreach ($service_values as $service) {
            if ($service['is_selected']) {
                $params['services'][] = $service['key'];
            }
        }

        $params['start_date'] = $start_date_value;
        $params['end_date']   = $end_date_value;

        return $url_param.'?'.http_build_query($params);
    }
}