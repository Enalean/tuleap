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
require_once('Project.class.php');
require_once('common/dao/ProjectDao.class.php');

/**
 * Provide access to projects
 */
class ProjectManager {
    
    /**
     * The Projects dao used to fetch data
     */
    protected $_dao;
    
    /**
     * stores the fetched projects
     */
    protected $_cached_projects;
    
    /**
     * Hold an instance of the class
     */
    private static $_instance;
    
    /**
     * A private constructor; prevents direct creation of object
     */
    private function __construct() {
    //    $this->_dao = $this->getDao();
        $this->_cached_projects = array();
    }
    
    /**
     * ProjectManager is a singleton
     * @return ProjectManager
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    /**
     * @return ProjectDao
     */
    public function _getDao() {
        if (!isset($this->_dao)) {
            $this->_dao = new ProjectDao(CodendiDataAccess::instance());
        }
        return $this->_dao;
    }
    
    /**
     * @param $group_id int The id of the project to look for
     * @return Project
     */
    public function getProject($group_id) {
        if (!isset($this->_cached_projects[$group_id])) {
            $p = $this->createProjectInstance($group_id);
            $this->_cached_projects[$group_id] = $p;
        }
        return $this->_cached_projects[$group_id];
    }
    
    /**
     * @param $group_id int The id of the project to look for
     * @return Project
     */
    protected function createProjectInstance($group_id_or_row) {
        return new Project($group_id_or_row);
    }
    
    /**
     * Clear the cache for project $group_id
     */
    public function clear($group_id) {
        unset($this->_cached_projects[$group_id]);
    }
    
    public function getProjectsByStatus($status) {
        $projects = array();
        $dao = new ProjectDao(CodendiDataAccess::instance());
        foreach($dao->searchByStatus($status) as $row) {
            $projects[$row['group_id']] = $this->getAndCacheProject($row);
        }
        return $projects;
    }
    
    /**
     * Look for project with name like given one
     * 
     * @param String  $name
     * @param Integer $limit
     * @param Integer $nbFound
     * @param User    $user
     * @param Boolean $isMember
     * @param Boolean $isAdmin
     * 
     * @return Array of Project
     */
    public function searchProjectsNameLike($name, $limit, &$nbFound, $user=null, $isMember=false, $isAdmin=false) {
        $projects = array();
        $dao = new ProjectDao(CodendiDataAccess::instance());
        $dar = $dao->searchProjectsNameLike($name, $limit, $user->getId(), $isMember, $isAdmin);
        $nbFound = $dao->foundRows();
        foreach($dar as $row) {
            $projects[] = $this->getAndCacheProject($row);
        }
        return $projects; 
    }

    /**
     * Try to find the project that match what can be entred in autocompleter
     * 
     * This can be either:
     * - The autocomplter result: Public Name (unixname)
     * - The group id: 101
     * - The project unix name: unixname
     * 
     * @return mixed Project or false
     */
    public function getProjectFromAutocompleter($name) {
        $matches = array();
        $dao = new ProjectDao(CodendiDataAccess::instance());
        if (preg_match('/^(.*) \((.*)\)$/', $name, $matches)) {
            // Autocompleter "normal" form: Public Name (unix_name); {
            $dar = $dao->searchByUnixGroupName($matches[2]);
        }
        elseif (is_numeric($name)) {
            // Only group_id (for codex guru or psychopath, more or less the same thing anyway)
            $dar = $dao->searchById($name);
        }
        else {
            // Give it a try with only the given name
            $dar = $dao->searchByUnixGroupName($name);
        }

        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return $this->getAndCacheProject($dar->getRow());
        }
        return false;
    }
    
    /**
     * Create new Project object from row or get it from cache if already built
     *
     * @param Array $row
     * 
     * @return Project
     */
    protected function getAndCacheProject($row) {
        if (!isset($this->_cached_projects[$row['group_id']])) {
            $p = $this->createProjectInstance($row);
            $this->_cached_projects[$row['group_id']] = $p;
        }
        return $this->_cached_projects[$row['group_id']];
    }

    /**
     * Return the project that match given unix name
     *  
     * @param String $name
     * 
     * @return Project
     */
    public function getProjectByUnixName($name) {
        $p = null;
        $dar = $this->_getDao()->searchByUnixGroupName($name);
        if ($dar && !$dar->isError() && $dar->rowCount() === 1) {
            $p = $this->createProjectInstance($dar->getRow());
        }
        return $p;
    }

    /**
     * Rename project
     * 
     * @param Project $project
     * @param String  $new_name
     * 
     * @return Boolean
     */
    public function renameProject($project,$new_name){
        //Remove the project from the cache, because it will be modified
        $this->clear($project->getId());
        $dao = $this->_getDao();
        return $dao->renameProject($project, $new_name);
    }

    /**
     * Return true if project id is cached
     * 
     * @param Integer $group_id
     * 
     * @return Boolean
     */
    public function isCached($group_id) {
        return (isset($this->_cached_projects[$group_id]));
    }
    
    /**
     * Filled the ugroups to be notified when admin action is needed 
     * 
     * @param Integer $groupId
     * @param Array   $ugroups
     * 
     * @return Boolean
     */
    public function setMembershipRequestNotificationUGroup($groupId, $ugroups) {
        $dao = $this->_getDao();
        return $dao->setMembershipRequestNotificationUGroup($groupId, $ugroups);
    }

    /**
     * Returns the ugroups to be notified when admin action is needed
     * If no ugroup is assigned, it returns the ugroup project admin
     * 
     * @param Integer $groupId
     * 
     * @return DataAceesResult
     */
    public function getMembershipRequestNotificationUGroup($groupId) {
        $dao = $this->_getDao();
        return $dao->getMembershipRequestNotificationUGroup($groupId);
    }

    /**
     * Returns the message to be displayed to requester asking access for a given project
     * 
     * @param Integer $groupId
     * 
     * @return DataAceesResult
     */
    public function getMessageToRequesterForAccessProject($groupId) {
        $dao = $this->_getDao();
        return $dao->getMessageToRequesterForAccessProject($groupId);
    }

    /**
     * Defines the message to be displayed to requester asking access for a given project
     * 
     * @param Integer $groupId
     * @param String  $message
     * 
     */
    public function setMessageToRequesterForAccessProject($groupId, $message) {
        $dao = $this->_getDao();
        return $dao->setMessageToRequesterForAccessProject($groupId, $message);
    }

    /**
     * Return the sql request retreiving project admins of given project
     *
     * @param Integer $groupId
     *
     * @return Data Access Result
     */
    function returnProjectAdminsByGroupId($groupId) {
        $dao = new UserGroupDao(CodendiDataAccess::instance());
        return $dao->returnProjectAdminsByGroupId($groupId);
    }
}
?>
