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
use Tuleap\User\Admin\UserListSearchFieldsPresenter;
use Tuleap\User\Admin\UserListResultsPresenter;

class ProjectMembersPresenter
{

    /**
     * @var UserListResultsPresenter
     */
    public $results;

    /**
     * @var UserListSearchFieldsPresenter
     */
    public $search_fields;

    public $public_name;
    public $id;
    public $group_id;
    public $information_label;
    public $history_label;
    public $pending_label;
    public $members_label;

    public function __construct(
        Project $project,
        UserListSearchFieldsPresenter $search_fields,
        UserListResultsPresenter $results
    ) {
        $this->id            = $project->getID();
        $this->group_id      = $project->getID();
        $this->public_name   = $project->getPublicName();
        $this->is_active     = $project->isActive();
        $this->results       = $results;
        $this->search_fields = $search_fields;

        $this->information_label = $GLOBALS['Language']->getText('admin_project', 'information_label');
        $this->history_label     = $GLOBALS['Language']->getText('admin_project', 'history_label');
        $this->pending_label     = $GLOBALS['Language']->getText('admin_project', 'pending_label');
        $this->members_label     = $GLOBALS['Language']->getText('admin_project', 'members_label');

        $this->detail_button_label = $GLOBALS['Language']->getText('admin_main', 'detail_button_label');
    }
}
