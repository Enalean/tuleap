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

namespace Tuleap\SystemEvent;

use SystemEvent;

class SystemEventSearchPresenter
{
    public $types_by_queue;
    public $status;
    public $filter_label;
    public $search_label;
    public $types_label;
    public $status_label;
    public $queues;
    public $queue_label;

    public function __construct(
        array $available_queues,
        $selected_queue_name,
        array $selected_status,
        array $types_by_queue,
        array $selected_types
    ) {
        $this->buildQueues($available_queues, $selected_queue_name);
        $this->buildStatus($selected_status);
        $this->buildTypesByQueue($selected_queue_name, $selected_types, $types_by_queue);

        $this->filter_label = $GLOBALS['Language']->getText('global', 'search_title');
        $this->search_label = $GLOBALS['Language']->getText('global', 'btn_search');
        $this->types_label  = $GLOBALS['Language']->getText('admin_system_events', 'types_label');
        $this->status_label = $GLOBALS['Language']->getText('admin_system_events', 'all_status_label');
        $this->queue_label  = $GLOBALS['Language']->getText('admin_system_events', 'queue_label');
    }

    private function buildQueues(array $available_queues, $selected_queue_name)
    {
        $this->queues = [];
        foreach ($available_queues as $queue) {
            $this->queues[] = [
                'value'   => $queue->getName(),
                'label'   => $queue->getLabel(),
                'checked' => $queue->getName() === $selected_queue_name
            ];
        }
    }

    private function buildStatus(array $selected_status)
    {
        $this->status = [
            [
                'label'   => $GLOBALS['Language']->getText('global', 'any'),
                'value'   => 0,
                'checked' => count($selected_status) === 0
            ],
            [
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_NEW)),
                'value'   => SystemEvent::STATUS_NEW,
                'checked' => in_array(SystemEvent::STATUS_NEW, $selected_status)
            ],
            [
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_RUNNING)),
                'value'   => SystemEvent::STATUS_RUNNING,
                'checked' => in_array(SystemEvent::STATUS_RUNNING, $selected_status)
            ],
            [
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_DONE)),
                'value'   => SystemEvent::STATUS_DONE,
                'checked' => in_array(SystemEvent::STATUS_DONE, $selected_status)
            ],
            [
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_WARNING)),
                'value'   => SystemEvent::STATUS_WARNING,
                'checked' => in_array(SystemEvent::STATUS_WARNING, $selected_status)
            ],
            [
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_ERROR)),
                'value'   => SystemEvent::STATUS_ERROR,
                'checked' => in_array(SystemEvent::STATUS_ERROR, $selected_status)
            ]
        ];
    }

    private function buildTypesByQueue($selected_queue_name, array $selected_types, array $types_by_queue)
    {
        $this->types_by_queue = [];
        foreach ($types_by_queue as $queue_name => $types) {
            $is_current_queue = $queue_name === $selected_queue_name;
            $this->types_by_queue[] = [
                'queue'      => $queue_name,
                'is_current' => $is_current_queue,
                'types'      => $this->buildTypes($is_current_queue, $selected_types, $types)
            ];
        }
    }

    private function buildTypes($is_current_queue, array $selected_types, array $types)
    {
        $any_value_is_checked = ! $is_current_queue
            || count($selected_types) === 0
            || count($selected_types) === count($types);

        $types_for_the_queue = [
            [
                'label'   => $GLOBALS['Language']->getText('global', 'any'),
                'value'   => 0,
                'checked' => $any_value_is_checked
            ]
        ];

        foreach ($types as $type) {
            $types_for_the_queue[] = [
                'label'   => $type,
                'value'   => $type,
                'checked' => ! $any_value_is_checked && in_array($type, $selected_types)
            ];
        }

        return $types_for_the_queue;
    }
}
