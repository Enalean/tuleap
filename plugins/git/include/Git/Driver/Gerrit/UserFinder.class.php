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

require_once GIT_BASE_DIR .'/Git.class.php';

/**
 * Encapsulate the orchestration between PermissionsManager and UgroupManager
 */
class Git_Driver_Gerrit_UserFinder {

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct(PermissionsManager $permissions_manager, UGroupManager $ugroup_manager) {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    /**
     *
     * Get a unique list of user names with the given permission
     *
     * @param string $permission_type
     * @param GitRepository $repository_id
     *
     * @return array of User
     */
    public function getUsersForPermission($permission_type, GitRepository $repository){
        $ugroups_members = array();
        $group_id = $repository->getProjectId();
        foreach ($this->getUgroups($repository->getId(), $permission_type) as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getById($ugroup_id);
            if ($ugroup) {
                $ugroups_members = array_merge($ugroup->getUserLdapIds($group_id), $ugroups_members);
            }
        }
        return array_filter(array_unique($ugroups_members));
    }

    private function getUgroups($repository_id, $permission_type) {
        if ($permission_type == Git::SPECIAL_PERM_ADMIN) {
            return array(UGroup::PROJECT_ADMIN);
        }
        $ugroup_ids = $this->permissions_manager->getAuthorizedUgroups($repository_id, $permission_type);
        $result = array();
        foreach ($ugroup_ids as $row) {
            $id = $row['ugroup_id'];
            if ($this->isNeitherRegisteredNorAnonymous($id)) {
                $result[] = $id;
            }
        }
        return $result;
    }

    private function isNeitherRegisteredNorAnonymous($ugroup_id) {
        return ! in_array($ugroup_id, array(Ugroup::REGISTERED, UGroup::ANONYMOUS));
    }

}
?>
