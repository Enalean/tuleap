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

    public function __construct(array $possible_events, $selected_event, $selected_subevents)
    {
        $this->event_label        = $GLOBALS['Language']->getText('project_admin_utils', 'event');
        $this->event_placeholder  = $GLOBALS['Language']->getText('project_admin_utils', 'event_placeholder');
        $this->selected_event     = $selected_event;
        $this->selected_subevents = $selected_subevents;

        $this->events = array(
            array(
                'key'        => 'any',
                'label'      => $GLOBALS["Language"]->getText('global', 'any'),
                'is_current' => 'any' === $selected_event
            )
        );
        $this->grouped_events = array();
        foreach ($possible_events as $group => $events) {
            if ($group === 'choose') {
                continue;
            }
            $this->events[] = array(
                'key'        => $group,
                'label'      => $GLOBALS['Language']->getText('project_admin_utils', $group),
                'is_current' => $selected_event === $group
            );

            $translated_events = array();
            foreach ($events as $event) {
                $translated_events[] = array(
                    'key'        => $event,
                    'label'      => $GLOBALS['Language']->getText('project_admin_utils', $event),
                    'is_current' => isset($selected_subevents[$event])
                );
            }

            $this->grouped_events[] = array(
                'group'  => $group,
                'events' => $translated_events
            );
        }
    }
}
