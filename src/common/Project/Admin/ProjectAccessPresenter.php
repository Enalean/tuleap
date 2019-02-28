<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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

namespace Tuleap\Project;

use ForgeConfig;
use Project;

class ProjectAccessPresenter
{

    public $incl_restricted_label;
    public $is_public_incl_restricted;
    public $is_wide_open;
    public $is_open;
    public $is_closed;
    public $access;

    public function __construct($project_access)
    {
        if (ForgeConfig::areRestrictedUsersAllowed()) {
            $this->is_public_incl_restricted = $project_access === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->is_wide_open              = $project_access === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->is_open                   = $project_access === Project::ACCESS_PUBLIC;
            $this->is_closed                 = $project_access === Project::ACCESS_PRIVATE;
        } else {
            $this->is_public_incl_restricted = false;
            $this->is_wide_open              = $project_access === Project::ACCESS_PUBLIC;
            $this->is_open                   = false;
            $this->is_closed                 = $project_access === Project::ACCESS_PRIVATE;
        }

        if ($project_access === Project::ACCESS_PRIVATE) {
            $this->access = $GLOBALS['Language']->getText('admin_project', 'private_label');
        } else {
            $this->access = $GLOBALS['Language']->getText('admin_project', 'public_label');
        }

        $this->incl_restricted_label = $GLOBALS['Language']->getText('admin_project', 'incl_restricted_label');
    }
}
