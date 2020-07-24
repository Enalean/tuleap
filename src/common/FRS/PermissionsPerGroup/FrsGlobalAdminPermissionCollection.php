<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS\PermissionsPerGroup;

class FrsGlobalAdminPermissionCollection
{
    /**
     * @var array
     */
    private $permissions;

    public function __construct()
    {
        $this->permissions = [];
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        $unique_permissions = array_values($this->permissions);

        return $unique_permissions;
    }

    /**
     * @param array $permission
     */
    public function addPermission($id, array $permission)
    {
        $this->permissions[$id] = $permission;
    }
}
