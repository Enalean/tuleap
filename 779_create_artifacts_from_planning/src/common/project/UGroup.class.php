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

/**
 *
 * UGroup object
 * 
 */
class UGroup {

    protected $id;
    protected $group_id;
    protected $name;
    protected $description;
    protected $is_dynamic;

    protected $members=null;

    protected $_ugroupdao;
    protected $_ugroupuserdao;

    public function __construct($row = null) {
        $this->id            = isset($row['ugroup_id'])            ? $row['ugroup_id']            : 0;
        $this->name          = isset($row['name'])                 ? $row['name']                 : null;
        $this->description   = isset($row['description'])          ? $row['description']          : null;
        $this->group_id      = isset($row['group_id'])             ? $row['group_id']             : 0;
        if ($this->id < 100) {
            $is_dynamic = true;
        } else {
            $is_dynamic = false;
        }
    }

    protected function _getUGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao(CodendiDataAccess::instance());
        }
        return $this->_ugroupdao;
    }

    protected function _getUGroupUserDao() {
        if (!$this->_ugroupuserdao) {
            $this->_ugroupuserdao = new UGroupUserDao(CodendiDataAccess::instance());
        }
        return $this->_ugroupuserdao;
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
}
?>