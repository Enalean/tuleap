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

use Project;
use Tuleap\Layout\PaginationPresenter;

class ProjectHistoryPresenter
{
    public $public_name;
    public $id;
    public $information_label;
    public $history_label;
    public $pending_label;
    public $results;
    public $event_label;
    public $value_label;
    public $date_label;
    public $user_label;
    public $search;
    public $is_active;
    public $members_label;

    public function __construct(
        Project $project,
        ProjectHistoryResultsPresenter $results,
        $limit,
        $offset,
        ProjectHistorySearchPresenter $search
    ) {
        $this->id          = $project->getID();
        $this->public_name = $project->getPublicName();
        $this->search      = $search;
        $this->is_active   = $project->isActive();

        $this->information_label = $GLOBALS['Language']->getText('admin_project', 'information_label');
        $this->history_label     = $GLOBALS['Language']->getText('admin_project', 'history_label');
        $this->pending_label     = $GLOBALS['Language']->getText('admin_project', 'pending_label');
        $this->filter_label      = $GLOBALS['Language']->getText('global', 'search_title');
        $this->search_label      = $GLOBALS['Language']->getText('global', 'btn_search');
        $this->change_label      = $GLOBALS['Language']->getText('admin_project', 'change_label');
        $this->empty_state       = $GLOBALS['Language']->getText('admin_project', 'history_empty_state');
        $this->empty_results     = $GLOBALS['Language']->getText('admin_project', 'history_empty_results');

        $this->event_label   = $GLOBALS['Language']->getText('project_admin_utils', 'event');
        $this->value_label   = $GLOBALS['Language']->getText('project_admin_utils', 'val');
        $this->date_label    = $GLOBALS['Language']->getText('project_admin_utils', 'date');
        $this->user_label    = $GLOBALS['Language']->getText('global', 'by');
        $this->members_label = $GLOBALS['Language']->getText('admin_project', 'members_label');

        $this->history = $results->history;

        if (count($this->history) > 0) {
            $this->pagination = $this->getPagination($project, $search, $results, $limit, $offset);
        } else {
            $this->pagination = false;
        }
    }

    public function getPagination(
        Project $project,
        ProjectHistorySearchPresenter $search,
        ProjectHistoryResultsPresenter $results,
        $limit,
        $offset
    ) {
        $base_url       = '/admin/projecthistory.php';
        $default_params = array(
            'group_id' => $project->getId()
        );

        if ($search->selected_event) {
            $default_params['events_box'] = $search->selected_event;
        }

        if ($search->selected_subevents) {
            $default_params['sub_events_box'] = array_keys($search->selected_subevents);
        }

        if ($search->selected_from) {
            $default_params['start'] = $search->selected_from;
        }

        if ($search->selected_to) {
            $default_params['end'] = $search->selected_to;
        }

        if ($search->selected_by) {
            $default_params['by'] = $search->selected_by;
        }

        if ($search->selected_value) {
            $default_params['value'] = $search->selected_value;
        }

        return new PaginationPresenter(
            $limit,
            $offset,
            count($this->history),
            $results->total_rows,
            $base_url,
            $default_params
        );
    }
}
