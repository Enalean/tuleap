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
use Statistics_DiskUsageManager;
use Statistics_DiskUsageOutput;

class DiskUsageTopProjectsPresenterBuilder
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $usage_manager;
    /**
     * @var Statistics_DiskUsageOutput
     */
    private $usage_output;
    /**
     * @var DiskUsageSearchFieldsPresenterBuilder
     */
    private $search_fields_builder;
    /**
     * @var DiskUsageServicesPresenterBuilder
     */
    private $services_builder;

    public function __construct(
        Statistics_DiskUsageManager $usage_manager,
        Statistics_DiskUsageOutput $usage_output,
        DiskUsageSearchFieldsPresenterBuilder $search_fields_builder,
        DiskUsageServicesPresenterBuilder $services_builder
    ) {
        $this->usage_manager         = $usage_manager;
        $this->usage_output          = $usage_output;
        $this->search_fields_builder = $search_fields_builder;
        $this->services_builder      = $services_builder;
    }

    public function buildTopProjects(
        $title,
        $selected_services,
        $start_date,
        $end_date,
        $order,
        $offset,
        $limit
    ) {
        if (! $order) {
            $order = 'end_size';
        }

        if (! $offset) {
            $offset = 0;
        }

        $services_with_selected = $this->services_builder->getServiceValues($selected_services);

        $search_fields = $this->search_fields_builder->buildSearchFieldsForTopProjects(
            $services_with_selected,
            $start_date,
            $end_date
        );

        list($data_projects, $total_projects) = $this->buildDataProjects(
            $search_fields,
            $order,
            $offset,
            $limit
        );

        return new DiskUsageTopProjectsPresenter(
            $this->getHeaderPresenter($title),
            $search_fields,
            $data_projects,
            $total_projects,
            $order,
            $offset,
            $limit
        );
    }

    private function buildDataProjects(
        DiskUsageTopProjectsSearchFieldsPresenter $search_fields,
        $order,
        $offset,
        $limit
    ) {
        $services = $this->getServiceKeys($search_fields->service_values);

        list($projects, $total_projects) = $this->usage_manager->getTopProjects(
            $search_fields->start_date_value,
            $search_fields->end_date_value,
            $services,
            $order,
            $offset,
            $limit
        );

        $data_projects = array();
        $rank          = $offset;
        foreach ($projects as $value) {
            $data_project = array(
                'rank'           => $rank + 1,
                'group_id'       => $value['group_id'],
                'group_name'     => util_unconvert_htmlspecialchars($value['group_name']),
                'start_size'     => $this->usage_output->sizeReadable($value['start_size']),
                'end_size'       => $this->usage_output->sizeReadable($value['end_size']),
                'evolution'      => $this->usage_output->sizeReadable($value['evolution']),
                'evolution_rate' => $value['evolution_rate']
            );

            $data_projects[] = $data_project;
            $rank++;
        }

        return array($data_projects, $total_projects);
    }

    private function getHeaderPresenter($title)
    {
        return new AdminHeaderPresenter(
            $title,
            'disk_usage'
        );
    }

    private function getServiceKeys(array $selected_services)
    {
        $services_keys_selected = array();

        foreach ($selected_services as $service) {
            if ($service['is_selected']) {
                $services_keys_selected[] = $service['key'];
            }
        }

        return $services_keys_selected;
    }
}
