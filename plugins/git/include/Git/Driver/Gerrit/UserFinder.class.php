<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


/**
 * Encapsulate the orchestration between PermissionsManager and UgroupManager
 */
class Git_Driver_Gerrit_UserFinder
{

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct(PermissionsManager $permissions_manager)
    {
        $this->permissions_manager = $permissions_manager;
    }


    /** @return bool */
    public function areRegisteredUsersAllowedTo($permission_type, GitRepository $repository)
    {
        if ($permission_type == Git::SPECIAL_PERM_ADMIN) {
            return false;
        }
        foreach ($this->permissions_manager->getAuthorizedUgroups($repository->getId(), $permission_type) as $row) {
            if (
                $row['ugroup_id'] == ProjectUGroup::REGISTERED ||
                $row['ugroup_id'] == ProjectUGroup::ANONYMOUS ||
                $row['ugroup_id'] == ProjectUGroup::AUTHENTICATED
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the list of UGroupIds according to Git permissions that can be managed by Gerrit
     *
     * @param int $repository_id
     * @param String  $permission_type
     *
     * @return array
     */
    public function getUgroups($repository_id, $permission_type)
    {
        if ($permission_type == Git::SPECIAL_PERM_ADMIN) {
            return array(ProjectUGroup::PROJECT_ADMIN);
        }

        $ugroup_ids = $this->permissions_manager->getAuthorizedUgroups($repository_id, $permission_type, false);
        $result = array();
        foreach ($ugroup_ids as $row) {
            $result[] = $row['ugroup_id'];
        }

        return $result;
    }
}
