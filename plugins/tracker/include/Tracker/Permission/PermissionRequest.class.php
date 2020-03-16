<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Permission_PermissionRequest
{
    private $permissions = array();

    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    public function setFromRequest(Codendi_Request $request, array $ugroup_ids)
    {
        foreach ($ugroup_ids as $id) {
            $this->permissions[$id] = $request->get(Tracker_Permission_Command::PERMISSION_PREFIX . $id);
        }
    }

    public function containsPermissionType($permission_type)
    {
        return in_array($permission_type, $this->permissions);
    }

    public function getPermissionType($id)
    {
        if (isset($this->permissions[$id])) {
            return $this->permissions[$id];
        }
        return null;
    }

    public function revoke($id)
    {
        if (isset($this->permissions[$id])) {
            unset($this->permissions[$id]);
        }
    }
}
