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
require_once 'Project.class.php';
require_once 'common/dao/UGroupDao.class.php';
require_once 'common/dao/UGroupUserDao.class.php';

class UGroupManager {
    
    /**
     * @var UGroupDao
     */
    private $dao;

    public function __construct(UGroupDao $dao = null) {
        $this->dao = $dao;
    }

    /**
     * @return UGroup of the given project or null if not found
     */
    public function getUGroup(Project $project, $ugroup_id) {
        $project_id = $project->getID();
        if ($ugroup_id <= 100) {
            $project_id = 100;
        }

        $row = $this->getDao()->searchByGroupIdAndUGroupId($project_id, $ugroup_id)->getRow();
        if ($row) {
            return new UGroup($row);
        }
    }

    public function getUGroups(Project $project, array $exclude = array()) {
        $ugroups = array();
        foreach ($this->getDao()->searchDynamicAndStaticByGroupId($project->getId()) as $row) {
            if (in_array($row['ugroup_id'], $exclude)) {
                continue;
            }
            $ugroups[] = new UGroup($row);
        }
        return $ugroups;
    }

    public function getUGroupByName(Project $project, $name) {
        $row = $this->getDao()->searchByGroupIdAndName($project->getID(), $name)->getRow();
        if (!$row && preg_match('/^ugroup_.*_key$/', $name)) {
            $row = $this->getDao()->searchByGroupIdAndName(100, $name)->getRow();
        }
        if ($row) {
            return new UGroup($row);
        }
        return null;
    }

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
    public function getDao() {
        if (!$this->dao) {
            $this->dao = new UGroupDao();
        }
        return $this->dao;
    }

    /**
     * Wrapper for EventManager
     *
     * @return EventManager
     */
    private function getEventManager() {
        return EventManager::instance();
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
        if ($ugroupId <= 100) {
            $dao = new UGroupUserDao();
            return $dao->searchUserByDynamicUGroupId($ugroupId, $groupId);
        }
    }

    /**
     * Check if update users is allowed for a given user group
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return boolean
     */
    public function isUpdateUsersAllowed($ugroupId) {
        $ugroupUpdateUsersAllowed = true;
        $this->getEventManager()->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));
        return $ugroupUpdateUsersAllowed;
    }

    /**
     * Wrapper for dao method that checks if the user group is valid
     *
     * @param Integer $groupId  Id of the project
     * @param Integer $ugroupId Id of the user goup
     *
     * @return boolean
     */
    public function checkUGroupValidityByGroupId($groupId, $ugroupId) {
        return $this->getDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Wrapper for dao method that retrieves all Ugroups bound to a given Ugroup
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return DataAccessResult
     */
    public function searchUGroupByBindingSource($ugroupId) {
        return $this->getDao()->searchUGroupByBindingSource($ugroupId);
    }

    /**
     * Wrapper for dao method that updates binding option for a given UGroup
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return Boolean
     */
    public function updateUgroupBinding($ugroupId, $sourceId = null) {
        return $this->getDao()->updateUgroupBinding($ugroupId, $sourceId);
    }

    /**
     * Wrapper to retrieve the source user group from a given bound ugroup id
     *
     * @param Integer $ugroupId The source ugroup id
     *
     * @return DataAccessResult
     */
    public function getUgroupBindingSource($ugroupId) {
        return $this->getDao()->getUgroupBindingSource($ugroupId);
    }

    /**
     * Wrapper for UserGroupDao
     *
     * @return UserGroupDao
     */
    public function getUserGroupDao() {
        return new UserGroupDao();
    }

    /**
     * Return name and id of all ugroups belonging to a specific project
     *
     * @param Integer $groupId    Id of the project
     * @param Array   $predefined List of predefined ugroup id
     *
     * @return DataAccessResult
     */
    public function getExistingUgroups($groupId, $predefined = null) {
        $dar = $this->getUserGroupDao()->getExistingUgroups($groupId, $predefined);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return array();
    }

}

?>