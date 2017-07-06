<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectHistoryDao;
use SystemEvent;
use SystemEventManager;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\v1\FullRepositoryRepresentation;
use Tuleap\REST\v1\RepositoryRepresentationBuilder;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Dao;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_DELETE_REPOSITORY;
use Tuleap\Svn\Repository\CannotCreateRepositoryException;
use Tuleap\Svn\Repository\CannotFindRepositoryException;
use Tuleap\Svn\Repository\HookDao;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryCreator;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RuleName;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\SvnLogger;
use Tuleap\Svn\SvnPermissionManager;

class RepositoryResource extends AuthenticatedResource
{
    /**
     * @var RepositoryManager
     */
    public $repository_manager;

    /**
     * @var SvnPermissionManager
     */
    public $permission_manager;

    /**
     * @var \UserManager
     */
    public $user_manager;

    /**
     * @var SystemEventManager
     */
    public $system_event_manager;

    /**
     * @var \ProjectManager
     */
    public $project_manager;

    /**
     * @var RepositoryCreator
     */
    public $repository_creator;

    public function __construct()
    {
        $dao                        = new Dao();
        $logger                     = new SvnLogger();
        $system_command             = new \System_Command();
        $backend_svn                = \Backend::instance(\Backend::SVN);
        $project_history_dao        = new ProjectHistoryDao();
        $this->system_event_manager = \SystemEventManager::instance();
        $this->project_manager      = \ProjectManager::instance();
        $hook_dao                   = new HookDao();
        $this->repository_manager   = new RepositoryManager(
            $dao,
            $this->project_manager,
            new SvnAdmin($system_command, $logger, $backend_svn),
            $logger,
            $system_command,
            new Destructor($dao, $logger),
            $hook_dao,
            \EventManager::instance(),
            $backend_svn,
            new AccessFileHistoryFactory(new AccessFileHistoryDao()),
            $this->system_event_manager,
            $project_history_dao
        );

        $this->user_manager       = \UserManager::instance();
        $this->permission_manager = new SvnPermissionManager(
            new \User_ForgeUserGroupFactory(new \UserGroupDao()),
            \PermissionsManager::instance()
        );

        $this->repository_creator  = new RepositoryCreator($dao, $this->system_event_manager, $project_history_dao);
    }

    /**
     * Return info about repository if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the repository
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get SVN
     *
     * Get info about project SVN repositories
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"project": {...},<br>
     *   &nbsp;"uri": "svn/90",<br>
     *   &nbsp;"name": "repo",<br>
     *   &nbsp;"settings": {<br>
     *   &nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;"mandatory_reference": true|false ,<br>
     *   &nbsp;&nbsp;"allow_commit_message_change": true|false<br>
     *   &nbsp;&nbsp;}<br>
     *   &nbsp;}<br>
     *  }<br>
     * </pre>
     *
     * @access hybrid
     *
     * @url GET {id}
     *
     * @param int $id Id of the repository
     *
     * @return FullRepositoryRepresentation
     *
     * @throws 404
     * @throws 403
     */
    public function get($id)
    {
        $this->checkAccess();

        $user       = $this->user_manager->getCurrentUser();
        $repository = $this->getRepository($id, $user);

        ProjectAuthorization::userCanAccessProject(
            $user,
            $repository->getProject(),
            new \URLVerification()
        );

        $this->sendAllowHeaders();

        $representation_builder = new RepositoryRepresentationBuilder(
            $this->permission_manager,
            $this->repository_manager
        );

        return $representation_builder->build($repository, $user);
    }

    /**
     * PUT SVN
     *
     * Update settings of an SVN repository. Only project admins can do this.
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"settings": {<br>
     *   &nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;"mandatory_reference": true|false ,<br>
     *   &nbsp;&nbsp;"allow_commit_message_change": true|false<br>
     *   &nbsp;&nbsp;}<br>
     *   &nbsp;}<br>
     *  }<br>
     * </pre>
     *
     * @access protected
     *
     * @url PUT {id}
     *
     * @param int $id       Id of the repository
     * @param int $settings The new settings of the SVN repository {@from body} {@type Tuleap\SVN\REST\v1\SettingsRepresentation}
     *
     * @return FullRepositoryRepresentation
     *
     * @throws 404
     * @throws 403
     */
    protected function put($id, SettingsRepresentation $settings)
    {
        $this->sendAllowHeaders();
        $this->checkAccess();

        $user       = $this->user_manager->getCurrentUser();
        $repository = $this->getRepository($id);

        ProjectAuthorization::userCanAccessProject(
            $user,
            $repository->getProject(),
            new \URLVerification()
        );

        $this->checkUserIsAdmin($repository->getProject(), $user);

        $this->repository_manager->updateHookConfig($id, $settings->commit_rules->toArray());

        $representation_builder = new RepositoryRepresentationBuilder(
            $this->permission_manager,
            $this->repository_manager
        );

        return $representation_builder->build($repository, $user);
    }

    /**
     * @return Repository
     */
    private function getRepository($id)
    {
        try {
            $repository = $this->repository_manager->getRepositoryById($id);

            if ($repository->isDeleted()) {
                throw new RestException('404', 'Repository not found');
            }

            return $repository;
        } catch (CannotFindRepositoryException $e) {
            throw new RestException('404', 'Repository not found');
        }
    }

    private function checkUserIsAdmin(Project $project, PFUser $user)
    {
        if (! $this->permission_manager->isAdmin($project, $user)) {
            throw new RestException(403, 'User must be SVN admin to do this action');
        }
    }

    /**
     * Delete SVN repository
     *
     * Delete a SVN repository
     *
     * @url DELETE {id}
     * @status 202
     * @access protected
     *
     * @param int $repository_id Id of the repository
     *
     * @throws 400
     * @throws 403
     * @throws 404
     */
    protected function delete($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $repository   = $this->getRepository($id);
            ProjectAuthorization::userCanAccessProject(
                $this->user_manager->getCurrentUser(),
                $repository->getProject(),
                new \URLVerification()
            );

            $this->checkUserIsAdmin($repository->getProject(), $current_user);

            if ($this->isDeletionAlreadyQueued($repository)) {
                throw new RestException('400', 'Repository already in queue for deletion');
                return;
            }

            $this->repository_manager->queueRepositoryDeletion($repository, \SystemEventManager::instance());
        } catch (CannotFindRepositoryException $e) {
            throw new RestException('404', 'Repository not found');
        }
    }

    private function isDeletionAlreadyQueued(Repository $repository)
    {
        return SystemEventManager::instance()->areThereMultipleEventsQueuedMatchingFirstParameter(
            'Tuleap\\Svn\\EventRepository\\' . SystemEvent_SVN_DELETE_REPOSITORY::NAME,
            $repository->getProject()->getID() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId()
        );
    }

    /**
     * @param Repository $repository
     * @param \PFUser    $user
     *
     * @return \Tuleap\SVN\REST\v1\RepositoryRepresentation
     */
    private function getRepositoryRepresentation(Repository $repository, \PFUser $user)
    {
        $representation_builder = new RepositoryRepresentationBuilder(
            $this->permission_manager,
            $this->repository_manager
        );

        return $representation_builder->build($repository, $user);
    }

    /**
     * Create a SVN repository
     *
     * Create a svn repository in a given project. User must be svn administrator to be able to create the repository.
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"project_id": 122,<br>
     *   &nbsp;"name" : "repo01",<br>
     *   &nbsp;"settings": {<br>
     *   &nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;"mandatory_reference": true|false ,<br>
     *   &nbsp;&nbsp;"allow_commit_message_change": true|false<br>
     *   &nbsp;&nbsp;}<br>
     *   &nbsp;}<br>
     *  }<br>
     * </pre>
     *
     * @url POST
     * @access protected
     * @status 201
     *
     * @param $project_id project id {@type int} {@from body}
     * @param $name Repository name {@type string} {@form body}
     * @param SettingsRepresentation $settings Repository settings {@type \Tuleap\SVN\REST\v1\SettingsRepresentation} {@required false}
     *
     * @return \Tuleap\SVN\REST\v1\RepositoryRepresentation
     * @throws 400 BadRequest Given project does not exist or project does not use SVN service
     * @throws 403 Forbidden User doesn't have permission to create a repository
     * @throws 500 Error Unable to create the repository
     * @throws 409 Repository name is invalid
     */
    protected function post($project_id, $name, SettingsRepresentation $settings)
    {
        $this->checkAccess();
        $this->options();

        $user    = $this->user_manager->getCurrentUser();
        $project = $this->project_manager->getProject($project_id);
        if ($project->isError()) {
            throw new RestException(400, "Given project does not exist");
        }

        if (! $project->usesService(\SvnPlugin::SERVICE_SHORTNAME)) {
            throw new RestException(400, "Project does not use SVN service");
        }

        ProjectAuthorization::userCanAccessProject(
            $user,
            $project,
            new \URLVerification()
        );

        if (! $this->permission_manager->isAdmin($project, $user)) {
            throw new RestException(403, "User doesn't have permission to create a repository");
        }

        $repository_to_create = new Repository("", $name, "", "", $project);
        try {
            $rule = new RuleName($project, new DAO());
            if (! $rule->isValid($name)) {
                if ($rule->getErrorMessage()) {
                    throw new RestException(409, $rule->getErrorMessage());
                }

                throw new RestException(
                    409,
                    "Repository name is invalid.  Must start by a letter, have a length of 3 characters minimum, only - _ . specials characters are allowed."
                );
            }
            $this->repository_creator->create($repository_to_create);
        } catch (CannotCreateRepositoryException $e) {
            throw new RestException(500, "Unable to create the repository");
        }

        $repository = $this->repository_manager->getRepositoryByName($project, $name);
        $this->repository_manager->updateHookConfig($repository->getId(), $settings->commit_rules->toArray());

        return $this->getRepositoryRepresentation($repository, $user);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPutDelete();
    }

    /**
     * @url OPTIONS
     *
     */
    public function options()
    {
        Header::allowOptionsPost();
    }
}
