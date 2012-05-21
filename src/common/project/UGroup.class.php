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

require_once('common/dao/UGroupDao.class.php');
require_once('common/dao/UGroupUserDao.class.php');
require_once('common/dao/UserGroupDao.class.php');
require_once('common/user/User.class.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('UGroup_Invalid_Exception.class.php');

/**
 *
 * UGroup object
 * 
 */
class UGroup {
    const PROJECT_ADMIN      = 4;
    const FILE_MANAGER_ADMIN = 11;
    const WIKI_ADMIN         = 14;
    const FORUM_ADMIN        = 16;
    const NEWS_ADMIN         = 17;
    const NEWS_EDITOR        = 18;
    const SVN_ADMIN          = 19;

    protected $id;
    protected $group_id;
    protected $name;
    protected $description;
    protected $is_dynamic;

    protected $members=null;

    protected $_ugroupdao;
    protected $_ugroupuserdao;
    protected $_usergroupdao;

    public function __construct($row = null) {
        $this->id            = isset($row['ugroup_id'])            ? $row['ugroup_id']            : 0;
        $this->name          = isset($row['name'])                 ? $row['name']                 : null;
        $this->description   = isset($row['description'])          ? $row['description']          : null;
        $this->group_id      = isset($row['group_id'])             ? $row['group_id']             : 0;
        if ($this->id < 100) {
            $this->is_dynamic = true;
        } else {
            $this->is_dynamic = false;
        }
    }

    protected function _getUGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao();
        }
        return $this->_ugroupdao;
    }

    protected function _getUGroupUserDao() {
        if (!$this->_ugroupuserdao) {
            $this->_ugroupuserdao = new UGroupUserDao();
        }
        return $this->_ugroupuserdao;
    }
    
    protected function _getUserGroupDao() {
        if (!$this->_usergroupdao) {
            $this->_usergroupdao = new UserGroupDao();
        }
        return $this->_usergroupdao;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * Return array of all ugroup members as User objects
     * WARNING: this does not work currently with dynamic ugroups
     */
    public function getMembers() {
        if (!$this->members) {
            $this->members=array();
            $ugroupuser_dao =& $this->_getUGroupUserDao();
            $dar =& $ugroupuser_dao->searchUserByStaticUGroupId($this->id);
            foreach($dar as $row) {
                $this->members[]=new User($row);
            }
        }
        return $this->members;
    }

    /**
     * Return array containing the user_name of all ugroup members
     * WARNING: this does not work currently with dynamic ugroups
     */
    public function getMembersUserName() {
        $username_array=array();
        if (!$this->members) {
            $ugroupuser_dao =& $this->_getUGroupUserDao();
            $dar =& $ugroupuser_dao->searchUserByStaticUGroupId($this->id);
            foreach($dar as $row) {
                $username_array[]=$row['user_name'];
            }
        } else {
            // If ugroup members already initialized
            foreach($this->members as $user) {
                $username_array[]=$user->getUserName();
            }
        }
        return $username_array;
    }

    /**
    * Check if the ugroup exist for the given project
    *
    * @param Integer $groupId the group id
    * @param Integer $ugroupId the ugroup id
    *
    * @return boolean
    */
    function exists($groupId, $ugroupId) {
        $dao = $this->_getUGroupDao();
        return $dao->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Return project admins of given static group
     *
     * @param Integer $groupId
     * @param Array $ugroups
     *
     * @return Data Access Result
     */
    public function returnProjectAdminsByStaticUGroupId($groupId, $ugroups) {
        $dao = $this->_getUGroupUserDao();
        return $dao->returnProjectAdminsByStaticUGroupId($groupId, $ugroups);
    }
    
    /**
     * Add the given user to the group
     * 
     * This method can add to any group, either dynamic or static.
     * 
     * @param User $user
     * 
     * @throws UGroup_Invalid_Exception 
     */
    public function addUser(User $user) {
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
    
    protected function addUserToStaticGroup($group_id, $ugroup_id, $user_id) {
        ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id);
    }
    
    protected function addUserToDynamicGroup(User $user) {
        $dao  = $this->_getUserGroupDao();
        $flag = $this->getAddFlagForUGroupId($this->id);
        $dao->updateUserGroupFlags($user->getId(), $this->group_id, $flag);
    }
    
    /**
     * Convert a dynamic ugroup_id into it's DB table update to add someone to a given group
     * 
     * @param Integer $id
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
     * 
     * This method can remove from any group, either dynamic or static.
     * 
     * @param User $user
     * @throws UGroup_Invalid_Exception 
     */
    public function removeUser(User $user) {
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
    
    protected function removeUserFromStaticGroup($group_id, $ugroup_id, $user_id) {
        ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id);
    }
    
    protected function removeUserFromDynamicGroup(User $user) {
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
     * @param type $id
     * @return string
     * @throws UGroup_Invalid_Exception 
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
}
?>