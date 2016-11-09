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

namespace Tuleap\Admin;

use CSRFSynchronizerToken;

class ProjectPendingPresenter
{
    /**
     * @var array
     */
    public $pending_projects;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public $more_than_one_to_validate;

    public function __construct(array $pending_projects, CSRFSynchronizerToken $csrf_token)
    {
        $this->no_content               = $GLOBALS['Language']->getText('admin_approve_pending', 'no_pending');
        $this->no_content_next          = $GLOBALS['Language']->getText('admin_approve_pending', 'no_pending_next');
        $this->title                    = $GLOBALS['Language']->getText('admin_approve_pending', 'title');
        $this->go_back                  = $GLOBALS['Language']->getText('admin_approve_pending', 'go_back');
        $this->no_content               = $GLOBALS['Language']->getText('admin_approve_pending', 'no_pending');
        $this->no_content_next          = $GLOBALS['Language']->getText('admin_approve_pending', 'no_pending_next');
        $this->title                    = $GLOBALS['Language']->getText('admin_approve_pending', 'title');
        $this->description              = $GLOBALS['Language']->getText('admin_approve_pending', 'description');
        $this->see_project_details      = $GLOBALS['Language']->getText('admin_approve_pending', 'see_project_details');
        $this->description_title_label  = $GLOBALS['Language']->getText('admin_approve_pending', 'description_label');
        $this->submitted_by_label       = $GLOBALS['Language']->getText('admin_approve_pending', 'submitted_by_label');
        $this->creation_date_label      = $GLOBALS['Language']->getText('admin_approve_pending', 'creation_date_label');
        $this->delete_label             = $GLOBALS['Language']->getText('admin_approve_pending', 'delete_label');
        $this->validate_label           = $GLOBALS['Language']->getText('admin_approve_pending', 'validate_label');
        $this->validate_all_label       = $GLOBALS['Language']->getText('admin_approve_pending', 'validate_all_label');
        $this->activate_all_label       = $GLOBALS['Language']->getText('admin_approve_pending', 'activate_all_label');
        $this->label_project_visibility = $GLOBALS['Language']->getText('admin_project', 'access_label');

        $this->pending_projects          = $pending_projects;
        $this->more_than_one_to_validate = count($pending_projects['project_list']) > 1;
        $this->has_project               = count($pending_projects['project_list']) !== 0;
        $this->csrf_token                = $csrf_token;
    }
}
