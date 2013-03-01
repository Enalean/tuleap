<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/dao/UGroupDao.class.php';
require_once 'common/dao/UGroupUserDao.class.php';
require_once 'common/dao/UserGroupDao.class.php';
require_once 'common/user/User.class.php';
require_once 'www/project/admin/ugroup_utils.php';
require_once 'UGroup_Invalid_Exception.class.php';
require_once 'common/user/Users.class.php';

/**
 * UGroup object
 */
class UGroup {

    const NONE               = 100;
    const ANONYMOUS          = 1;
    const REGISTERED         = 2;
    const PROJECT_MEMBERS    = 3;
    const PROJECT_ADMIN      = 4;
    const FILE_MANAGER_ADMIN = 11;
    const DOCUMENT_TECH      = 12;
    const DOCUMENT_ADMIN     = 13;
    const WIKI_ADMIN         = 14;
    const TRACKER_ADMIN      = 15;
    const FORUM_ADMIN        = 16;
    const NEWS_ADMIN         = 17;
    const NEWS_EDITOR        = 18;
    const SVN_ADMIN          = 19;

    protected $id    = 0;
    protected $group_id     = 0;
    protected $name         = null;
    protected $description  = null;
    protected $is_dynamic   = true;

    protected $members      = null;
    protected $members_name = null;

    protected $_ugroupdao;
    protected $_ugroupuserdao;
    protected $_usergroupdao;

    /**
     * Constructor of the class
     *
     * @param Array $row ugroup row
     *
     * @return Void
     */
    public function __construct($row = null) {
        $this->id          = isset($row['ugroup_id'])   ? $row['ugroup_id']   : 0;
        $this->name        = isset($row['name'])        ? $row['name']        : null;
        $this->description = isset($row['description']) ? $row['description'] : null;
        $this->group_id    = isset($row['group_id'])    ? $row['group_id']    : 0;
        $this->is_dynamic  = $this->id < 100;
    }

    /**
     * Get instance of UGroupDao
     *
     * @return UGroupDao
     */
    protected function getUGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao();
        }
        return $this->_ugroupdao;
    }

    /**
     * Get instance of UGroupUserDao
     *
     * @return UGroupUserDao
     */
    protected function getUGroupUserDao() {
        if (!$this->_ugroupuserdao) {
            $this->_ugroupuserdao = new UGroupUserDao();
        }
        return $this->_ugroupuserdao;
    }
    
    public function setUGroupUserDao(UGroupUserDao $dao) {
        $this->_ugroupuserdao = $dao;
    }

    /**
     * Get instance of UserGroupDao
     *
     * @return UserGroupDao
     */
    protected function _getUserGroupDao() {
        if (!$this->_usergroupdao) {
            $this->_usergroupdao = new UserGroupDao();
        }
        return $this->_usergroupdao;
    }

    /**
     * Get the ugroup name
     *
     * @return String
     */
    public function getName() {
        return $this->name;
    }

    public function getTranslatedName() {
        return util_translate_name_ugroup($this->getName());
    }

    /**
     * Get the ugroup id
     *
     * @return Integer
     */
    public function getId() {
        return $this->id;
    }

    public function getProjectId() {
        return $this->group_id;
    }

    public function getDescription() {
        return $this->description;
    }

    /**
     * Return array of users members of the ugroup
     *
     * @return User[]
     */
    public function getMembers() {
        if (! $this->members) {
            $this->members = $this->getStaticOrDynamicMembers($this->group_id);
        }
        return $this->members;
    }

    /**
     *  
     * @param int $group_id the group id of the static or dynamic group.
     * You have to supply this argument as $this->group_id is 100 in the case of a dynamic group
     *
     * @return Users
     */
    public function getUsers($group_id) {
        return new Users($this->getStaticOrDynamicMembers($group_id));
    }
    
    /**
     *  
     * @param int $group_id the group id of the static or dynamic group.
     * You have to supply this argument as $this->group_id is 100 in the case of a dynamic group
     *
     * @return Array of string
     */
    public function getUserNames($group_id) {
        return $this->getUsers($group_id)->getNames();
    }

    public function getUserLdapIds($group_id) {
        return $this->getUsers($group_id)->getLdapIds();
    }

    /**
     * Return array containing the user_name of all ugroup members
     *
     * @return Array
     */
    public function getMembersUserName() {
        $names = array();
        foreach ($this->getMembers() as $member) {
            $names[] = $member->getUserName();
        }
        return $names;
    }

    private function getStaticOrDynamicMembers($group_id) {
        if ($this->is_dynamic) {
            $dar = $this->getUGroupUserDao()->searchUserByDynamicUGroupId($this->id, $group_id);
            return $dar->instanciateWith(array($this, 'newUserFromIncompleteRow'));
        }
        $dar = $this->getUGroupUserDao()->searchUserByStaticUGroupId($this->id);
        return $dar->instanciateWith(array($this, 'newUser'));
    }

    public function newUser($row) {
        return new PFUser($row);
    }

    public function newUserFromIncompleteRow($row) {
        return UserManager::instance()->getUserById($row['user_id']);
    }

    /**
    * Check if the ugroup exist for the given project
    *
    * @param Integer $groupId  The group id
    * @param Integer $ugroupId The ugroup id
    *
    * @return Boolean
    */
    public function exists($groupId, $ugroupId) {
        return $this->getUGroupDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Return project admins of given static group
     *
     * @param Integer $groupId Id of the project
     * @param Array   $ugroups list of ugroups
     *
     * @return DataAccessResult
     */
    public function returnProjectAdminsByStaticUGroupId($groupId, $ugroups) {
        return $this->getUGroupUserDao()->returnProjectAdminsByStaticUGroupId($groupId, $ugroups);
    }

    /**
     * Add the given user to the group
     * This method can add to any group, either dynamic or static.
     *
     * @param PFUser $user User to add
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return Void
     */
    public function addUser(PFUser $user) {
        $this->assertProjectUGroupAndUserValidity($user);
        if ($this->is_dynamic) {
            $this->addUserToDynamicGroup($user);
        } else {
            if ($this->exists($this->group_id, $this->id)) {
                $this->addUserToStaticGroup($this->group_id, $this->id, $user->getId());
            } else {
                throw new UGroup_Invalid_Exception();
            }
        }
    }

    /**
     * Test the status of the ugroup & the user
     *
     * @param PFUser $user User to test
     *
     * @return Void
     */
    private function assertProjectUGroupAndUserValidity($user) {
        if (!$this->group_id) {
            throw new Exception('Invalid group_id');
        }
        if (!$this->id) {
            throw new UGroup_Invalid_Exception();
        }
        if ($user->isAnonymous()) {
            throw new Exception('Invalid user');
        }
    }

    /**
     * Add user to a static ugroup
     *
     * @param Integer $group_id  Id of the project
     * @param Integer $ugroup_id Id of the ugroup
     * @param Integer $user_id   Id of the user
     *
     * @return Void
     */
    protected function addUserToStaticGroup($group_id, $ugroup_id, $user_id) {
        ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id);
    }

    /**
     * Add user to a dynamic ugroup
     *
     * @param PFUser $user User to add
     *
     * @return Void
     */
    protected function addUserToDynamicGroup(PFUser $user) {
        $dao  = $this->_getUserGroupDao();
        $flag = $this->getAddFlagForUGroupId($this->id);
        $dao->updateUserGroupFlags($user->getId(), $this->group_id, $flag);
    }

    /**
     * Convert a dynamic ugroup_id into it's DB table update to add someone to a given group
     *
     * @param Integer $id Id of the ugroup
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return String
     */
    public function getAddFlagForUGroupId($id) {
        switch ($id) {
            case self::PROJECT_ADMIN:
                return "admin_flags = 'A'";
            case self::FILE_MANAGER_ADMIN:
                return 'file_flags = 2';
            case self::WIKI_ADMIN:
                return 'wiki_flags = 2';
            case self::SVN_ADMIN:
                return 'svn_flags = 2';
            case self::FORUM_ADMIN:
                return 'forum_flags = 2';
            case self::NEWS_ADMIN:
                return 'news_flags = 2';
            case self::NEWS_EDITOR:
                 return 'news_flags = 1';
            default:
                throw new UGroup_Invalid_Exception();
        }
    }

    /**
     * Remove given user from user group
     * This method can remove from any group, either dynamic or static.
     *
     * @param PFUser $user
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return Void
     */
    public function removeUser(PFUser $user) {
        $this->assertProjectUGroupAndUserValidity($user);
        if ($this->is_dynamic) {
            $this->removeUserFromDynamicGroup($user);
        } else {
            if ($this->exists($this->group_id, $this->id)) {
                $this->removeUserFromStaticGroup($this->group_id, $this->id, $user->getId());
            } else {
                throw new UGroup_Invalid_Exception();
            }
        }
    }

    /**
     * Remove user from static ugroup
     *
     * @param Integer $group_id  Id of the project
     * @param Integer $ugroup_id Id of the ugroup
     * @param Integer $user_id   Id of the user
     *
     * @return Void
     */
    protected function removeUserFromStaticGroup($group_id, $ugroup_id, $user_id) {
        ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id);
    }

    /**
     * Remove user from dynamic ugroup
     *
     * @param PFUser $user User to remove
     *
     * @return Boolean
     */
    protected function removeUserFromDynamicGroup(PFUser $user) {
        $dao  = $this->_getUserGroupDao();
        if ($this->id == self::PROJECT_ADMIN && $dao->returnProjectAdminsByGroupId($this->group_id)->rowCount() <= 1) {
            throw new Exception('Impossible to remove last admin of the project');
        }
        $flag = $this->getRemoveFlagForUGroupId($this->id);
        return $dao->updateUserGroupFlags($user->getId(), $this->group_id, $flag);
    }

    /**
     * Convert a dynamic ugroup_id into it's DB table update to remove someone from given group
     *
     * @param Integer $id Id of the ugroup
     *
     * @throws UGroup_Invalid_Exception
     *
     * @return String
     */
    public function getRemoveFlagForUGroupId($id) {
        switch ($id) {
            case self::PROJECT_ADMIN:
                return "admin_flags = ''";
            case self::FILE_MANAGER_ADMIN:
                return 'file_flags = 0';
            case self::WIKI_ADMIN:
                return 'wiki_flags = 0';
            case self::SVN_ADMIN:
                return 'svn_flags = 0';
            case self::FORUM_ADMIN:
                return 'forum_flags = 0';
            case self::NEWS_ADMIN:
            case self::NEWS_EDITOR:
                 return 'news_flags = 0';
            default:
                throw new UGroup_Invalid_Exception();
        }
    }

    /**
     * Check if the user group is bound
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return Boolean
     */
    public function isBound() {
        $dar = $this->getUGroupDao()->getUgroupBindingSource($this->id);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return  true;
        } else {
            return false;
        }
    }
}
?>