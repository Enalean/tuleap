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

namespace Tuleap\FRS;

use Project;
use UGroupManager;
use ProjectUGroup;
use UserManager;

class FRSPermissionManager
{
    const FRS_ADMIN             = 'FRS_ADMIN';

    /** @var PermissionDao */
    private $permission_dao;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct(FRSPermissionDao $permission_dao, UGroupManager $ugroup_manager)
    {
        $this->permission_dao = $permission_dao;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function savePermissions(Project $project, array $ugroup_ids)
    {
        $this->permission_dao->savePermissions($project->getId(), self::FRS_ADMIN, $ugroup_ids);
    }
}
