<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

class FRSPermissionFactory
{
    /** @var FRSPermissionDao */
    private $permission_dao;

    public function __construct(
        FRSPermissionDao $permission_dao
    ) {
        $this->permission_dao = $permission_dao;
    }

    /**
     * @param         $permission_type
     *
     * @return FRSPermission[]
     */
    public function getFrsUGroupsByPermission(Project $project, $permission_type)
    {
        $admins = array();
        $permissions = $this->permission_dao->searchPermissionsForProjectByType($project->getID(), $permission_type);

        foreach ($permissions as $permission) {
            $admins[$permission['ugroup_id']] = $this->instantiateFromRow($permission);
        }

        return $admins;
    }

    private function instantiateFromRow(array $row)
    {
        return new FRSPermission($row['ugroup_id']);
    }
}
