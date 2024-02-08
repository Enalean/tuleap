<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use CodendiDataAccess;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectHistoryDao;
use SystemEvent;
use SystemEventManager;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\AccessControl\CannotCreateAccessFileHistoryException;
use Tuleap\SVN\Admin\CannotCreateMailHeaderException;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\ImmutableTagListTooBigException;
use Tuleap\SVN\Admin\MailNotificationDao;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Events\SystemEvent_SVN_DELETE_REPOSITORY;
use Tuleap\SVN\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Notifications\UgroupsToNotifyDao;
use Tuleap\SVN\Notifications\UsersToNotifyDao;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Repository\Exception\CannotCreateRepositoryException;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVN\Repository\Exception\UserIsNotSVNAdministratorException;
use Tuleap\SVN\Repository\HookConfigChecker;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\HookConfigSanitizer;
use Tuleap\SVN\Repository\HookConfigUpdator;
use Tuleap\SVN\Repository\HookDao;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryDeleter;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RepositoryRegexpBuilder;
use Tuleap\SVN\Repository\Settings\Settings;
use Tuleap\SVN\Repository\Settings\SettingsBuilder;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\SVN\SvnAdmin;
use Tuleap\SVN\SvnPermissionManager;
use Tuleap\SVNCore\Repository;
use UGroupManager;

class RepositoryResource extends AuthenticatedResource
{
    /**
     * @var UserGroupRetriever
     */
    private $user_group_id_retriever;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var NotificationsEmailsBuilder
     */
    private $emails_builder;
    /**
     * @var RepositoryResourceUpdater
     */
    private $repository_updater;
    /**
     * @var ImmutableTagFactory
     */
    private $immutable_tag_factory;

    /**
     * @var RepositoryDeleter
     */
    private $repository_deleter;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    /**
     * @var SvnPermissionManager
     */
    private $permission_manager;

    /**
     * @var \UserManager
     */
    private $user_manager;

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var \ProjectManager
     */
    private $project_manager;

    /**
     * @var RepositoryCreator
     */
    private $repository_creator;

    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;

    /**
     * @var RepositoryRepresentationBuilder
     */
    private $representation_builder;

    public function __construct()
    {
        $dao                        = new Dao();
        $logger                     = \SvnPlugin::getLogger();
        $system_command             = new \System_Command();
        $backend_svn                = \Backend::instanceSVN();
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
            \EventManager::instance(),
            $backend_svn,
            new AccessFileHistoryFactory(new AccessFileHistoryDao())
        );

        $this->user_manager       = \UserManager::instance();
        $this->permission_manager = new SvnPermissionManager(
            \PermissionsManager::instance()
        );

        $this->hook_config_retriever = new HookConfigRetriever($hook_dao, new HookConfigSanitizer());
        $project_history_formatter   = new ProjectHistoryFormatter();
        $hook_config_updator         = new HookConfigUpdator(
            $hook_dao,
            $project_history_dao,
            new HookConfigChecker($this->hook_config_retriever),
            new HookConfigSanitizer(),
            $project_history_formatter
        );

        $immutable_tag_dao           = new ImmutableTagDao();
        $this->immutable_tag_factory = new ImmutableTagFactory($immutable_tag_dao);
        $immutable_tag_creator       = new ImmutableTagCreator(
            $immutable_tag_dao,
            $project_history_formatter,
            $project_history_dao,
            $this->immutable_tag_factory
        );
        $access_file_history_factory = new AccessFileHistoryFactory(new AccessFileHistoryDao());
        $access_file_history_creator = new AccessFileHistoryCreator(
            new AccessFileHistoryDao(),
            $access_file_history_factory,
            $project_history_dao,
            $project_history_formatter,
            \Tuleap\SVNCore\SVNAccessFileDefaultBlockGenerator::instance(),
            new \Tuleap\SVN\Repository\DefaultPermissionsDao(),
        );

        $this->ugroup_manager = new UGroupManager();

        $project_history_formatter = new ProjectHistoryFormatter();
        $this->emails_builder      = new NotificationsEmailsBuilder();
        $mail_notification_manager = new MailNotificationManager(
            new MailNotificationDao(CodendiDataAccess::instance(), new RepositoryRegexpBuilder()),
            new UsersToNotifyDao(),
            new UgroupsToNotifyDao(),
            $project_history_dao,
            $this->emails_builder,
            $this->ugroup_manager
        );

        $this->repository_creator = new RepositoryCreator(
            $dao,
            $this->system_event_manager,
            $project_history_dao,
            $this->permission_manager,
            $hook_config_updator,
            $project_history_formatter,
            $immutable_tag_creator,
            $access_file_history_creator,
            $mail_notification_manager
        );

        $user_to_notify_dao           = new UsersToNotifyDao();
        $ugroup_to_notify_dao         = new UgroupsToNotifyDao();
        $this->representation_builder = new RepositoryRepresentationBuilder(
            $this->permission_manager,
            $this->hook_config_retriever,
            $this->immutable_tag_factory,
            $access_file_history_factory,
            $mail_notification_manager,
            new NotificationsBuilder(
                $user_to_notify_dao,
                $this->user_manager,
                $ugroup_to_notify_dao,
                $this->ugroup_manager
            )
        );

        $this->repository_deleter = new RepositoryDeleter(
            new \System_Command(),
            $project_history_dao,
            $dao,
            $this->system_event_manager,
            $this->repository_manager
        );

        $this->repository_updater = new RepositoryResourceUpdater(
            $hook_config_updator,
            $immutable_tag_creator,
            $access_file_history_factory,
            $access_file_history_creator,
            $this->immutable_tag_factory,
            $mail_notification_manager,
            new NotificationUpdateChecker(
                $mail_notification_manager,
                new EmailsToBeNotifiedRetriever(
                    $mail_notification_manager
                )
            )
        );

        $this->user_group_id_retriever = new UserGroupRetriever(new UGroupManager());
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
     *   &nbsp;&nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_reference_mandatory": true ,<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_commit_message_change_allowed": true<br>
     *   &nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;"immutable_tags": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"paths": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags1",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags2"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"whitelist": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags/whitelist1",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags/whitelist2"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;"access_file": "[/] * = rw @members = rw\r\n[/tags] @admins = rw",<br>
     *   &nbsp;&nbsp;&nbsp;"email_notifications": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"path": "trunk",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"user_groups": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"id": "101_3",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"uri": "user_groups/101_3",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"label": "Project members"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"users": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"id": "333",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"uri": "/users/333",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"username": "..."<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"emails": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"foo@example.com",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"bar@example.com"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;}<br>
     *   &nbsp;&nbsp;},<br>
     *   &nbsp;"has_default_permissions": true<br>
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
     * @throws RestException 404
     * @throws RestException 403
     */
    public function get($id)
    {
        $this->checkAccess();

        $user       = $this->user_manager->getCurrentUser();
        $repository = $this->getRepository($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $repository->getProject()
        );

        ProjectAuthorization::userCanAccessProject(
            $user,
            $repository->getProject(),
            new \URLVerification()
        );

        $this->sendAllowHeaders();

        return $this->representation_builder->build($repository, $user);
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
     *   &nbsp;&nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_reference_mandatory": true ,<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_commit_message_change_allowed": true<br>
     *   &nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;"immutable_tags": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"paths": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags1",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags2"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"whitelist": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags/whitelist1",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags/whitelist2"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;"access_file": "[/] * = rw @members = rw\r\n[/tags] @admins = rw",<br>
     *   &nbsp;&nbsp;&nbsp;"email_notifications": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"path": "/trunk",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"emails": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"foo@example.com",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"bar@example.com"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"users": [],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"user_groups": []<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"path": "/tags",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"emails": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"foo@example.com"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"users": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"102"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"user_groups": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"101_3",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"105"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
     *   &nbsp;&nbsp;&nbsp;]<br>,
     *   &nbsp;&nbsp;&nbsp;"has_default_permissions": true,
     *   &nbsp;},<br>
     *  }<br>
     * </pre>
     *
     * @access protected
     *
     * @url PUT {id}
     *
     * @param int $id Id of the repository
     * @param SettingsPUTRepresentation $settings The new settings of the SVN repository {@from body} {@type \Tuleap\SVN\REST\v1\SettingsPUTRepresentation}
     *
     * @return FullRepositoryRepresentation
     *
     * @throws RestException 404
     * @throws RestException 403
     */
    protected function put(int $id, SettingsPUTRepresentation $settings)
    {
        $this->sendAllowHeaders();
        $this->checkAccess();

        $user       = $this->user_manager->getCurrentUser();
        $repository = $this->getRepository($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository->getProject()
        );

        ProjectAuthorization::userCanAccessProject(
            $user,
            $repository->getProject(),
            new \URLVerification()
        );

        $this->checkUserIsAdmin($repository->getProject(), $user);

        $repository_settings = $this->getSettings($repository, $settings);

        try {
            $this->repository_updater->update($repository, $repository_settings);
        } catch (ImmutableTagListTooBigException $exception) {
            throw new RestException(400, $exception->getMessage(), [], $exception);
        } catch (CannotCreateMailHeaderException $exception) {
            throw new RestException(500, 'An error occurred while saving the notifications.');
        }

        return $this->representation_builder->build($repository, $user);
    }

    /**
     * @return Repository
     */
    private function getRepository($id)
    {
        try {
            $repository = $this->repository_manager->getRepositoryById($id);

            if ($repository->isDeleted()) {
                throw new RestException(404, 'Repository not found');
            }

            return $repository;
        } catch (CannotFindRepositoryException $e) {
            throw new RestException(404, 'Repository not found');
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
     * @param int $id Id of the repository
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function delete($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $repository   = $this->getRepository($id);

            ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
                $repository->getProject()
            );

            ProjectAuthorization::userCanAccessProject(
                $this->user_manager->getCurrentUser(),
                $repository->getProject(),
                new \URLVerification()
            );

            $this->checkUserIsAdmin($repository->getProject(), $current_user);

            if ($this->isDeletionAlreadyQueued($repository)) {
                throw new RestException(400, 'Repository already in queue for deletion');
            }

            $this->repository_deleter->queueRepositoryDeletion($repository);
        } catch (CannotFindRepositoryException $e) {
            throw new RestException(404, 'Repository not found');
        }
    }

    private function isDeletionAlreadyQueued(Repository $repository)
    {
        return SystemEventManager::instance()->areThereMultipleEventsQueuedMatchingFirstParameter(
            'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_DELETE_REPOSITORY::NAME,
            $repository->getProject()->getID() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId()
        );
    }

    /**
     *
     * @return \Tuleap\SVN\REST\v1\RepositoryRepresentation
     */
    private function getRepositoryRepresentation(Repository $repository, \PFUser $user)
    {
        return $this->representation_builder->build($repository, $user);
    }

    /**
     * Create a SVN repository
     *
     * Create a svn repository in a given project. User must be svn administrator to be able to create the repository.
     *
     * <br>
     * <br>
     * A project admin can create an SVN repository like this:
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"project_id": 122,<br>
     *   &nbsp;"name" : "repo01"<br>
     *  }<br>
     * </pre>
     * <br>
     * <br>
     * In addition, the admin can create a repository with custom settings:
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;&nbsp;"project_id": 122,<br>
     *   &nbsp;&nbsp;"name" : "repo01",<br>
     *   &nbsp;&nbsp;"settings": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;"commit_rules": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_reference_mandatory": true,<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_commit_message_change_allowed": false<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;"immutable_tags": {<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"paths": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags1",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags2"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"whitelist": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags/whitelist1",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags/whitelist2"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;"layout": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/trunk",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"/tags"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp; ],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;"has_default_permissions": true,<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;"access_file": "[/] * = rw \r\n@members = rw\r\n[/tags] @admins = rw",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;"email_notifications": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"path": "/trunk",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"emails": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"foo@example.com",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"bar@example.com"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"users": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;102,<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;103<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"user_groups": []<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;},<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"path": "/tags",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"emails": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"foo@example.com"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"users": [],<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"user_groups": [<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"101_3",<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"102"<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
     *   &nbsp;&nbsp;&nbsp;&nbsp;]<br>
     *   &nbsp;&nbsp;}<br>
     *  }<br>
     * </pre>
     *
     * @url POST
     * @access protected
     * @status 201
     *
     * @param int $project_id project id {@type int} {@from body}
     * @param string $name Repository name {@type string} {@form body}
     * @param SettingsPOSTRepresentation $settings Repository settings {@type \Tuleap\SVN\REST\v1\SettingsPOSTRepresentation} {@required false}
     *
     * @return \Tuleap\SVN\REST\v1\RepositoryRepresentation
     * @throws RestException 400 BadRequest Given project does not exist or project does not use SVN service
     * @throws RestException 403 Forbidden User doesn't have permission to create a repository
     * @throws RestException 500 Error Unable to create the repository
     * @throws RestException 409 Repository name is invalid
     */
    protected function post(int $project_id, string $name, ?SettingsPOSTRepresentation $settings = null)
    {
        $this->checkAccess();
        $this->options();

        $user    = $this->user_manager->getCurrentUser();
        $project = $this->project_manager->getProject($project_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $project
        );

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

        $repository_to_create = SvnRepository::buildToBeCreatedRepository($name, $project);
        try {
            $repository_settings       = $this->getPOSTSettings($repository_to_create, $settings);
            $has_initial_layout        = $settings !== null && $settings->layout !== null;
            $initial_repository_layout = $has_initial_layout ? $settings->layout : [];

            $this->repository_creator->createWithSettings(
                $repository_to_create,
                $user,
                $repository_settings,
                $initial_repository_layout,
            );
        } catch (ImmutableTagListTooBigException $exception) {
            throw new RestException(400, $exception->getMessage(), [], $exception);
        } catch (CannotCreateRepositoryException $e) {
            throw new RestException(500, "Unable to create the repository");
        } catch (UserIsNotSVNAdministratorException $e) {
            throw new RestException(403, "User doesn't have permission to create a repository");
        } catch (RepositoryNameIsInvalidException $e) {
            throw new RestException(409, $e->getMessage());
        } catch (CannotCreateAccessFileHistoryException $e) {
            throw new RestException(500, "Unable to store access file");
        }

        $repository = $this->repository_manager->getRepositoryByName($project, $name);

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

    private function getSettings(
        Repository $repository,
        SettingsPUTRepresentation $settings,
    ): Settings {
        return $this->extractSettingsFromRepresentation($repository, $settings);
    }

    private function getPOSTSettings(Repository $repository, ?SettingsPOSTRepresentation $settings = null): Settings
    {
        return $this->extractSettingsFromRepresentation($repository, $settings);
    }

    /**
     * @throws RestException
     */
    private function extractSettingsFromRepresentation(
        Repository $repository,
        SettingsPOSTRepresentation | SettingsPUTRepresentation | null $settings_representation = null,
    ): Settings {
        $builder = new SettingsBuilder(
            $this->immutable_tag_factory,
            $this->user_manager,
            $this->user_group_id_retriever,
        );

        return $builder->buildFromPOSTPUTRESTRepresentation(
            $repository,
            $settings_representation,
        )->match(
            static fn (Settings $settings): Settings => $settings,
            function (Fault $fault): void {
                throw new RestException(
                    400,
                    (string) $fault,
                );
            }
        );
    }
}
