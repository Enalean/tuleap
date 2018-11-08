<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\MessageFactoryBuilder;
use Tuleap\Project\Webhook\Log\StatusLogger as WebhookStatusLogger;
use Tuleap\Project\Webhook\Log\WebhookLoggerDao;
use Tuleap\Project\Webhook\ProjectCreatedPayload;
use Tuleap\Project\Webhook\WebhookDao;
use Tuleap\Project\Webhook\Retriever;
use Tuleap\Webhook\Emitter;

class ProjectManager
{
    const CONFIG_PROJECT_APPROVAL                            = 'sys_project_approval';
    const CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION          = 'nb_projects_waiting_for_validation';
    const CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER = 'nb_projects_waiting_for_validation_per_user';

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
     * @var Project_HierarchyManager
     */
    private $hierarchy_manager;

    /**
     * A private constructor; prevents direct creation of object
     */
    private function __construct(ProjectDao $dao = null) {
        $this->_dao = $dao;
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

    public static function testInstance(ProjectDao $dao) {
        return new ProjectManager($dao);
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
     *
     * @throws Project_NotFoundException
     */
    public function getValidProject($group_id) {
        return $this->assertProjectIsValid(
            $this->getProject($group_id)
        );
    }

    /**
     * @param string|int $project
     * @return Project
     *
     * @throws Project_NotFoundException
     */
    public function getValidProjectByShortNameOrId($project) {
        try {
            return $this->assertProjectIsValid(
                $this->getProjectByCaseInsensitiveUnixName($project)
            );
        } catch (Project_NotFoundException $exception) {
            return $this->getValidProject($project);
        }
    }

    private function assertProjectIsValid($project) {
        if ($project && ! $project->isError() && ! $project->isDeleted())  {
            return $project;
        }

        throw new Project_NotFoundException();
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
        if (is_array($group_id_or_row)) {
            return new Project($group_id_or_row);
        } else {
            $dar = $this->_getDao()->searchById($group_id_or_row);
            return new Project($dar->getRow());
        }
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
     * @param $status
     * @return int
     */
    public function countProjectsByStatus($status)
    {
        $dar = $this->_getDao()->searchByStatus($status);

        return (int) $this->_getDao()->foundRows();
    }

    /**
     * @return Project[]
     */
    public function getAllProjectsButDeleted() {

        $projects_active     = $this->getProjectsByStatus(Project::STATUS_ACTIVE);
        $projects_pending    = $this->getProjectsByStatus(Project::STATUS_PENDING);
        $projects_holding    = $this->getProjectsByStatus(Project::STATUS_SUSPENDED);

        return array_merge($projects_active, $projects_pending, $projects_holding);
    }

    /**
     * @return Project[]
     */
    public function getAllPrivateProjects() {
        $private_projects = array();
        foreach ($this->_getDao()->searchByPublicStatus(false) as $row) {
            $private_projects[] = $this->getAndCacheProject($row);
        }
        return $private_projects;
    }

    /**
     * @return array
     */
    public function getAllPendingProjects()
    {
        $pending_projects = array();
        foreach ($this->_getDao()->searchByStatus(Project::STATUS_PENDING) as $row) {
            $pending_projects[] = $this->createProjectInstance($row);
        }

        return $pending_projects;
    }

    /**
     * Look for project with name like given one
     *
     * @param String  $name
     * @param Integer $limit
     * @param Integer $nbFound
     * @param PFUser    $user
     * @param Boolean $isMember
     * @param Boolean $isAdmin
     * @param Boolean $isPrivate Display private projects if true
     *
     * @return Array of Project
     */
    public function searchProjectsNameLike($name, $limit, &$nbFound, $user=null, $isMember=false, $isAdmin=false, $isPrivate = false, $offset = 0) {
        $projects = array();
        $dao = new ProjectDao(CodendiDataAccess::instance());
        $dar = $dao->searchProjectsNameLike($name, $limit, $user->getId(), $isMember, $isAdmin, $isPrivate, $offset);
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
            $dar = $dao->searchByCaseInsensitiveUnixGroupName($name);
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

    public function getProjectByCaseInsensitiveUnixName($name) {
        $dar = $this->_getDao()->searchByCaseInsensitiveUnixGroupName($name);
        if ($dar && !$dar->isError() && $dar->rowCount() === 1) {
            return $this->createProjectInstance($dar->getRow());
        }
        return null;
    }

    /**
     * Make project available
     *
     * @param Project $project
     *
     * @return Boolean
     */
    public function activate(Project $project)
    {
        if ($this->activateWithoutNotifications($project)) {
            if (! send_new_project_email($project)) {
                $GLOBALS['Response']->addFeedback('warning', $project->getPublicName()." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
            }
            return true;
        }
        return false;
    }

    public function activateWithoutNotifications(Project $project)
    {
        if ($this->_getDao()->updateStatus($project->getId(), 'A')) {
            include_once 'proj_email.php';

            $this->removeProjectFromCache($project);

            group_add_history('approved', 'x', $project->getId());

            $em = $this->getEventManager();
            $em->processEvent('approve_pending_project', array('group_id' => $project->getId()));

            $this->launchWebhooksProjectCreated($project);

            return true;
        }

        return false;
    }

    private function launchWebhooksProjectCreated(Project $project)
    {
        $webhook_status_logger   = new WebhookStatusLogger(new WebhookLoggerDao());
        $webhook_emitter         = new Emitter(
            MessageFactoryBuilder::build(),
            HttpClientFactory::createClient(),
            $webhook_status_logger
        );
        $project_created_payload = new ProjectCreatedPayload($project, $_SERVER['REQUEST_TIME']);
        $webhooks                = $this->getProjectWebhooks();
        $webhook_emitter->emit($project_created_payload, ...$webhooks);
    }

    public function updateStatus(Project $project, $status)
    {
        if (! $this->_getDao()->updateStatus($project->getId(), $status)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_approve_pending', 'error_update'));
        }

        $this->removeProjectFromCache($project);
    }

    public function removeProjectFromCache(Project $project)
    {
        $project_id = $project->getID();

        if (isset($this->_cached_projects[$project_id])) {
            unset($this->_cached_projects[$project_id]);
        }
    }

    /**
     * Rename project
     *
     * @param Project $project
     * @param String  $new_name
     *
     * @return Boolean
     */
    public function renameProject($project, $new_name){
        //Remove the project from the cache, because it will be modified
        $this->clear($project->getId());
        $dao = $this->_getDao();

        $rename = $dao->renameProject($project, $new_name);

        if ($rename) {
            $success = true;
            $event_manager = EventManager::instance();
            $event_manager->processEvent(
                Event::RENAME_PROJECT,
                array(
                    'project'     => $project,
                    'success'     => &$success,
                    'new_name'    => $new_name,
                )
            );

            return $success;
        }

        return false;
    }

    /**
     * @param int $project_id
     * @param string $plugin_name
     * @param string $new_link
     * @return boolean
     */
    public function renameProjectPluginServiceLink($project_id, $plugin_name, $new_link) {
        return $this->_getDao()->renameProjectPluginServiceLink($project_id, $plugin_name, $new_link);
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

    public function clearProjectFromCache($project_id) {
        unset($this->_cached_projects[$project_id]);
    }

    public function setAccess(Project $project, $access_level) {
        $project_id = $project->getID();
        $old_access = $project->getAccess();

        switch ($access_level) {
            case Project::ACCESS_PRIVATE:
                $this->_getDao()->setIsPrivate($project_id);
                $is_private = true;
                break;
            case Project::ACCESS_PUBLIC:
                $this->_getDao()->setIsPublic($project_id);
                $is_private = false;
                break;
            case Project::ACCESS_PUBLIC_UNRESTRICTED:
                $this->_getDao()->setUnrestricted($project_id);
                $is_private = false;
                break;
            default:
                $GLOBALS['Response']->addFeedback('error', 'bad value '.$access_level);
                return;
        }

        group_add_history('access', $access_level, $project_id);
        $this->getEventManager()->processEvent('project_is_private', array(
            'group_id'           => $project_id,
            'project_is_private' => $is_private,
        ));
        $this->getEventManager()->processEvent(Event::PROJECT_ACCESS_CHANGE, array(
            'project_id'         => $project_id,
            'access'             => $access_level,
            'old_access'         => $old_access,
        ));

        $this->getFrsPermissionsCreator()->updateProjectAccess($project, $old_access, $access_level);
        if ($access_level == Project::ACCESS_PRIVATE) {
            $this->updateForumVisibilityToPrivate($project_id);
        }
    }

    private function getFrsPermissionsCreator()
    {
        return new FRSPermissionCreator(
            new FRSPermissionDao(),
            new UGroupDao()
        );
    }

    public function setTruncatedEmailsUsage(Project $project, $usage) {
        $project_id = $project->getID();
        $this->_getDao()->setTruncatedEmailsUsage($project_id, $usage);

        group_add_history('truncated_emails', $usage, $project_id);
    }

    public function disableAllowRestrictedForAll() {
        $this->_getDao()->disableAllowRestrictedForAll();
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
     * @return DataAccessResult
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

    public function checkRestrictedAccess($group) {
        return $this->getRestrictedAccessForUserInGroup($group, $this->_getUserManager()->getCurrentUser());
    }

    public function checkRestrictedAccessForUser($group, PFUser $user) {
        return $this->getRestrictedAccessForUserInGroup($group, $user);
    }

    /**
     * Checks if the user can access the project $group,
     * regarding the restricted access
     *
     * @param Project $group Affected project
     * @param         $user
     *
     * @return boolean true if the current session user has access to this project, false otherwise
     */
    private function getRestrictedAccessForUserInGroup($group, $user) {
        if (ForgeConfig::areRestrictedUsersAllowed()) {
            if ($group) {
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
    public function getProjectsUserIsAdmin(PFUser $user) {
        // Why not use method in User class?
        return $this->_getDao()
            ->searchProjectsUserIsAdmin($user->getId())
            ->instanciateWith(array($this, 'getProjectFromDbRow'));
    }

    /**
     * @return Project[]
     */
    public function getActiveProjectsForUser(PFUser $user) {
        return $this->_getDao()
            ->searchActiveProjectsForUser($user->getId())
            ->instanciateWith(array($this, 'getProjectFromDbRow'));
    }

    /**
     * @return Project[]
     */
    public function getAllProjectsForUser(PFUser $user)
    {
        return $this->_getDao()
            ->searchAllActiveProjectsForUser($user->getId())
            ->instanciateWith(array($this, 'getProjectFromDbRow'));
    }

    /**
     * @return Tuleap\Project\PaginatedProjects
     */
    public function getMyAndPublicProjectsForREST(PFUser $user, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getMyAndPublicProjectsForREST($user, $offset, $limit);
        $total_size        = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $total_size);
    }

    /**
     * @return Tuleap\Project\PaginatedProjects
     */
    public function getMyProjectsForREST(PFUser $user, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getMyProjectsForREST($user, $offset, $limit);
        $total_size        = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $total_size);
    }

    public function getMyAndPublicProjectsForRESTByShortname($shortname, PFUser $user, $offset, $limit)
    {
        $dao = $this->_getDao();

        $matching_projects = $dao->searchMyAndPublicProjectsForRESTByShortname($shortname, $user, $offset, $limit);
        $total_size        = $dao->foundRows();

        return $this->getPaginatedProjects($matching_projects, $total_size);
    }

    public function getProjectsWithStatusForREST($project_status, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getProjectsWithStatusForREST(
            $project_status,
            $offset,
            $limit
        );

        $total_size = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $total_size);
    }

    private function getPaginatedProjects(LegacyDataAccessResultInterface $result, $total_size)
    {
        $projects = array();
        foreach ($result as $row) {
            $projects[] = $this->getProjectFromDbRow($row);
        }

        return new Tuleap\Project\PaginatedProjects($projects, $total_size);
    }

    /**
     * @return Project[]
     */
    public function getAllMyAndPublicProjects(PFUser $user) {
        $rows = $this->_getDao()
            ->getAllMyAndPublicProjects($user);

        $projects = array();
        foreach ($rows as $row) {
            $project = $this->getProjectFromDbRow($row);
            $projects[$project->getID()] = $project;
        }

        return $projects;
    }

    /**
     * @param int $group_id
     * @param int $parent_group_id
     * @return Boolean
     * @throws Project_HierarchyManagerNoChangeException
     * @throws Project_HierarchyManagerAlreadyAncestorException
     * @throws Project_HierarchyManagerAncestorIsSelfException
     */
    public function setParentProject($group_id, $parent_group_id) {
        $event_manager = EventManager::instance();
        $result        = $this->getHierarchyManager()->setParentProject($group_id, $parent_group_id);

        if ($result) {
            $event_manager->processEvent(Event::PROJECT_SET_PARENT_PROJECT, array(
                'group_id'  => $group_id,
                'parent_id' => $parent_group_id
            ));
        }

        return $result;
    }

    /**
     * @param int $group_id
     * @return Boolean
     */
    public function removeParentProject($group_id) {
        $event_manager = EventManager::instance();
        $result        = $this->getHierarchyManager()->removeParentProject($group_id);

        if ($result) {
            $event_manager->processEvent(Event::PROJECT_UNSET_PARENT_PROJECT, array(
                'group_id'  => $group_id
            ));
        }

        return $result;
    }

    /**
     * @param int $group_id
     * @return Project|null
     */
    public function getParentProject($group_id) {
        return $this->getHierarchyManager()->getParentProject($group_id);
    }

    /**
     * Get all parents of a project
     * @return Project[]
     */
    public function getAllParentsProjects($group_id) {
        $projects   = array();
        $parent_ids = $this->getHierarchyManager()->getAllParents($group_id);

        foreach ($parent_ids as $parent_id) {
            $projects[] = $this->getProject($parent_id);
        }

        return $projects;
    }

    /**
     *
     * @param int $group_id
     * @return Project[]
     */
    public function getChildProjects($group_id) {
        return $this->getHierarchyManager()->getChildProjects($group_id);
    }

    /**
     * @return Project_HierarchyManager
     */
    private function getHierarchyManager() {
        if (! $this->hierarchy_manager) {
            $this->hierarchy_manager = new Project_HierarchyManager(
                $this,
                new ProjectHierarchyDao(CodendiDataAccess::instance())
            );
        }

        return $this->hierarchy_manager;
    }

    /**
     * @return \Tuleap\Project\Webhook\Webhook[]
     */
    private function getProjectWebhooks()
    {
        $webhook_retriever = new Retriever(new WebhookDao());
        return $webhook_retriever->getWebhooks();
    }

    /**
     * Fetch the members of a project
     *
     * @return array user_id => array (
     *      user_id => (int),
     *      user_name => (string),
     *      realname => (string))
     */
    public function getProjectMembers($project_id) {
        $dar = $this->_getDao()->getProjectMembers($project_id);
        if(!$dar) return array();

        $result = array();
        while ($row = $dar->getRow()) {
            $result[$row['user_id']] = $row;
        }
        $dar->freeMemory();
        return $result;
    }

    private function updateForumVisibilityToPrivate($group_id)
    {
            $forum_dao = new ForumDao(CodendiDataAccess::instance());
            return $forum_dao->updatePublicForumToPrivate($group_id);
    }

    public function userCanCreateProject(PFUser $requester)
    {
        if (ForgeConfig::get(self::CONFIG_PROJECT_APPROVAL, 1) == 1) {
            return $this->numberOfProjectsWaitingForValidationBelowThreshold() &&
                $this->numberOfProjectsWaitingForValidationPerUserBelowThreshold($requester);
        }
        return true;
    }

    private function numberOfProjectsWaitingForValidationBelowThreshold()
    {
        $max_nb_projects_waiting_for_validation = (int) ForgeConfig::get(self::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, -1);
        if ($max_nb_projects_waiting_for_validation < 0) {
            return true;
        }
        $current_nb_projects_waiting_for_validation = $this->countProjectsByStatus(Project::STATUS_PENDING);
        return $current_nb_projects_waiting_for_validation < $max_nb_projects_waiting_for_validation;
    }

    private function numberOfProjectsWaitingForValidationPerUserBelowThreshold(PFUser $requester)
    {
        $max_per_user = (int) ForgeConfig::get(self::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, -1);
        if ($max_per_user < 0) {
            return true;
        }
        $current_per_user = $this->_getDao()->countByStatusAndUser($requester->getId(), Project::STATUS_PENDING);
        return $current_per_user < $max_per_user;
    }

    public function countRegisteredProjectsBefore($timestamp)
    {
        return $this->_getDao()->countProjectRegisteredBefore($timestamp);
    }
}
