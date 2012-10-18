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
     * @param string $permission_type
     * @param int    $object_id
     * 
     * @return array of User
     */
    public function getUsersForPermission($permission_type, $object_id){
        $ugroups_members = array();
        foreach ($this->getUgroups($object_id, $permission_type) as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getById($ugroup_id);
            if ($ugroup) {
                $ugroups_members = array_merge($ugroup->getMembers(), $ugroups_members);
            }
        }

        return $this->uniqueUsers($ugroups_members);
    }

    private function getUgroups($object_id, $permission_type) {
        $ugroup_ids = $this->permissions_manager->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_type);
        return array_filter($ugroup_ids, array($this, 'notTooBigGroup'));
    }

    private function notTooBigGroup($ugroup_id) {
        return ! in_array($ugroup_id, array(Ugroup::REGISTERED, UGroup::ANONYMOUS));
    }

    private function uniqueUsers($ugroups_members) {
        $ret = array();
        foreach ($ugroups_members as $member) {
            $ret[$member->getId()] = $member;
        }
        return array_values($ret);

    }
}

?>
