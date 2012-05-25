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

class UGroupManager {
    
    public static $literal_user_status = array(
        User::STATUS_RESTRICTED => 'site_restricted',
        User::STATUS_ACTIVE     => 'site_active'
    );
    
    public static $literal_ugroups_templates = array(
        UGroup::REGISTERED      => '@site_active @%s_project_members',
        UGroup::PROJECT_MEMBERS => '@%s_project_members',
        UGroup::PROJECT_ADMIN   => '@%s_project_admin'
    );
    
    /**
     * @var UGroupDao
     */
    protected $dao;

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
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new UGroupDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    /**
     * Return User groups in a litteral form
     * 
     * @param string $user_name
     * 
     * @return array Ex: array('site_active', 'gpig1_project_members')
     */
    public function getLiteralUserGroupsByUserName($user_name) {
        $user = $this->getValidUserByName($user_name);
        if (!$user) {
            return array();
        }
        $groups = array(self::$literal_user_status[$user->getStatus()]);
        $groups = $this->appendDynamicUGroups($user, $groups);
        $groups = $this->appendStaticUgroups($user, $groups);
        
        return $groups;
    }

    /**
     * Append project dynamic ugroups of user
     * 
     * @param User  $user
     * @param array $user_ugroups
     *
     * @return array the new array of user's ugroup
     */
    protected function appendDynamicUGroups( User $user, array $user_ugroups = array()) {
        $user_projects = $user->getProjects(true);
        foreach ($user_projects as $user_project) {
            $project_name = strtolower($user_project['unix_group_name']);
            $group_id     = $user_project['group_id'];
            $user_ugroups[] = $project_name.'_project_members';
            if ($user->isMember($group_id, 'A')) {
                $user_ugroups[] = $project_name.'_project_admin';
            }
        }
        return $user_ugroups;
    }

    /**
     * Append project static ugroups of user
     * 
     * @param User  $user
     * @param array $user_ugroups
     * 
     * @return array the new array of user's ugroup
     */
    protected function appendStaticUgroups( User $user, array $user_ugroups = array()) {
        $ugroups = $user->getAllUgroups();
        foreach ($ugroups as $row) {
            $user_ugroups[] = 'ug_'.$row['ugroup_id'];
        }
        return $user_ugroups;
    } 

    /**
     * return an user if it's active or restricted
     * 
     * @param string $user_name
     * 
     * @return User if exists false otherwise
     */
    protected function getValidUserByName($user_name) {
        $user = UserManager::instance()->getUserByUserName($user_name);
        if ($user && isset(self::$literal_user_status[$user->getStatus()])) {
            return $user;
        }
        return false;
    }
}
?>