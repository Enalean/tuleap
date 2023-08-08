<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Config\ConfigKey;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\DeletedProjectStatusChangeException;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipProjectVisibilityToggler;
use Tuleap\Project\Webhook\Log\StatusLogger as WebhookStatusLogger;
use Tuleap\Project\Webhook\Log\WebhookLoggerDao;
use Tuleap\Project\Webhook\ProjectCreatedPayload;
use Tuleap\Project\Webhook\WebhookDao;
use Tuleap\Project\Webhook\Retriever;
use Tuleap\Webhook\Emitter;

class ProjectManager implements ProjectByIDFactory, ProjectByUnixNameFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    #[ConfigKey("Is project creation allowed to regular users (1) or not (0)")]
    public const CONFIG_PROJECTS_CAN_BE_CREATED = 'sys_use_project_registration';

    #[ConfigKey("Should project be approved by site admin (1) or auto approved (0)")]
    public const CONFIG_PROJECT_APPROVAL = 'sys_project_approval';

    #[ConfigKey("Max number of projects in the site administration validation queue")]
    public const CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION = 'nb_projects_waiting_for_validation';

    #[ConfigKey("Max number of projects a user can submit in validation queue")]
    public const CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER = 'nb_projects_waiting_for_validation_per_user';

    #[ConfigKey("Are restricted users allowed to create projects (1) or not (0)")]
    public const CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS = 'restricted_users_can_create_projects';

    #[ConfigKey("Are project admin allowed to choose project's visibility (1) or not (0)")]
    public const SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY = 'sys_user_can_choose_project_privacy';

    /**
     * The Projects dao used to fetch data
     */
    protected $_dao; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * stores the fetched projects
     */
    protected $_cached_projects; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * Hold an instance of the class
     */
    private static $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * @var Project_HierarchyManager
     */
    private $hierarchy_manager;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    /**
     * A private constructor; prevents direct creation of object
     */
    private function __construct(
        ProjectAccessChecker $project_access_checker,
        ProjectHistoryDao $project_history_dao,
        ?ProjectDao $dao = null,
    ) {
        $this->_dao                   = $dao;
        $this->_cached_projects       = [];
        $this->project_history_dao    = $project_history_dao;
        $this->project_access_checker = $project_access_checker;
    }

    /**
     * ProjectManager is a singleton
     * @return ProjectManager
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $project_access_checker = new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            );
            self::$_instance        = new self($project_access_checker, new ProjectHistoryDao());
        }
        return self::$_instance;
    }

    /**
     * ProjectManager is a singleton need this to test
     */
    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    /**
     * ProjectManager is a singleton need this to clean after tests
     * @return ProjectManager
     */
    public static function clearInstance()
    {
        self::$_instance = null;
    }

    public static function testInstance(
        ProjectAccessChecker $project_access_checker,
        ProjectHistoryDao $project_history_dao,
        ProjectDao $dao,
    ) {
        return new self($project_access_checker, $project_history_dao, $dao);
    }

    /**
     * @return ProjectDao
     */
    public function _getDao() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (! isset($this->_dao)) {
            $this->_dao = new ProjectDao(CodendiDataAccess::instance());
        }
        return $this->_dao;
    }

    /**
     * @param $group_id int The id of the project to look for
     * @return Project
     */
    public function getProject($group_id)
    {
        if (! isset($this->_cached_projects[$group_id])) {
            $p                                 = $this->createProjectInstance($group_id);
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
    public function getValidProject($group_id)
    {
        return $this->assertProjectIsValid(
            $this->getProject($group_id)
        );
    }

    public function getProjectById(int $project_id): \Project
    {
        return $this->getProject($project_id);
    }

    /**
     * @throws Project_NotFoundException
     */
    public function getValidProjectById(int $project_id): \Project
    {
        return $this->getValidProject($project_id);
    }

    /**
     * @param string|int $project
     * @return Project
     *
     * @throws Project_NotFoundException
     */
    public function getValidProjectByShortNameOrId($project)
    {
        try {
            return $this->assertProjectIsValid(
                $this->getProjectByCaseInsensitiveUnixName($project)
            );
        } catch (Project_NotFoundException $exception) {
            return $this->getValidProject($project);
        }
    }

    private function assertProjectIsValid($project)
    {
        if ($project && ! $project->isError() && ! $project->isDeleted()) {
            return $project;
        }

        throw new Project_NotFoundException();
    }

    /**
     * Instanciate a project based on a database row
     *
     * @param array $row
     *
     */
    public function getProjectFromDbRow(array $row): Project
    {
        return $this->getAndCacheProject($row);
    }

    /**
     * @param $group_id int The id of the project to look for
     */
    protected function createProjectInstance($group_id_or_row): Project
    {
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
    public function clear($group_id)
    {
        unset($this->_cached_projects[$group_id]);
    }

    public function getProjectsByStatus($status)
    {
        $projects = [];
        $dao      = new ProjectDao(CodendiDataAccess::instance());
        foreach ($dao->searchByStatus($status) as $row) {
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
        return $this->_getDao()->countByStatus($status);
    }

    /**
     * @return Project[]
     */
    public function getAllProjectsButDeleted()
    {
        $projects_active  = $this->getProjectsByStatus(Project::STATUS_ACTIVE);
        $projects_pending = $this->getProjectsByStatus(Project::STATUS_PENDING);
        $projects_holding = $this->getProjectsByStatus(Project::STATUS_SUSPENDED);

        return array_merge($projects_active, $projects_pending, $projects_holding);
    }

    /**
     * @return Project[]
     */
    public function getAllPrivateProjects()
    {
        $private_projects = [];
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
        $pending_projects = [];
        foreach ($this->_getDao()->searchByStatus(Project::STATUS_PENDING) as $row) {
            $pending_projects[] = $this->createProjectInstance($row);
        }

        return $pending_projects;
    }

    /**
     * Look for project with name like given one
     *
     * @param String  $name
     * @param int $limit
     * @param int $nbFound
     * @param PFUser    $user
     * @param bool $isMember
     * @param bool $isAdmin
     * @param bool $isPrivate Display private projects if true
     *
     * @return Array of Project
     */
    public function searchProjectsNameLike($name, $limit, &$nbFound, $user = null, $isMember = false, $isAdmin = false, $isPrivate = false, $offset = 0)
    {
        if ($user === null) {
            return [];
        }
        $projects = [];
        $dao      = new ProjectDao(CodendiDataAccess::instance());
        $dar      = $dao->searchProjectsNameLike($name, $limit, $user->getId(), $isMember, $isAdmin, $isPrivate, $offset);
        $nbFound  = $dao->foundRows();
        foreach ($dar as $row) {
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
    public function getProjectFromAutocompleter($name)
    {
        $matches = [];
        $dao     = new ProjectDao(CodendiDataAccess::instance());
        if (preg_match('/^(.*) \((.*)\)$/', $name, $matches)) {
            // Autocompleter "normal" form: Public Name (unix_name); {
            $dar = $dao->searchByUnixGroupName($matches[2]);
        } elseif (is_numeric($name)) {
            // Only group_id (for codex guru or psychopath, more or less the same thing anyway)
            $dar = $dao->searchById($name);
        } else {
            // Give it a try with only the given name
            $dar = $dao->searchByCaseInsensitiveUnixGroupName($name);
        }

        if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
            return $this->getAndCacheProject($dar->getRow());
        }
        return false;
    }

    /**
     * Create new Project object from row or get it from cache if already built
     *
     * @param Array $row
     */
    protected function getAndCacheProject($row): Project
    {
        if (! isset($this->_cached_projects[$row['group_id']])) {
            $p                                        = $this->createProjectInstance($row);
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
    public function getProjectByUnixName($name)
    {
        $p   = null;
        $dar = $this->_getDao()->searchByUnixGroupName($name);
        if ($dar && ! $dar->isError() && $dar->rowCount() === 1) {
            $p = $this->createProjectInstance($dar->getRow());
        }
        return $p;
    }

    public function getProjectByCaseInsensitiveUnixName($name): ?Project
    {
        $dar = $this->_getDao()->searchByCaseInsensitiveUnixGroupName($name);
        if ($dar && ! $dar->isError() && $dar->rowCount() === 1) {
            return $this->createProjectInstance($dar->getRow());
        }
        return null;
    }

    /**
     * Make project available
     *
     *
     * @return bool
     */
    public function activate(Project $project)
    {
        if ($this->activateWithoutNotifications($project)) {
            if (! send_new_project_email($project)) {
                $GLOBALS['Response']->addFeedback('warning', $project->getPublicName() . " - " . $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')]));
            }
            return true;
        }
        return false;
    }

    public function activateWithoutNotifications(Project $project)
    {
        if ($this->_getDao()->updateStatus($project->getId(), 'A')) {
            include_once __DIR__ . '/../../www/include/proj_email.php';

            $this->removeProjectFromCache($project);

            $this->project_history_dao->groupAddHistory('approved', 'x', $project->getId());

            $em = $this->getEventManager();
            $em->processEvent('approve_pending_project', ['group_id' => $project->getId()]);

            $this->launchWebhooksProjectCreated($project);

            return true;
        }

        return false;
    }

    private function launchWebhooksProjectCreated(Project $project)
    {
        $webhook_status_logger   = new WebhookStatusLogger(new WebhookLoggerDao());
        $webhook_emitter         = new Emitter(
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            HttpClientFactory::createAsyncClient(),
            $webhook_status_logger
        );
        $project_created_payload = new ProjectCreatedPayload($project, $_SERVER['REQUEST_TIME']);
        $webhooks                = $this->getProjectWebhooks();
        $webhook_emitter->emit($project_created_payload, ...$webhooks);
    }

    /**
     * @throws DeletedProjectStatusChangeException
     */
    public function updateStatus(Project $project, string $status): void
    {
        if ($project->getStatus() === Project::STATUS_DELETED) {
            throw new DeletedProjectStatusChangeException();
        }

        if (! $this->_getDao()->updateStatus($project->getId(), $status)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_approve_pending', 'error_update'));
        }

        $this->removeProjectFromCache($project);
    }

    public function removeProjectFromCache(Project $project): void
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
     * @return bool
     */
    public function renameProject($project, $new_name)
    {
        //Remove the project from the cache, because it will be modified
        $this->clear($project->getId());
        $dao = $this->_getDao();

        $rename = $dao->renameProject($project, $new_name);

        if ($rename) {
            $success       = true;
            $event_manager = EventManager::instance();
            $event_manager->processEvent(
                Event::RENAME_PROJECT,
                [
                    'project'     => $project,
                    'success'     => &$success,
                    'new_name'    => $new_name,
                ]
            );

            return $success;
        }

        return false;
    }

    /**
     * Return true if project id is cached
     *
     * @param int $group_id
     *
     * @return bool
     */
    public function isCached($group_id)
    {
        return (isset($this->_cached_projects[$group_id]));
    }

    public function clearProjectFromCache($project_id)
    {
        unset($this->_cached_projects[$project_id]);
    }

    public function setAccess(Project $project, $access_level)
    {
        $project_id = (int) $project->getID();
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
            case Project::ACCESS_PRIVATE_WO_RESTRICTED:
                $this->_getDao()->setIsPrivateWORestricted($project_id);
                $is_private = true;
                break;
            default:
                $GLOBALS['Response']->addFeedback('error', 'bad value ' . $access_level);
                return;
        }

        $this->project_history_dao->groupAddHistory('access', $access_level, $project_id);
        $this->getEventManager()->processEvent('project_is_private', [
            'group_id'           => $project_id,
            'project_is_private' => $is_private,
        ]);
        $this->getEventManager()->processEvent(Event::PROJECT_ACCESS_CHANGE, [
            'project_id'         => $project_id,
            'access'             => $access_level,
            'old_access'         => $old_access,
        ]);

        $this->getFrsPermissionsCreator()->updateProjectAccess($project, $old_access, $access_level);
        if ($is_private) {
            $this->updateForumVisibilityToPrivate($project_id);
        }
        $this->getSynchronizedProjectMembershipProjectVisibilityToggler()->enableAccordingToVisibility($project, $old_access, $access_level);
    }

    private function getFrsPermissionsCreator()
    {
        return new FRSPermissionCreator(
            new FRSPermissionDao(),
            new UGroupDao(),
            $this->project_history_dao
        );
    }

    private function getSynchronizedProjectMembershipProjectVisibilityToggler()
    {
        return new SynchronizedProjectMembershipProjectVisibilityToggler(
            new SynchronizedProjectMembershipDao()
        );
    }

    public function setTruncatedEmailsUsage(Project $project, $usage)
    {
        $project_id = $project->getID();
        $this->_getDao()->setTruncatedEmailsUsage($project_id, $usage);

        $this->project_history_dao->groupAddHistory('truncated_emails', $usage, $project_id);
    }

    public function disableAllowRestrictedForAll()
    {
        $this->_getDao()->switchUnrestrictedToPublic();
        $this->_getDao()->switchPrivateWithoutRestrictedToPrivate();
    }

    /**
     * Filled the ugroups to be notified when admin action is needed
     *
     * @param int $groupId
     * @param Array   $ugroups
     *
     * @return bool
     */
    public function setMembershipRequestNotificationUGroup($groupId, $ugroups)
    {
        $dao = $this->_getDao();
        return $dao->setMembershipRequestNotificationUGroup($groupId, $ugroups);
    }

    /**
     * Returns the ugroups to be notified when admin action is needed
     * If no ugroup is assigned, it returns the ugroup project admin
     *
     * @param int $groupId
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getMembershipRequestNotificationUGroup($groupId)
    {
        $dao = $this->_getDao();
        return $dao->getMembershipRequestNotificationUGroup($groupId);
    }

    /**
     * Deletes the ugroups & the message related to a given group
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function deleteMembershipRequestNotificationEntries($groupId)
    {
        $dao = $this->_getDao();
        if ($dao->deleteMembershipRequestNotificationUGroup($groupId)) {
            return $dao->deleteMembershipRequestNotificationMessage($groupId);
        }
        return false;
    }

    /**
     * Returns the message to be displayed to requester asking access for a given project
     *
     * @param int $groupId
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getMessageToRequesterForAccessProject($groupId)
    {
        $dao = $this->_getDao();
        return $dao->getMessageToRequesterForAccessProject($groupId);
    }

    /**
     * Defines the message to be displayed to requester asking access for a given project
     *
     * @param int $groupId
     * @param String  $message
     *
     */
    public function setMessageToRequesterForAccessProject($groupId, $message)
    {
        $dao = $this->_getDao();
        return $dao->setMessageToRequesterForAccessProject($groupId, $message);
    }

    /**
     * Return the sql request retreiving project admins of given project
     *
     * @param int $groupId
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function returnProjectAdminsByGroupId($groupId)
    {
        $dao = new UserGroupDao(CodendiDataAccess::instance());
        return $dao->returnProjectAdminsByGroupId($groupId);
    }

    /**
     * Remove Project members from a project
     *
     * @param Project $project Affected project
     *
     * @return bool
     */
    public function removeProjectMembers($project)
    {
        if (! $project || ! is_object($project) || $project->isError()) {
            exit_no_group();
        }
        $dao = new UserGroupDao(CodendiDataAccess::instance());
        return $dao->removeProjectMembers($project->getID());
    }

    /**
     * Set SVN header
     *
     * @param int $projectId
     * @param String  $mailingHeader
     *
     * @return bool
     */
    public function setSvnHeader($projectId, $mailingHeader)
    {
        $dao = $this->_getDao();
        return $dao->setSvnHeader($projectId, $mailingHeader);
    }

    /**
     * Wrapper for tests
     *
     * @return UserManager
     */
    protected function _getUserManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return UserManager::instance();
    }

    /**
     * Wrapper
     *
     * @return EventManager
     */
    protected function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Return all projects matching given parameters
     *
     * @param int $offset Offset
     * @param int $limit Limit of the search
     * @param String  $status    Status of the projects to search
     * @param String  $groupName Name to search
     *
     * @return Array ('projects' => DataAccessResult, 'numrows' => int)
     */
    public function getAllProjectsRows($offset, $limit, $status = false, $groupName = false)
    {
        $dao = $this->_getDao();
        return $dao->returnAllProjects($offset, $limit, $status, $groupName);
    }

    /**
     * @return Project[]
     */
    public function getSiteTemplates()
    {
        $projects = [];
        foreach ($this->_getDao()->searchSiteTemplates() as $row) {
            $projects[] = $this->getProjectFromDbRow($row);
        }

        return $projects;
    }

    /**
     * @return Project[]
     */
    public function getProjectsUserIsAdmin(PFUser $user)
    {
        return $this->instantiateProjectsForUser(
            $this->_getDao()->searchProjectsUserIsAdmin($user->getId()),
            $user
        );
    }

    /**
     * @return Project[]
     */
    public function getActiveProjectsForUser(PFUser $user)
    {
        return $this->instantiateProjectsForUser(
            $this->_getDao()->searchActiveProjectsForUser($user->getId()),
            $user
        );
    }

    /**
     * @return Project[]
     */
    public function getAllProjectsForUserIncludingTheOnesSheDoesNotHaveAccessTo(PFUser $user)
    {
        return $this->_getDao()
            ->searchAllActiveProjectsForUser($user->getId())
            ->instanciateWith([$this, 'getProjectFromDbRow']);
    }

    private function instantiateProjectsForUser(LegacyDataAccessResultInterface $projects_results, PFUser $user)
    {
        $projects = [];
        foreach ($projects_results as $row) {
            if (
                $row['access'] === Project::ACCESS_PRIVATE_WO_RESTRICTED &&
                ForgeConfig::areRestrictedUsersAllowed() &&
                $user->isRestricted()
            ) {
                continue;
            }
            $projects[] = $this->getProjectFromDbRow($row);
        }

        return $projects;
    }

    /**
     * @return Tuleap\Project\PaginatedProjects
     */
    public function getMyAndPublicProjectsForREST(PFUser $user, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getMyAndPublicProjectsForREST($user, $offset, $limit);
        $total_size        = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $user, $total_size);
    }

    /**
     * @return Tuleap\Project\PaginatedProjects
     */
    public function getMyProjectsForREST(PFUser $user, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getMyProjectsForREST($user, $offset, $limit);
        $total_size        = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $user, $total_size);
    }

    /**
     * @return Tuleap\Project\PaginatedProjects
     */
    public function getProjectICanAdminForREST(PFUser $user, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getProjectICanAdminForREST($user, $offset, $limit);
        $total_size        = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $user, $total_size);
    }

    public function getMyAndPublicProjectsForRESTByShortname($shortname, PFUser $user, $offset, $limit)
    {
        $dao = $this->_getDao();

        $matching_projects = $dao->searchMyAndPublicProjectsForRESTByShortname($shortname, $user, $offset, $limit);
        $total_size        = $dao->foundRows();

        return $this->getPaginatedProjects($matching_projects, $user, $total_size);
    }

    public function getProjectsWithStatusForREST($project_status, PFUser $user, $offset, $limit)
    {
        $matching_projects = $this->_getDao()->getProjectsWithStatusForREST(
            $project_status,
            $offset,
            $limit
        );

        $total_size = $this->_getDao()->foundRows();

        return $this->getPaginatedProjects($matching_projects, $user, $total_size);
    }

    private function getPaginatedProjects(LegacyDataAccessResultInterface $result, PFUser $user, $total_size)
    {
        $projects = [];
        foreach ($result as $row) {
            $project = $this->getProjectFromDbRow($row);
            try {
                $this->project_access_checker->checkUserCanAccessProject($user, $project);
            } catch (Project_AccessException $e) {
                continue;
            }

            $projects[] = $project;
        }

        return new Tuleap\Project\PaginatedProjects($projects, $total_size);
    }

    /**
     * @param int $group_id
     * @param int $parent_group_id
     * @return bool
     * @throws Project_HierarchyManagerAlreadyAncestorException
     * @throws Project_HierarchyManagerAncestorIsSelfException
     */
    public function setParentProject($group_id, $parent_group_id)
    {
        $event_manager = EventManager::instance();
        $result        = $this->getHierarchyManager()->setParentProject($group_id, $parent_group_id);

        if ($result) {
            $event_manager->processEvent(Event::PROJECT_SET_PARENT_PROJECT, [
                'group_id'  => $group_id,
                'parent_id' => $parent_group_id,
            ]);
        }

        return $result;
    }

    /**
     * @param int $group_id
     * @return bool
     */
    public function removeParentProject($group_id)
    {
        $event_manager = EventManager::instance();
        $result        = $this->getHierarchyManager()->removeParentProject($group_id);

        if ($result) {
            $event_manager->processEvent(Event::PROJECT_UNSET_PARENT_PROJECT, [
                'group_id'  => $group_id,
            ]);
        }

        return $result;
    }

    /**
     * @param int $group_id
     * @return Project|null
     */
    public function getParentProject($group_id)
    {
        return $this->getHierarchyManager()->getParentProject($group_id);
    }

    /**
     * Get all parents of a project
     * @return Project[]
     */
    public function getAllParentsProjects($group_id)
    {
        $projects   = [];
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
    public function getChildProjects($group_id)
    {
        return $this->getHierarchyManager()->getChildProjects($group_id);
    }

    /**
     * @return Project_HierarchyManager
     */
    private function getHierarchyManager()
    {
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
    public function getProjectMembers($project_id)
    {
        $dar = $this->_getDao()->getProjectMembers($project_id);
        if (! $dar) {
            return [];
        }

        $result = [];
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

    public function countRegisteredProjectsBefore($timestamp)
    {
        return $this->_getDao()->countProjectRegisteredBefore($timestamp);
    }
}
