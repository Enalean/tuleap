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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS;

use Project;

class PermissionPresenter extends BaseFrsPresenter
{
    public $permission_title;
    public $administrator_info;
    public $access_control_intro;
    public $ugroups_admin;
    public $ugroups_reader;
    public $project_id;
    public $frs_admins_submit_button;
    public $permission_read_title;
    public $under_implementation;

    public function __construct(Project $project, array $ugroups_admin, array $ugroups_reader)
    {
        $this->permission_title          = $GLOBALS['Language']->getText('file_file_utils', 'permissions_title');
        $this->frs_admins_submit_button  = $GLOBALS['Language']->getText('file_file_utils', 'frs_admins_submit_button');
        $this->administrator_info        = $GLOBALS['Language']->getText('file_file_utils', 'administrator_info');
        $this->access_control_intro      = $GLOBALS['Language']->getText('file_file_utils', 'access_control_intro');
        $this->write_title               = $GLOBALS['Language']->getText('file_file_utils', 'write_title');
        $this->permission_read_title     = $GLOBALS['Language']->getText('file_file_utils', 'permission_read_title');
        $this->ugroups_admin             = $ugroups_admin;
        $this->ugroups_reader            = $ugroups_reader;
        $this->project_id                = $project->getId();
        $this->frs_admins_form_action    = FRS_BASE_URL .'/admin/?'. http_build_query(array(
            'group_id' => $this->project_id,
            'action'   => 'admin-frs-admins'
        ));
    }
}
