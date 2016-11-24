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
use Statistics_DiskUsageGraph;
use Statistics_DiskUsageManager;
use Statistics_DiskUsageOutput;

class DiskUsagePresenterBuilder
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $statistics_disk_usage_manager;
    /**
     * @var Statistics_DiskUsageOutput
     */
    private $statistics_disk_usage_output;
    /**
     * @var Statistics_DiskUsageGraph
     */
    private $statistics_disk_usage_graph;

    public function __construct(
        Statistics_DiskUsageManager $statistics_disk_usage_manager,
        Statistics_DiskUsageOutput $statistics_disk_usage_output,
        Statistics_DiskUsageGraph $statistics_disk_usage_graph
    ) {
        $this->statistics_disk_usage_manager = $statistics_disk_usage_manager;
        $this->statistics_disk_usage_output  = $statistics_disk_usage_output;
        $this->statistics_disk_usage_graph   = $statistics_disk_usage_graph;
    }

    public function buildServices(
        $title,
        $group_id,
        $selected_services,
        $selected_group_by_date,
        $start_date,
        $end_date,
        $relative_y_axis
    ) {
        $header_presenter = new AdminHeaderPresenter(
            $title,
            'disk_usage'
        );

        $search_field = $this->buildSearchField(
            $selected_services,
            $selected_group_by_date[0],
            $start_date,
            $end_date,
            $relative_y_axis
        );

        $graph_url = $this->buildGraphParam(
            $search_field
        );

        $data_services = $this->buildDataServices(
            $group_id,
            $search_field->service_values,
            $search_field->start_date_value,
            $search_field->end_date_value
        );

        return new DiskUsageServicesPresenter(
            $header_presenter,
            $search_field,
            $graph_url,
            $data_services['services'],
            $data_services['total_start_size'],
            $data_services['total_end_size'],
            $data_services['total_evolution']
        );
    }

    private function buildGraphParam(DiskUsageSearchFieldsPresenter $search_field)
    {
        $url_param = 'disk_usage_graph.php?';
        $params    = array();
        foreach ($search_field->service_values as $service) {
            if ($service['is_selected']) {
                $params[] .= 'services[]=' . $service['key'];
            }
        }

        $url_param .= implode('&', $params);

        if ($search_field->relative_y_axis_value) {
            $url_param .= '&relative='.$search_field->relative_y_axis_value;
        }

        $url_param .= '&start_date='.$search_field->start_date_value.'&end_date='.$search_field->end_date_value;
        $url_param .= '&group_by='.$this->getSelectedGroupByDate($search_field->group_by_values);
        $url_param .= '&graph_type=graph_service';

        return $url_param;
    }

    private function buildSearchField(
        $selected_services,
        $selected_group_by_date,
        $start_date_value,
        $end_date_value,
        $relative_y_axis_value
    ) {
        $services = array();
        foreach ($this->statistics_disk_usage_manager->getProjectServices() as $key => $value) {
            $services[] = array('key' => $key, 'value' => $value);
        }

        $group_by_date_with_selected = $this->getGroupByDateValues($selected_group_by_date);
        $services_with_selected      = $this->getServiceValues($services, $selected_services);

        if (! $start_date_value) {
            $start_date = new DateTime();
            $start_date_value = $start_date->sub(new DateInterval('P5W'))->format('Y-m-d');
        }

        if (! $end_date_value) {
            $end_date = new DateTime();
            $end_date_value = $end_date->format('Y-m-d');
        }

        return new DiskUsageSearchFieldsPresenter(
            $services_with_selected,
            $group_by_date_with_selected,
            $start_date_value,
            $end_date_value,
            $relative_y_axis_value
        );
    }

    private function buildDataServices($group_id, array $services, $start_date, $end_date)
    {
        $total_start_size = 0;
        $total_end_size   = 0;
        $total_evolution  = 0;

        $services = $this->getSelectedServices($services);

        if ($group_id) {
            $evolution_by_service = $this->statistics_disk_usage_manager->returnServiceEvolutionForPeriod($start_date, $end_date, $group_id);
        } else {
            $evolution_by_service = $this->statistics_disk_usage_manager->returnServiceEvolutionForPeriod($start_date, $end_date);
        }

        foreach ($services as $key => $value) {
            $total_start_size += $evolution_by_service[$value['key']]['start_size'];
            $total_end_size   += $evolution_by_service[$value['key']]['end_size'];
            $total_evolution  += $evolution_by_service[$value['key']]['evolution'];

            $color      = $this->statistics_disk_usage_manager->getServiceColor($value['key']);
            $color_rgb  = $this->statistics_disk_usage_graph->applyColorModifierRGB($color);
            $color_rgba = $this->statistics_disk_usage_graph->applyColorModifierRGBA($color);

            $value = array_merge($value, array(
                'color_rgb'  => $color_rgb,
                'color_rgba' => $color_rgba,
                'start_size' => $this->statistics_disk_usage_output->sizeReadable($evolution_by_service[$value['key']]['start_size']),
                'end_size'   => $this->statistics_disk_usage_output->sizeReadable($evolution_by_service[$value['key']]['end_size']),
                'evolution'  => $this->statistics_disk_usage_output->sizeReadable($evolution_by_service[$value['key']]['evolution'])
            ));

            $services[$key] = $value;
        }

        $total_start_size = $this->statistics_disk_usage_output->sizeReadable($total_start_size);
        $total_end_size   = $this->statistics_disk_usage_output->sizeReadable($total_end_size);
        $total_evolution  = $this->statistics_disk_usage_output->sizeReadable($total_evolution);

        return array(
            'services'         => $services,
            'total_start_size' => $total_start_size,
            'total_end_size'   => $total_end_size,
            'total_evolution'  => $total_evolution
        );
    }

    private function getGroupByDateKeys()
    {
        return array(
            'day'   => $GLOBALS['Language']->getText('plugin_statistics', 'day'),
            'week'  => $GLOBALS['Language']->getText('plugin_statistics', 'week'),
            'month' => $GLOBALS['Language']->getText('plugin_statistics', 'month'),
            'year'  => $GLOBALS['Language']->getText('plugin_statistics', 'year')
        );
    }

    private function getServiceValues(array $services, $selected_services)
    {
        $services_with_selected = array();

        if (! $selected_services) {
            foreach ($services as $service) {
                $service['is_selected']   = true;
                $services_with_selected[] = $service;
            }
        } else {
            foreach ($services as $service) {
                $service['is_selected']   = in_array($service['key'], $selected_services);
                $services_with_selected[] = $service;
            }
        }

        return $services_with_selected;
    }

    private function getGroupByDateValues($selected_group_by_date)
    {
        $options = $this->getGroupByDateKeys();

        $group_by_date_with_selected = array();

        foreach ($options as $key => $value) {
            $is_selected = $key === $selected_group_by_date;

            $group_by_date_with_selected[] = array(
                'key'         => $key,
                'value'       => $value,
                'is_selected' => $is_selected
            );
        }

        if (! $selected_group_by_date) {
            $group_by_date_with_selected[0]['is_selected'] = true;
        }

        return $group_by_date_with_selected;
    }

    private function getSelectedServices(array $services)
    {
        $selected_services = array();

        foreach ($services as $service) {
            if ($service['is_selected']) {
                $selected_services[] = $service;
            }
        }

        return $selected_services;
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
