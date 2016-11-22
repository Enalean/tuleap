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
use Codendi_HTMLPurifier;

class SystemEventSearchPresenter
{
    public $types;
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
        $filter_status,
        array $types,
        $filter_type
    ) {
        $this->queues = array();
        foreach ($available_queues as $queue) {
            $this->queues[] = array(
                'value'   => $queue->getName(),
                'label'   => $queue->getLabel(),
                'checked' => $queue->getName() === $selected_queue_name
            );
        }

        $this->status = array(
            array(
                'label'   => $GLOBALS['Language']->getText('global', 'any'),
                'value'   => 0,
                'checked' => count($filter_status) === 0
            ),
            array(
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_NEW)),
                'value'   => SystemEvent::STATUS_NEW,
                'checked' => in_array(SystemEvent::STATUS_NEW, $filter_status)
            ),
            array(
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_RUNNING)),
                'value'   => SystemEvent::STATUS_RUNNING,
                'checked' => in_array(SystemEvent::STATUS_RUNNING, $filter_status)
            ),
            array(
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_DONE)),
                'value'   => SystemEvent::STATUS_DONE,
                'checked' => in_array(SystemEvent::STATUS_DONE, $filter_status)
            ),
            array(
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_WARNING)),
                'value'   => SystemEvent::STATUS_WARNING,
                'checked' => in_array(SystemEvent::STATUS_WARNING, $filter_status)
            ),
            array(
                'label'   => ucfirst(strtolower(SystemEvent::STATUS_ERROR)),
                'value'   => SystemEvent::STATUS_ERROR,
                'checked' => in_array(SystemEvent::STATUS_ERROR, $filter_status)
            )
        );

        $any_value_is_checked = count($filter_type) === 0 || count($filter_type) === count($types);

        $this->types = array(
            array(
                'label'   => $GLOBALS['Language']->getText('global', 'any'),
                'value'   => 0,
                'checked' => $any_value_is_checked
            )
        );

        foreach ($types as $type) {
            $this->types[] = array(
                'label'   => $type,
                'value'   => $type,
                'checked' => ! $any_value_is_checked && in_array($type, $filter_type)
            );
        }

        $this->filter_label = $GLOBALS['Language']->getText('admin_system_events', 'filter_label');
        $this->search_label = $GLOBALS['Language']->getText('admin_system_events', 'search_label');
        $this->types_label  = $GLOBALS['Language']->getText('admin_system_events', 'types_label');
        $this->status_label = $GLOBALS['Language']->getText('admin_system_events', 'all_status_label');
        $this->queue_label  = $GLOBALS['Language']->getText('admin_system_events', 'queue_label');
    }
}
