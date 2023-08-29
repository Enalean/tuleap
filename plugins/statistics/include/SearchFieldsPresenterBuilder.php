<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use EventManager;
use Tuleap\Statistics\Frequencies\FrequenciesSearchFieldsPresenter;

class SearchFieldsPresenterBuilder
{
    public function buildSearchFieldsForFrequencies(
        array $type_values,
        $filter_value,
        $start_date_value,
        $end_date_value,
    ) {
        $type_options   = $this->getListOfTypeValuePresenter($type_values);
        $filter_options = $this->getListOfFilterValuePresenter($filter_value);

        return new FrequenciesSearchFieldsPresenter(
            $type_options,
            $filter_options,
            $start_date_value,
            $end_date_value
        );
    }

    public function buildSearchFieldsForServices(
        $selected_project,
        $services_with_selected,
        $group_by_date_with_selected,
        $start_date_value,
        $end_date_value,
        $relative_y_axis_value,
    ) {
        $start_date_value = $this->getDate($start_date_value, 'Y-m-d', 'P1M');
        $end_date_value   = $this->getDate($end_date_value, 'Y-m-d');

        if ($start_date_value > $end_date_value) {
            throw new StartDateGreaterThanEndDateException();
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
        $end_date_value,
    ) {
        $start_date_value = $this->getDate($start_date_value, 'Y-m-d', 'P1W');
        $end_date_value   = $this->getDate($end_date_value, 'Y-m-d');

        if ($start_date_value > $end_date_value) {
            throw new StartDateGreaterThanEndDateException();
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
        $relative_y_axis_value,
    ) {
        $params = [
            'services' => [],
        ];

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
        $end_date_value,
    ) {
        $params = [
            'services' => [],
        ];

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

    private function getListOfTypeValuePresenter(array $type_values)
    {
        $all_data = EventManager::instance()->dispatch(new FrequenciesLabels());

        $type_options = [];
        foreach ($all_data->getLabels() as $type => $label) {
            $type_options[] = $this->getValuePresenter($type, $type_values, $label);
        }

        return $type_options;
    }

    private function getListOfFilterValuePresenter($filter_value)
    {
        $all_filter = [
            'month'  => dgettext('tuleap-statistics', 'Group by month'),
            'day'    => dgettext('tuleap-statistics', 'Group by day'),
            'hour'   => dgettext('tuleap-statistics', 'Group by hour'),
            'month1' => dgettext('tuleap-statistics', 'Month'),
            'day1'   => dgettext('tuleap-statistics', 'Day'),
        ];

        $filter_options = [];

        foreach ($all_filter as $filter => $label) {
            $filter_options[] = $this->getValuePresenter($filter, [$filter_value], $label);
        }

        return $filter_options;
    }

    private function getValuePresenter($value, array $selected_values, $label)
    {
        return [
            'value'       => $value,
            'is_selected' => in_array($value, $selected_values),
            'label'       => $label,
        ];
    }

    private function getDate($date, $format, $sub_interval = null)
    {
        if (! $date) {
            $date_time = new DateTime();
            if ($sub_interval) {
                $date_time = $date_time->sub(new DateInterval($sub_interval));
            }
            $date = $date_time->format($format);
        }

        return $date;
    }
}
