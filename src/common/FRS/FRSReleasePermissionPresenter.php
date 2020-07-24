<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\FRS;

use Project;

class FRSReleasePermissionPresenter
{
    public $user_groups;
    public $permission_not_defined;
    public $package_permission_information;

    public function __construct(Project $project, array $user_groups, $permission_type)
    {
        $this->user_groups            = $user_groups;
        $this->permission_information = $GLOBALS['Language']->getText(
            'project_admin_permissions',
            'admins_create_modify_ug',
            [
                "/project/admin/ugroup.php?group_id=" . urlencode($project->getGroupId())
            ]
        );
    }
}
