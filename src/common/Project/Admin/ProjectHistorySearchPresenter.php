<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Project\Admin;

class ProjectHistorySearchPresenter
{
    public $events;
    public $event_label;
    public $event_placeholder;
    public $grouped_events;
    public $selected_event;
    public $selected_subevents;
    public $selected_from;
    public $selected_to;
    public $from_label;
    public $to_label;
    public $selected_by;
    public $by_label;
    public $selected_value;
    public $value_label;
    public $user_has_searched_for_something;

    public function __construct(
        array $possible_events,
        $selected_event,
        $selected_subevents,
        $selected_value,
        $selected_from,
        $selected_to,
        $selected_by
    ) {
        $this->buildDatesBox($selected_from, $selected_to);
        $this->buildEventsBox($possible_events, $selected_event, $selected_subevents);
        $this->buildUserBox($selected_by);
        $this->buildValueBox($selected_value);

        $this->user_has_searched_for_something = $selected_from
            || $selected_to
            || $selected_event
            || $selected_subevents
            || $selected_subevents
            || $selected_by
            || $selected_value;
    }

    private function buildValueBox($selected_value)
    {
        $this->selected_value = $selected_value;

        $this->value_label = $GLOBALS['Language']->getText('project_admin_utils', 'val');
    }

    private function buildUserBox($selected_by)
    {
        $this->selected_by = $selected_by;

        $this->by_label = $GLOBALS['Language']->getText('global', 'by');
    }

    private function buildDatesBox($selected_from, $selected_to)
    {
        $this->selected_from = $selected_from ? $selected_from : '';
        $this->selected_to   = $selected_to ? $selected_to : '';

        $this->from_label = $GLOBALS['Language']->getText('project_admin_utils', 'from');
        $this->to_label   = $GLOBALS['Language']->getText('project_admin_utils', 'to');
    }

    private function buildEventsBox(array $possible_events, $selected_event, $selected_subevents)
    {
        $this->event_label        = $GLOBALS['Language']->getText('project_admin_utils', 'event');
        $this->event_placeholder  = $GLOBALS['Language']->getText('project_admin_utils', 'event_placeholder');
        $this->selected_event     = $selected_event;
        $this->selected_subevents = $selected_subevents;

        $this->events = [
            [
                'key'        => 'any',
                'label'      => $GLOBALS["Language"]->getText('global', 'any'),
                'is_current' => 'any' === $selected_event
            ]
        ];
        $this->grouped_events = [];
        foreach ($possible_events as $group => $events) {
            if ($group === 'choose') {
                continue;
            }
            $this->events[] = [
                'key'        => $group,
                'label'      => $GLOBALS['Language']->getOverridableText('project_admin_utils', $group),
                'is_current' => $selected_event === $group
            ];

            $translated_events = [];
            foreach ($events as $event) {
                $translated_events[] = [
                    'key'        => $event,
                    'label'      => $GLOBALS['Language']->getOverridableText('project_admin_utils', $event),
                    'is_current' => isset($selected_subevents[$event])
                ];
            }

            $this->grouped_events[] = [
                'group'  => $group,
                'events' => $translated_events
            ];
        }
    }
}
