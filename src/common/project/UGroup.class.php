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
    
    protected $ugroup_id   = 0;
    protected $group_id    = 0;
    protected $name        = null;
    protected $description = null;
    protected $is_dynamic  = true;

    protected $members     = null;
    protected $members_name= null;

    protected $_ugroupdao;
    protected $_ugroupuserdao;

    public function __construct($row = null) {
        $properties_in_row = array_intersect(array_keys($row), array('ugroup_id', 'name', 'description', 'group_id'));
        foreach ($properties_in_row as $property) {
            $this->$property = $row[$property];
        }
        $this->is_dynamic = $this->ugroup_id < 100;
    }

    protected function UGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao(CodendiDataAccess::instance());
        }
        return $this->_ugroupdao;
    }

    protected function UGroupUserDao() {
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
        if ($this->members) {
            return $this->members;
        }
        $this->members = array();
        $dar           = $this->UGroupUserDao()->searchUserByStaticUGroupId($this->ugroup_id);
        foreach($dar as $row) {
            $currentUser        = new User($row);
            $this->members[]    = $currentUser;
            $this->members_name = $currentUser->getUserName();
        }
        return $this->members;
    }

    /**
     * Return array containing the user_name of all ugroup members
     * WARNING: this does not work currently with dynamic ugroups
     */
    public function getMembersUserName() {
        $this->getMembers();
        return $this->members_name;
    }

    /**
    * Check if the ugroup exist for the given project
    *
    * @param Integer $groupId the group id
    * @param Integer $ugroupId the ugroup id
    *
    * @return boolean
    */
    public function exists($groupId, $ugroupId) {
        return $this->UGroupDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
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
        return $this->UGroupUserDao()->returnProjectAdminsByStaticUGroupId($groupId, $ugroups);
    }
}
?>