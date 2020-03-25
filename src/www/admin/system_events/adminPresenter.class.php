<?php
/**
 * Copyright (c) Enalean, 2014 — 2016. All Rights Reserved.
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

class SystemEvents_adminPresenter
{

    /**
     * @var Tuleap\Layout\PaginationPresenter
     */
    public $pagination;

    /**
     * @var Tuleap\SystemEvent\SystemEventSearchPresenter
     */
    public $search;
    public $status_label;
    public $parameters_label;
    public $time_taken_label;
    public $details_label;
    public $replay_label;

    /** @var CSRFSynchronizerToken */
    public $csrf;

    /** @var array */
    public $status;

    public $types;

    public $sections;

    /** @var string */
    public $queue;
    public $title;
    public $has_events;
    public $empty_state;
    public $events_label;

    public function __construct(
        $title,
        CSRFSynchronizerToken $csrf,
        array $events,
        $queue,
        Tuleap\SystemEvent\SystemEventSearchPresenter $search,
        Tuleap\Layout\PaginationPresenter $pagination
    ) {
        $this->csrf       = $csrf;
        $this->sections   = $this->groupByCreatedDate($events);
        $this->has_events = count($events) > 0;
        $this->queue      = $queue;
        $this->title      = $title;
        $this->pagination = $pagination;
        $this->search     = $search;

        $this->events_label        = $GLOBALS['Language']->getText('admin_system_events', 'events');
        $this->notifications_label = $GLOBALS['Language']->getText('admin_system_events', 'notifications');

        $this->empty_state      = $GLOBALS['Language']->getText('admin_system_events', 'empty_state');
        $this->status_label     = $GLOBALS['Language']->getText('admin_system_events', 'status_label');
        $this->parameters_label = $GLOBALS['Language']->getText('admin_system_events', 'parameters_label');
        $this->time_taken_label = $GLOBALS['Language']->getText('admin_system_events', 'time_taken_label');
        $this->details_label    = $GLOBALS['Language']->getText('admin_system_events', 'details_label');
        $this->replay_label     = $GLOBALS['Language']->getText('admin_system_events', 'replay_label');
        $this->type_label       = $GLOBALS['Language']->getText('admin_system_events', 'type_label');
        $this->id_label         = $GLOBALS['Language']->getText('admin_system_events', 'id_label');
        $this->created_label    = $GLOBALS['Language']->getText('admin_system_events', 'created_label');
        $this->owner_label      = $GLOBALS['Language']->getText('admin_system_events', 'owner_label');
        $this->priority_label   = $GLOBALS['Language']->getText('admin_system_events', 'priority_label');
        $this->start_label      = $GLOBALS['Language']->getText('admin_system_events', 'start_label');
        $this->end_label        = $GLOBALS['Language']->getText('admin_system_events', 'end_label');
        $this->created_at       = $GLOBALS['Language']->getText('admin_system_events', 'created_at');
        $this->start_at         = $GLOBALS['Language']->getText('admin_system_events', 'start_at');
        $this->end_at           = $GLOBALS['Language']->getText('admin_system_events', 'end_at');
        $this->log_label        = $GLOBALS['Language']->getText('admin_system_events', 'log_label');
        $this->replayed_label   = $GLOBALS['Language']->getText('admin_system_events', 'replayed_label');
        $this->details_title    = $GLOBALS['Language']->getText('admin_system_events', 'details_title');
        $this->close_label      = $GLOBALS['Language']->getText('global', 'btn_close');
    }

    private function groupByCreatedDate(array $events)
    {
        $grouped_events = array();
        foreach ($events as $event) {
            \assert($event instanceof \Tuleap\SystemEvent\SystemEventPresenter);
            $prefix = substr($event->raw_create_date, 0, 10);
            if (! isset($grouped_events[$prefix])) {
                $grouped_events[$prefix] = array(
                    'label'  => $this->getSectionLabel($event->raw_create_date),
                    'events' => array()
                );
            }

            $grouped_events[$prefix]['events'][] = $event;
        }

        return array_values($grouped_events);
    }

    private function getSectionLabel($date)
    {
        return strftime(
            $GLOBALS['Language']->getText('admin_system_events', 'section_date_format'),
            strtotime($date)
        );
    }
}
