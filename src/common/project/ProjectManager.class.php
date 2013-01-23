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
     * ProjectManager is a singleton need this to test
     */
    public static function setInstance($instance) {
        self::$_instance = $instance;
    }
    /**
     * ProjectManager is a singleton need this to clean after tests
     * @return ProjectManager
     */
    public static function clearInstance() {
        self::$_instance = null;
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
     * Instanciate a project based on a database row
     *
     * @param array $row
     *
     * @return Project
     */
    public function getProjectFromDbRow(array $row) {
        return $this->getAndCacheProject($row);
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
     * @param Boolean $isPrivate Display private projects if true
     *
     * @return Array of Project
     */
    public function searchProjectsNameLike($name, $limit, &$nbFound, $user=null, $isMember=false, $isAdmin=false, $isPrivate = false) {
        $projects = array();
        $dao = new ProjectDao(CodendiDataAccess::instance());
        $dar = $dao->searchProjectsNameLike($name, $limit, $user->getId(), $isMember, $isAdmin, $isPrivate);
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
     * @return Project
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
     * Make project available
     *
     * @param Project $project
     *
     * @return Boolean
     */
    public function activate(Project $project) {
        $dao = $this->_getDao();
        if ($dao->updateStatus($project->getId(), 'A')) {
            include_once 'proj_email.php';

            group_add_history('approved', 'x', $project->getId());

            $em = $this->getEventManager();
            $em->processEvent('approve_pending_project', array('group_id' => $project->getId()));

            if (!send_new_project_email($project)) {
                $GLOBALS['Response']->addFeedback('warning', $project->getPublicName()." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
            }
            return true;
        }
        return false;
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
     * Deletes the ugroups & the message related to a given group
     *
     * @param Integer $groupId
     *
     * @return Boolean
     */
    public function deleteMembershipRequestNotificationEntries($groupId) {
        $dao = $this->_getDao();
        if ($dao->deleteMembershipRequestNotificationUGroup($groupId)) {
            return $dao->deleteMembershipRequestNotificationMessage($groupId);
        }
        return false;
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

    /**
     * Remove Project members from a project
     *
     * @param Project $project Affected project
     *
     * @return Boolean
     */
    public function removeProjectMembers($project) {
        if (!$project || !is_object($project) || $project->isError()) {
            exit_no_group();
        }
        $dao = new UserGroupDao(CodendiDataAccess::instance());
        return $dao->removeProjectMembers($project->getID());
    }

    /**
     * Get the project from its id for SOAP
     *
     * @param Integer $groupId    Id of the project
     * @param String  $method     Name of the callback method
     * @param Boolean $byUnixName Optional, Search the project by its unix name instead of its id
     *
     * @return Project or SoapFault
     */
    function getGroupByIdForSoap($groupId, $method, $byUnixName = false) {
        if ($byUnixName) {
            $group = $this->getProjectByUnixName($groupId);
        } else {
            $group = $this->getProject($groupId);
        }
        if (!$group || !is_object($group)) {
            throw new SoapFault(get_group_fault, $groupId.' : '.$GLOBALS['Language']->getText('include_group', 'g_not_found'), $method);
        } elseif ($group->isError()) {
            throw new SoapFault(get_group_fault, $group->getErrorMessage(), $method);
        } elseif (!$group->isActive()) {
            throw new SoapFault(get_group_fault, $group->getUnixName().' : '.$GLOBALS['Language']->getText('include_exit', 'project_status_'.$group->getStatus()), $method);
        }
        if (!$this->checkRestrictedAccess($group)) {
            throw new SoapFault(get_group_fault, 'Restricted user: permission denied.', $method);
        }
        return $group;
    }

    /**
     * Assert given groupid is valid, otherwise throw exception
     *
     * @param Integer $groupId    Id of the project
     * @param String  $method     Name of the callback method
     * @param Boolean $byUnixName Optional, Search the project by its unix name instead of its id
     */
    public function checkGroupIdForSoap($groupId, $method, $byUnixName = false) {
        $this->getGroupByIdForSoap($groupId, $method, $byUnixName);
    }
    
    /**
     * Check if the user can access the project $group,
     * regarding the restricted access
     *
     * @param Project $group Affected project
     * @return boolean true if the current session user has access to this project, false otherwise
     */
    function checkRestrictedAccess($group) {
        if (array_key_exists('sys_allow_restricted_users', $GLOBALS) && $GLOBALS['sys_allow_restricted_users'] == 1) {
            if ($group) {
                $user = $this->_getUserManager()->getCurrentUser();
                if ($user) {
                    if ($user->isRestricted()) {
                        return $group->userIsMember();
                    } else {
                        return true;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Set SVN header
     *
     * @param Integer $projectId
     * @param String  $mailingHeader
     *
     * @return Boolean
     */
    function setSvnHeader($projectId, $mailingHeader) {
        $dao = $this->_getDao();
        return $dao->setSvnHeader($projectId, $mailingHeader);
    }

    /**
     * Wrapper for tests
     *
     * @return UserManager
     */
    function _getUserManager() {
        return UserManager::instance();
    }

    /**
     * Wrapper
     *
     * @return EventManager
     */
    protected function getEventManager() {
        return EventManager::instance();
    }

    /**
     * Return all projects matching given parameters
     *
     * @param Integer $offset    Offset
     * @param Integer $limit     Limit of the search
     * @param String  $status    Status of the projects to search
     * @param String  $groupName Name to search
     *
     * @return Array ('projects' => DataAccessResult, 'numrows' => int)
     */
    public function getAllProjectsRows($offset, $limit, $status = false, $groupName = false) {
        $dao = $this->_getDao();
        return $dao->returnAllProjects($offset, $limit, $status, $groupName);
    }

    /**
     * @return Project[]
     */
    public function getSiteTemplates() {
        return $this->_getDao()
            ->searchSiteTemplates()
            ->instanciateWith(array($this, 'getProjectFromDbRow'));
    }

    /**
     * @return Project[]
     */
    public function getProjectsUserIsAdmin(User $user) {
        // Why not use method in User class?
        return $this->_getDao()
            ->searchProjectsUserIsAdmin($user->getId())
            ->instanciateWith(array($this, 'getProjectFromDbRow'));
    }
}

?>