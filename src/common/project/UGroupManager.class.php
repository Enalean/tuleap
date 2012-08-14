<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'UGroup.class.php';
require_once 'common/dao/UGroupDao.class.php';
require_once 'common/dao/UGroupUserDao.class.php';

class UGroupManager {
    
    /**
     * @var UGroupDao
     */
    private $dao;

    /**
     * Return all UGroups the user belongs to
     *
     * @param User $user The user
     *
     * @return DataAccessResult
     */
    public function getByUserId($user) {
        return $this->getDao()->searchByUserId($user->getId());
    }

    /**
     * Returns a UGroup from its Id
     *
     * @param Integer $ugroupId The UserGroupId
     * 
     * @return UGroup
     */
    public function getById($ugroupId) {
        $dar = $this->getDao()->searchByUGroupId($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return new UGroup($dar->getRow());
        } else {
            return new UGroup();
        }
    }

    /**
     * Wrapper for UGroupDao
     *
     * @return UGroupDao
     */
    private function getDao() {
        if (!$this->dao) {
            $this->dao = new UGroupDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    /**
     * Get Dynamic ugroups members
     *
     * @param Integer $ugroupId Id of the uGroup
     * @param Integer $groupId  Id of the project
     *
     * @return DataAccessResult
     */
    public function getDynamicUGroupsMembers($ugroupId, $groupId) {
        if($ugroupId <= 100) {
            $dao = new UGroupUserDao(CodendiDataAccess::instance());
            return $dao->searchUserByDynamicUGroupId($ugroupId, $groupId);
        }
    }

}

?>