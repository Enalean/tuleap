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
        $selected_project,
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
            $selected_project,
            $services_with_selected,
            $this->buildUrlParamsForServices(
                $services_with_selected,
                $start_date_value,
                $end_date_value,
                $group_by_date_with_selected,
                $relative_y_axis_value
            ),
            $group_by_date_with_selected,
            $start_date_value,
            $end_date_value,
            $relative_y_axis_value
        );
    }

    public function buildSearchFieldsForProjects(
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

        return new DiskUsageProjectsSearchFieldsPresenter(
            $services_with_selected,
            $this->buildUrlParamsForProjects($services_with_selected, $start_date_value, $end_date_value),
            $start_date_value,
            $end_date_value
        );
    }

    private function buildUrlParamsForServices(
        array $service_values,
        $start_date_value,
        $end_date_value,
        $group_by_date_with_selected,
        $relative_y_axis_value
    ) {
        $params = array(
            'services' => array()
        );

        foreach ($service_values as $service) {
            if ($service['is_selected']) {
                $params['services'][] = $service['key'];
            }
        }

        $params['start_date'] = $start_date_value;
        $params['end_date']   = $end_date_value;

        if ($relative_y_axis_value) {
            $params['relative'] = $relative_y_axis_value;
        }

        $params['group_by'] = $this->getSelectedGroupByDate($group_by_date_with_selected);

        return $params;
    }

    private function buildUrlParamsForProjects(
        array $service_values,
        $start_date_value,
        $end_date_value
    ) {
        $params = array(
            'services' => array()
        );

        foreach ($service_values as $service) {
            if ($service['is_selected']) {
                $params['services'][] = $service['key'];
            }
        }

        $params['start_date'] = $start_date_value;
        $params['end_date']   = $end_date_value;

        return $params;
    }

    private function getSelectedGroupByDate(array $group_by_values)
    {
        foreach ($group_by_values as $group_by_value) {
            if ($group_by_value['is_selected']) {
                return $group_by_value['key'];
            }
        }

        return $group_by_values[0]['key'];
    }
}