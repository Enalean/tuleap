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

use ProjectManager;
use Statistics_DiskUsageGraph;
use Statistics_DiskUsageManager;
use Statistics_DiskUsageOutput;

class DiskUsageServicesPresenterBuilder
{
    public const GROUP_BY_DAY_KEY   = 'day';
    public const GROUP_BY_WEEK_KEY  = 'week';
    public const GROUP_BY_MONTH_KEY = 'month';
    public const GROUP_BY_YEAR_KEY  = 'year';

    /**
     * @var Statistics_DiskUsageGraph
     */
    private $usage_graph;
    /**
     * @var Statistics_DiskUsageManager
     */
    private $usage_manager;
    /**
     * @var Statistics_DiskUsageOutput
     */
    private $usage_output;
    /**
     * @var SearchFieldsPresenterBuilder
     */
    private $search_fields_builder;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        ProjectManager $project_manager,
        Statistics_DiskUsageManager $usage_manager,
        Statistics_DiskUsageOutput $usage_output,
        Statistics_DiskUsageGraph $usage_graph,
        SearchFieldsPresenterBuilder $search_fields_builder
    ) {
        $this->usage_graph           = $usage_graph;
        $this->usage_manager         = $usage_manager;
        $this->usage_output          = $usage_output;
        $this->search_fields_builder = $search_fields_builder;
        $this->project_manager       = $project_manager;
    }

    public function buildServices(
        $project_id,
        $title,
        $selected_project,
        $selected_services,
        $selected_group_by_date,
        $start_date,
        $end_date,
        $relative_y_axis
    ) {
        if ($project_id) {
            $project = $this->project_manager->getProject($project_id);
            if (! $project->isError()) {
                $selected_project = $project->getPublicName() . ' (' . $project->getUnixName() . ')';
            }
        } elseif ($selected_project) {
            $project = $this->project_manager->getProjectFromAutocompleter($selected_project);
            if ($project) {
                $project_id = $project->getID();
            }
        }

        $group_by_date_with_selected = $this->getGroupByDateValues($selected_group_by_date[0]);
        $services_with_selected      = $this->getServiceValues($selected_services);

        $search_fields = $this->search_fields_builder->buildSearchFieldsForServices(
            $selected_project,
            $services_with_selected,
            $group_by_date_with_selected,
            $start_date,
            $end_date,
            $relative_y_axis
        );

        $graph_url = $this->buildGraphParam($project_id, $search_fields);

        list($services, $total_start_size, $total_end_size, $total_evolution) = $this->buildDataServices(
            $project_id,
            $search_fields
        );

        return new DiskUsageServicesPresenter(
            $this->getHeaderPresenter($title),
            $search_fields,
            $graph_url,
            $services,
            $total_start_size,
            $total_end_size,
            $total_evolution
        );
    }

    private function buildGraphParam($project_id, DiskUsageServicesSearchFieldsPresenter $search_fields)
    {
        $page   = '/plugins/statistics/disk_usage_graph.php';
        $params = $search_fields->fields_values_url;

        if ($project_id) {
            $params['group_id']   = $project_id;
            $params['graph_type'] = 'graph_project';
        } else {
            $params['graph_type'] = 'graph_service';
        }

        return $page . '?' . http_build_query($params);
    }

    private function buildDataServices($project_id, DiskUsageServicesSearchFieldsPresenter $search_fields)
    {
        $total_start_size = 0;
        $total_end_size   = 0;
        $total_evolution  = 0;

        $services = $this->getSelectedServices($search_fields->service_values);

        if ($project_id) {
            $evolution_by_service = $this->usage_manager->returnServiceEvolutionForPeriod(
                $search_fields->start_date_value,
                $search_fields->end_date_value,
                $project_id
            );
        } else {
            $evolution_by_service = $this->usage_manager->returnServiceEvolutionForPeriod(
                $search_fields->start_date_value,
                $search_fields->end_date_value
            );
        }

        foreach ($services as $key => $value) {
            $total_start_size += $evolution_by_service[$value['key']]['start_size'];
            $total_end_size   += $evolution_by_service[$value['key']]['end_size'];
            $total_evolution  += $evolution_by_service[$value['key']]['evolution'];

            $color      = $this->usage_manager->getServiceColor($value['key']);
            $color_rgb  = $this->usage_graph->applyColorModifierRGB($color);
            $color_rgba = $this->usage_graph->applyColorModifierRGBA($color);

            $value = array_merge($value, array(
                'color_rgb'  => $color_rgb,
                'color_rgba' => $color_rgba,
                'start_size' => $this->usage_output->sizeReadable($evolution_by_service[$value['key']]['start_size']),
                'end_size'   => $this->usage_output->sizeReadable($evolution_by_service[$value['key']]['end_size']),
                'evolution'  => $this->usage_output->sizeReadable($evolution_by_service[$value['key']]['evolution'])
            ));

            $services[$key] = $value;
        }

        $total_start_size = $this->usage_output->sizeReadable($total_start_size);
        $total_end_size   = $this->usage_output->sizeReadable($total_end_size);
        $total_evolution  = $this->usage_output->sizeReadable($total_evolution);

        return array(
            $services,
            $total_start_size,
            $total_end_size,
            $total_evolution
        );
    }

    private function getGroupByDateKeys()
    {
        return array(
            self::GROUP_BY_DAY_KEY   => $GLOBALS['Language']->getText('plugin_statistics', 'day'),
            self::GROUP_BY_WEEK_KEY  => $GLOBALS['Language']->getText('plugin_statistics', 'week'),
            self::GROUP_BY_MONTH_KEY => $GLOBALS['Language']->getText('plugin_statistics', 'month'),
            self::GROUP_BY_YEAR_KEY  => $GLOBALS['Language']->getText('plugin_statistics', 'year')
        );
    }

    private function getGroupByDateValues($selected_group_by_date)
    {
        $options                     = $this->getGroupByDateKeys();
        $group_by_date_with_selected = array();

        if (! $selected_group_by_date) {
            $selected_group_by_date = self::GROUP_BY_WEEK_KEY;
        }

        foreach ($options as $key => $value) {
            $selected = $key === $selected_group_by_date;

            $group_by_date_with_selected[] = array(
                'key'         => $key,
                'value'       => $value,
                'is_selected' => $selected
            );
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

    public function getServiceValues($selected_services)
    {
        $services_with_selected = array();

        foreach ($this->usage_manager->getProjectServices() as $key => $value) {
            $service = array('key' => $key, 'value' => $value);

            if (! $selected_services) {
                $service['is_selected'] = true;
            } else {
                $service['is_selected'] = in_array($service['key'], $selected_services);
            }
            $services_with_selected[] = $service;
        }

        return $services_with_selected;
    }

    private function getHeaderPresenter($title)
    {
        return new AdminHeaderPresenter(
            $title,
            'disk_usage'
        );
    }
}
