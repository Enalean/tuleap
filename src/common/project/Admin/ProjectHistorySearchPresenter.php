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
    public $event_label;
    public $events;

    public function __construct($event)
    {
        $this->event_label = $GLOBALS['Language']->getText('project_admin_utils', 'event');

        $this->events = array(
            array(
                'key'        => 'any',
                'label'      => $GLOBALS["Language"]->getText('global', 'any'),
                'is_current' => 'any' === $event
            ),
            array(
                'key'        => 'event_permission',
                'label'      => $GLOBALS["Language"]->getText("project_admin_utils", "event_permission"),
                'is_current' => 'event_permission' === $event
            ),
            array(
                'key'        => 'event_project',
                'label'      => $GLOBALS["Language"]->getText("project_admin_utils", "event_project"),
                'is_current' => 'event_project' === $event
            ),
            array(
                'key'        => 'event_user',
                'label'      => $GLOBALS["Language"]->getText("project_admin_utils", "event_user"),
                'is_current' => 'event_user' === $event
            ),
            array(
                'key'        => 'event_ug',
                'label'      => $GLOBALS["Language"]->getText("project_admin_utils", "event_ug"),
                'is_current' => 'event_ug' === $event
            ),
            array(
                'key'        => 'event_others',
                'label'      => $GLOBALS["Language"]->getText("project_admin_utils", "event_others"),
                'is_current' => 'event_others' === $event
            )
        );
    }
}
