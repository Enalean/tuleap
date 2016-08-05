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
use PFUser;

class FRSPermissionManager
{
    /** @var PermissionDao */
    private $permission_dao;
    /** @var FRSPermissionFactory */
    private $permission_factory;

    public function __construct(
        FRSPermissionDao $permission_dao,
        FRSPermissionFactory $permission_factory
    ) {
        $this->permission_dao     = $permission_dao;
        $this->permission_factory = $permission_factory;
    }

    public function isAdmin(Project $project, PFUser $user)
    {
        if ($user->isAdmin($project->getId())) {
            return true;
        }

        $permissions = $this->permission_factory->getFrsUgroupsByPermission($project, FRSPermission::FRS_ADMIN);

        foreach ($permissions as $permission) {
            if ($user->isMemberOfUGroup($permission->getUGroupId(), $project->getID())) {
                return true;
            }
        }

        return false;
    }

    public function doesProjectHaveOldFrsAdminMembers(Project $project)
    {
        return $this->permission_dao->doesProjectHaveLegacyFrsAdminMembers($project->getID());
    }

    public function userCanRead(Project $project, PFUser $user)
    {
        $permissions = $this->permission_dao->searchPermissionsForProjectByType($project->getID(), FRSPermission::FRS_READER);
        foreach ($permissions as $permission) {
            if ($user->isMemberOfUGroup($permission['ugroup_id'], $project->getID())) {
                return true;
            }
        }

        return false;
    }
}
