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
    
    public function getUsersForWhichTheHighestPermissionIs($permission_type, $object_id){
        $ugroup_ids = $this->permissions_manager->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_type);
        $ugroup = null;
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getById($ugroup_id);
            
        }
        if ($ugroup == null) {
            return array();
        }
        return $ugroup->getMembers() ;
//        return array();
    }   
}

?>
