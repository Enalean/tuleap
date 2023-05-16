<?php
/**
  * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */

use Tuleap\Git\Exceptions\DeletePluginNotInstalledException;
use Tuleap\Git\Exceptions\RepositoryNotMigratedException;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Git\GitViews\RepoManagement\Pane;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PermissionChangesDetector;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpPermissionFilter;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;

/**
 * GitActions
 * @todo call Event class instead of SystemEvent
 */
class GitActions extends PluginActions
{
    /**
     * @var PermissionChangesDetector
     */
    private $permission_changes_detector;

    /**
     * @var HistoryValueFormatter
     */
    private $history_value_formatter;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedPermissionSaver
     */
    private $fine_grained_permission_saver;

    /**
     * @var FineGrainedUpdater
     */
    private $fine_grained_updater;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Git_SystemEventManager
     */
    protected $git_system_event_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $factory;

    /**
     * @var GitRepositoryManager
     */
    private $manager;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    private $driver_factory;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    private $gerrit_usermanager;

    /** @var Git_Driver_Gerrit_ProjectCreator */
    private $project_creator;

    /** @var Git_Driver_Gerrit_Template_TemplateFactory */
    private $template_factory;

    /** @var ProjectManager */
    private $project_manager;

    /** @var GitPermissionsManager */
    private $git_permissions_manager;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /** @var ProjectHistoryDao*/
    private $history_dao;

    /** @var MigrationHandler*/
    private $migration_handler;

    /**
     * @var GerritCanMigrateChecker
     */
    private $gerrit_can_migrate_checker;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_enabler;

    /**
     * @var RegexpFineGrainedDisabler
     */
    private $regexp_disabler;

    /**
     * @var \Tuleap\Git\Permissions\RegexpPermissionFilter
     */
    private $permission_filter;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var UsersToNotifyDao
     */
    private $users_to_notify_dao;
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroups_to_notify_dao;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        Git $controller,
        Git_SystemEventManager $system_event_manager,
        GitRepositoryFactory $factory,
        GitRepositoryManager $manager,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        Git_Driver_Gerrit_ProjectCreator $project_creator,
        Git_Driver_Gerrit_Template_TemplateFactory $template_factory,
        ProjectManager $project_manager,
        GitPermissionsManager $git_permissions_manager,
        Git_GitRepositoryUrlManager $url_manager,
        \Psr\Log\LoggerInterface $logger,
        ProjectHistoryDao $history_dao,
        MigrationHandler $migration_handler,
        GerritCanMigrateChecker $gerrit_can_migrate_checker,
        FineGrainedUpdater $fine_grained_updater,
        FineGrainedPermissionSaver $fine_grained_permission_saver,
        FineGrainedRetriever $fine_grained_retriever,
        HistoryValueFormatter $history_value_formatter,
        PermissionChangesDetector $permission_changes_detector,
        RegexpFineGrainedEnabler $regexp_enabler,
        RegexpFineGrainedDisabler $regexp_disabler,
        RegexpPermissionFilter $permission_filter,
        RegexpFineGrainedRetriever $regexp_retriever,
        UsersToNotifyDao $users_to_notify_dao,
        UgroupsToNotifyDao $ugroups_to_notify_dao,
        UGroupManager $ugroup_manager,
    ) {
        parent::__construct($controller);
        $this->git_system_event_manager      = $system_event_manager;
        $this->factory                       = $factory;
        $this->manager                       = $manager;
        $this->gerrit_server_factory         = $gerrit_server_factory;
        $this->driver_factory                = $driver_factory;
        $this->gerrit_usermanager            = $gerrit_usermanager;
        $this->project_creator               = $project_creator;
        $this->template_factory              = $template_factory;
        $this->project_manager               = $project_manager;
        $this->git_permissions_manager       = $git_permissions_manager;
        $this->url_manager                   = $url_manager;
        $this->logger                        = $logger;
        $this->history_dao                   = $history_dao;
        $this->migration_handler             = $migration_handler;
        $this->gerrit_can_migrate_checker    = $gerrit_can_migrate_checker;
        $this->fine_grained_updater          = $fine_grained_updater;
        $this->fine_grained_permission_saver = $fine_grained_permission_saver;
        $this->fine_grained_retriever        = $fine_grained_retriever;
        $this->history_value_formatter       = $history_value_formatter;
        $this->permission_changes_detector   = $permission_changes_detector;
        $this->regexp_enabler                = $regexp_enabler;
        $this->regexp_disabler               = $regexp_disabler;
        $this->permission_filter             = $permission_filter;
        $this->regexp_retriever              = $regexp_retriever;
        $this->users_to_notify_dao           = $users_to_notify_dao;
        $this->ugroups_to_notify_dao         = $ugroups_to_notify_dao;
        $this->ugroup_manager                = $ugroup_manager;
    }

    public function deleteRepository($projectId, $repositoryId)
    {
        $controller   = $this->getController();
        $projectId    = intval($projectId);
        $repositoryId = intval($repositoryId);
        if (empty($projectId) || empty($repositoryId)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }

        $repository = $this->factory->getRepositoryById($repositoryId);
        if ($repository) {
            if ($repository->canBeDeleted()) {
                $this->markAsDeleted($repository);
                $controller->addInfo(sprintf(dgettext('tuleap-git', 'Repository \'%1$s\' will be removed in a few seconds'), $repository->getFullName()));
                $controller->addInfo(sprintf(dgettext('tuleap-git', 'A repository backup of \'%1$s\' will be available in the backup directory'), $repository->getFullName()) . ' : ' . $controller->getPlugin()->getConfigurationParameter('git_backup_dir'));
            } else {
                $this->getController()->addError(dgettext('tuleap-git', 'Unable to delete repository: it has child(ren)'));
                $this->redirectToRepo($repository);
                return false;
            }
        } else {
            $this->getController()->addError(dgettext('tuleap-git', 'The repository does not exist'));
        }
        $controller->redirect('/plugins/git/?action=index&group_id=' . $projectId);
    }

    private function markAsDeleted(GitRepository $repository)
    {
        $repository->markAsDeleted();
        $this->git_system_event_manager->queueRepositoryDeletion($repository);

        $this->history_dao->groupAddHistory(
            "git_repo_delete",
            $repository->getName(),
            $repository->getProjectId()
        );
    }

    /**
     * Action to load the user's repositories of a project. If user is not given, then load the project repositories instead.
     *
     * @param int $projectId The project id
     * @param int $userId    The user id. (== null for project repositories)
     *
     * @return bool true if success false otherwise
     */
    public function getProjectRepositoryList($projectId, $userId = null)
    {
        $scope = true;
        $dao   = $this->getDao();
        $this->addData([
            'repository_list'     => $dao->getProjectRepositoryList($projectId, $scope, $userId),
            'repositories_owners' => $dao->getProjectRepositoriesOwners($projectId),
        ]);
        return true;
    }

    /**
     * Generates a list of GitRepositoryWithPermissions which are migrated to a
     * gerrit server and belong to the project or the project's parent.
     */
    public function generateGerritRepositoryAndTemplateList(Project $project, PFUser $user)
    {
        $repos            = $this->factory->getAllGerritRepositoriesFromProject($project, $user);
        $templates        = $this->template_factory->getAllTemplatesOfProject($project);
        $parent_templates = $this->template_factory->getTemplatesAvailableForParentProjects($project);

        $this->addData([
            'repository_list'        => $repos,
            'templates_list'         => $templates,
            'parent_templates_list'  => $parent_templates,
            'has_gerrit_servers_set_up' => $this->gerrit_server_factory->hasRemotesSetUp(),
        ]);
    }

    protected function getDao()
    {
        return new GitDao();
    }

    /**
     * Displays the contents of the config file of a repository migrated to gerrit.
     * (used in AJAX)
     *
     * @param int $repo_id
     * @return void if error
     */
    public function fetchGitConfig($repo_id, PFUser $user, Project $project)
    {
        $git_repo = $this->getGitRepository($repo_id);

        try {
            $this->checkRepoValidity($git_repo, $project);
            $this->checkUserIsAdmin($project, $user);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e::class . $e->getTraceAsString());
            $GLOBALS['Response']->sendStatusCode($e->getCode());
            return;
        }

        $gerrit_server           = $this->gerrit_server_factory->getServerById($git_repo->getRemoteServerId());
        $git_repo_name_on_gerrit = $this->driver_factory->getDriver($gerrit_server)->getGerritProjectName($git_repo);
        $url                     = $gerrit_server->getCloneSSHUrl($git_repo_name_on_gerrit);

        try {
            $GLOBALS['Response']->sendJSON($this->project_creator->getGerritConfig($gerrit_server, $url));
        } catch (Git_Driver_Gerrit_Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'Cannot access Gerrit ' . $e->getTraceAsString());
            $GLOBALS['Response']->sendStatusCode(500);
            return;
        }
    }

    /**
     * Delete a given template
     *
     * @param int the $template_id
     */
    public function deleteGerritTemplate($template_id, Project $project, PFUser $user)
    {
        if (! $this->isUserAdmin($user, $project)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'An error occured while trying to delete the template.'));
            return;
        }

        try {
            $template = $this->template_factory->getTemplate($template_id);

            if ($template->belongsToProject((int) $project->getID())) {
                $this->template_factory->deleteTemplate($template_id);

                $this->history_dao->groupAddHistory(
                    "git_delete_template",
                    $template->getName(),
                    (int) $project->getID()
                );

                $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'Template successfully deleted.'));
                return;
            }
        } catch (Exception $e) {
        }

        $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'An error occured while trying to delete the template.'));
    }

    /**
     *
     * @param Project $project
     * @throws Git_ProjectNotFoundException
     * @throws GitRepoNotFoundException
     * @throws GitRepoNotInProjectException
     * @throws GitRepoNotOnGerritException
     */
    private function checkRepoValidity(?GitRepository $git_repo, $project)
    {
        if ($project->isError()) {
            throw new Git_ProjectNotFoundException('unable to get config');
        }

        if (! $git_repo) {
            throw new GitRepoNotFoundException('unable to get config');
        }

        if (! $git_repo->belongsToProject($project)) {
            throw new GitRepoNotInProjectException('unable to get config');
        }

        if (! $git_repo->isMigratedToGerrit()) {
            throw new GitRepoNotOnGerritException('unable to get config');
        }
    }

    /**
     * Displays the content of a template (used in AJAX)
     *
     * @param int $template_id
     * @return void
     */
    public function fetchGitTemplate($template_id, PFUser $user, Project $project)
    {
        try {
            $template = $this->template_factory->getTemplate($template_id);
            $this->checkTemplateIsAccessible($template, $project, $user);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e::class . $e->getTraceAsString());
            $GLOBALS['Response']->sendStatusCode($e->getCode());
            return;
        }

        $GLOBALS['Response']->sendJSON($template->getContent());
    }

    /**
     *
     * @throws GitUserNotAdminException
     */
    private function checkUserIsAdmin(Project $project, PFUser $user)
    {
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
             throw new GitUserNotAdminException('unable to get template');
        }

        return true;
    }

    /**
     * @throws Git_TemplateNotInProjectHierarchyException
     */
    private function checkTemplateIsAccessible(Git_Driver_Gerrit_Template_Template $template, Project $project, PFUser $user)
    {
        $template_id = $template->getId();

        foreach ($this->template_factory->getTemplatesAvailableForProject($project) as $available_template) {
            if ($available_template->getId() == $template_id) {
                $template_project = $this->project_manager->getProject($available_template->getProjectId());
                $this->checkUserIsAdmin($template_project, $user);

                return true;
            }
        }

        throw new Git_TemplateNotInProjectHierarchyException('Project not in hierarchy');
    }

    /**
     * @param string $template_content
     * @param int $template_id
     * @return void
     */
    public function updateTemplate(Project $project, PFUser $user, $template_content, $template_id)
    {
        if ($project->isError() || ! $this->checkUserIsAdmin($project, $user)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Invalid Project ID'));
            return;
        }

        if (! $template_id) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Invalid Template ID'));
            return;
        }

        try {
            $template = $this->template_factory->getTemplate($template_id);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Unable to update template'));
            return;
        }

        if (! $template->belongsToProject((int) $project->getID())) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Invalid Template ID'));
            return;
        }

        $template->setContent($template_content);

        if ($this->template_factory->updateTemplate($template)) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'Template updated'));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Unable to update template'));
        }
    }

    /**
     *
     * @param string $template_content
     * @param string $template_name
     * @return void
     */
    public function createTemplate(Project $project, PFUser $user, $template_content, $template_name)
    {
        if (! $this->checkIfProjectIsValid($project) || ! $this->isUserAdmin($user, $project)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Unable to create template'));
            return;
        }

        if ($this->template_factory->createTemplate((int) $project->getID(), $template_content, $template_name)) {
            $this->history_dao->groupAddHistory(
                "git_create_template",
                $template_name,
                (int) $project->getID()
            );

            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'Template created'));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Unable to create template'));
        }
    }

    public function getRepositoryDetails($projectId, $repositoryId)
    {
        $c            = $this->getController();
        $projectId    = intval($projectId);
        $repositoryId = intval($repositoryId);
        if (empty($repositoryId)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }
        $repository = $this->factory->getRepositoryById($repositoryId);
        if (! $repository) {
            $this->getController()->addError(dgettext('tuleap-git', 'The repository does not exist'));
            $c->redirect('/plugins/git/?action=index&group_id=' . $projectId);
            return;
        }
        $this->addData([
            'repository'         => $repository,
            'gerrit_servers'     => $this->gerrit_server_factory->getServers(),
            'driver_factory'     => $this->driver_factory,
            'gerrit_usermanager' => $this->gerrit_usermanager,
        ]);
        return true;
    }

    public function repoManagement(GitRepository $repository)
    {
        $this->addData(['repository' => $repository]);
        $this->displayFeedbacksOnRepoManagement($repository);
        $this->addData([
            'gerrit_servers'             => $this->gerrit_server_factory->getServers(),
            'driver_factory'             => $this->driver_factory,
            'gerrit_templates'           => $this->template_factory->getTemplatesAvailableForRepository($repository),
            'gerrit_can_migrate_checker' => $this->gerrit_can_migrate_checker,
        ]);
        return true;
    }

    private function displayFeedbacksOnRepoManagement(GitRepository $repository)
    {
        $project_creator_status = new Git_Driver_Gerrit_ProjectCreatorStatus(
            new Git_Driver_Gerrit_ProjectCreatorStatusDao()
        );
        if ($project_creator_status->getStatus($repository) == Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'The migration of this repository to Gerrit server is on going, please wait for a few minutes.'));
        }

        if ($this->git_system_event_manager->isProjectDeletionOnGerritOnGoing($repository)) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'The deletion of the project under Gerrit is on going, please wait for a few minutes.'));
        }

        if ($this->git_system_event_manager->isProjectSetReadOnlyOnGerritOnGoing($repository)) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'The setting of the project under Gerrit to read-only is on going, please wait for a few minutes.'));
        }
    }

    public function notificationUpdatePrefix($projectId, $repositoryId, $mailPrefix, $pane)
    {
        $controller = $this->getController();

        if (empty($repositoryId)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }

        $repository = $this->_loadRepository($projectId, $repositoryId);

        if ($repository->getMailPrefix() != $mailPrefix) {
            $repository->setMailPrefix($mailPrefix);
            $repository->save();
            $repository->changeMailPrefix();
            $controller->addInfo(dgettext('tuleap-git', 'Mail prefix updated'));
            $this->addData(['repository' => $repository]);

            $this->git_system_event_manager->queueRepositoryUpdate($repository);

            $this->history_dao->groupAddHistory(
                "git_repo_update",
                $repository->getName() . ': update notification prefix',
                $repository->getProjectId()
            );
        }

        return true;
    }

    public function notificationAddMail($projectId, $repositoryId, $mails, $pane)
    {
        $controller = $this->getController();
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if (empty($repositoryId) || empty($mails)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }

        $res = true;
        foreach ($mails as $mail) {
            if ($repository->isAlreadyNotified($mail)) {
                $res = false;
                $controller->addInfo(sprintf(dgettext('tuleap-git', 'The notification is already enabled for this email %1$s'), $mail));
            } else {
                if (! $repository->notificationAddMail($mail)) {
                    $res = false;
                    $controller->addError(sprintf(dgettext('tuleap-git', 'Could not add mail %1$s'), $mail));
                }
            }
        }

        $this->history_dao->groupAddHistory(
            "git_repo_update",
            $repository->getName() . ': add notification email',
            $repository->getProjectId()
        );

        //Display this message, just if all the entred mails have been added
        if ($res) {
            $controller->addInfo(dgettext('tuleap-git', 'Mail added'));
        }
        return true;
    }

    public function notificationAddUsers($projectId, $repository_id, array $users)
    {
        $controller = $this->getController();
        $repository = $this->_loadRepository($projectId, $repository_id);
        if (empty($repository_id) || empty($users)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }

        $user_helper   = UserHelper::instance();
        $great_success = true;
        foreach ($users as $user) {
            \assert($user instanceof PFUser);
            if ($this->users_to_notify_dao->insert($repository_id, $user->getId())) {
                $controller->addInfo(
                    sprintf(
                        dgettext('tuleap-git', 'User "%s" successfully added to notifications'),
                        $user_helper->getDisplayNameFromUser($user)
                    )
                );
                $this->history_dao->groupAddHistory(
                    "git_repo_update",
                    $repository->getName() . ': add notification user ' . $user->getUserName(),
                    $repository->getProjectId()
                );
            } else {
                $controller->addError(
                    sprintf(
                        dgettext('tuleap-git', 'Cannot add user "%s"'),
                        $user_helper->getDisplayNameFromUser($user)
                    )
                );
                $great_success = false;
            }
        }

        return $great_success;
    }

    public function notificationAddUgroups($projectId, $repository_id, array $ugroups)
    {
        $controller = $this->getController();
        $repository = $this->_loadRepository($projectId, $repository_id);
        if (empty($repository_id) || empty($ugroups)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }

        $great_success = true;
        foreach ($ugroups as $ugroup) {
            \assert($ugroup instanceof ProjectUGroup);
            if ($this->ugroups_to_notify_dao->insert($repository_id, $ugroup->getId())) {
                $controller->addInfo(
                    sprintf(
                        dgettext('tuleap-git', 'User group "%s" successfully added to notifications'),
                        $ugroup->getTranslatedName()
                    )
                );
                $this->history_dao->groupAddHistory(
                    "git_repo_update",
                    $repository->getName() . ': add notification user group ' . $ugroup->getNormalizedName(),
                    $repository->getProjectId()
                );
            } else {
                $controller->addError(
                    sprintf(
                        dgettext('tuleap-git', 'Cannot add user group "%s"'),
                        $ugroup->getTranslatedName()
                    )
                );
                $great_success = false;
            }
        }

        return $great_success;
    }

    public function notificationRemoveMail($projectId, $repositoryId, $mails, $pane)
    {
        $controller = $this->getController();
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if (empty($repositoryId) || empty($mails)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }
        $ret = true;
        foreach ($mails as $mail) {
            if ($repository->notificationRemoveMail($mail)) {
                $controller->addInfo(sprintf(dgettext('tuleap-git', 'Mail %1$s removed'), $mail));
            } else {
                $controller->addError(sprintf(dgettext('tuleap-git', 'Could not remove mail %1$s'), $mail));
                $ret = false;
            }
        }

        $this->history_dao->groupAddHistory(
            "git_repo_update",
            $repository->getName() . ': remove notification email',
            $repository->getProjectId()
        );

        return $ret;
    }

    public function notificationRemoveUser($project_id, $repository_id, array $users_to_remove)
    {
        if (empty($project_id) || empty($users_to_remove)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }
        $great_success = true;
        $controller    = $this->getController();
        $repository    = $this->_loadRepository($project_id, $repository_id);
        $user_manager  = UserManager::instance();

        foreach ($users_to_remove as $user_id) {
            $user = $user_manager->getUserById($user_id);
            if (! $user) {
                continue;
            }

            if ($this->users_to_notify_dao->delete($repository_id, $user_id)) {
                $feedback = sprintf(
                    dgettext('tuleap-git', 'User %s has been removed from notifications'),
                    $user->getUserName()
                );
                $controller->addInfo($feedback);
                $this->history_dao->groupAddHistory(
                    "git_repo_update",
                    $repository->getName() . ': remove user ' . $user->getUserName() . ' from notifications',
                    $repository->getProjectId()
                );
            } else {
                $feedback = sprintf(
                    dgettext('tuleap-git', 'Cannot remove user %s from notifications'),
                    $user->getUserName()
                );
                $controller->addError($feedback);
                $great_success = false;
            }
        }

        return $great_success;
    }

    public function notificationRemoveUgroup($project_id, $repository_id, array $ugroups_to_remove)
    {
        if (empty($project_id) || empty($ugroups_to_remove)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }
        $great_success = true;
        $controller    = $this->getController();
        $repository    = $this->_loadRepository($project_id, $repository_id);

        foreach ($ugroups_to_remove as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getUgroup($this->request->getProject(), $ugroup_id);
            if (! $ugroup) {
                continue;
            }

            if ($this->ugroups_to_notify_dao->delete($repository_id, $ugroup_id)) {
                $feedback = sprintf(
                    dgettext('tuleap-git', 'User group "%s" has been removed from notifications'),
                    $ugroup->getTranslatedName()
                );
                $controller->addInfo($feedback);
                $this->history_dao->groupAddHistory(
                    "git_repo_update",
                    $repository->getName() . ': remove user group ' . $ugroup->getNormalizedName() . ' from notifications',
                    $repository->getProjectId()
                );
            } else {
                $feedback = sprintf(
                    dgettext('tuleap-git', 'Cannot remove user group "%s" from notifications'),
                    $ugroup->getTranslatedName()
                );
                $controller->addError($feedback);
                $great_success = false;
            }
        }

        return $great_success;
    }

    public function redirectToDefaultSettings($project_id, $pane)
    {
        $this->getController()->redirect(
            GIT_BASE_URL . '/?' . http_build_query(
                [
                    'action'   => 'admin-default-settings',
                    'group_id' => $project_id,
                    'pane'     => $pane,
                ]
            )
        );
    }

    public function redirectToRepoManagement($projectId, $repositoryId, $pane)
    {
        $redirect_url = GIT_BASE_URL . '/?' . http_build_query(
            [
                'action'   => 'repo_management',
                'group_id' => $projectId,
                'repo_id'  => $repositoryId,
                'pane'     => $pane,
            ]
        );
        $this->getController()->redirect($redirect_url);
    }

    public function redirectToRepoManagementWithMigrationAccessRightInformation($projectId, $repositoryId, $pane)
    {
        $redirect_url = GIT_BASE_URL . '/?' . http_build_query(
            [
                'action'               => 'repo_management',
                'group_id'             => $projectId,
                'repo_id'              => $repositoryId,
                'pane'                 => $pane,
            ]
        );
        $this->getController()->redirect($redirect_url);
    }

    public function confirmPrivate($projectId, $repoId, $repoAccess, $repoDescription)
    {
        $c = $this->getController();
        if (empty($repoId) || empty($repoAccess) || empty($repoDescription)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }
        $repository = $this->_loadRepository($projectId, $repoId);
        if (strcmp($repoAccess, 'private') == 0 && strcmp($repository->getAccess(), $repoAccess) != 0) {
            $mailsToDelete = $repository->getNonMemberMails();
            if (! empty($mailsToDelete)) {
                $repository->setDescription($repoDescription);
                $repository->save();
                $this->addData(['repository' => $repository]);
                $this->addData(['mails' => $mailsToDelete]);
                $c->addWarn(dgettext('tuleap-git', 'Making the repository access private will remove notification for all mail addresses that doesn\'t correspond to a user member of this project.'));
                return true;
            }
        }
        $this->save($projectId, $repoId, $repoAccess, $repoDescription, false, [], [], [], false);
        return true;
    }

    public function setPrivate($projectId, $repoId)
    {
        $c = $this->getController();
        if (empty($repoId)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            return false;
        }
        $repository    = $this->_loadRepository($projectId, $repoId);
        $mailsToDelete = $repository->getNonMemberMails();
        foreach ($mailsToDelete as $mail) {
            $repository->notificationRemoveMail($mail);
        }
        $c->addInfo(dgettext('tuleap-git', 'Repository access permissions are going to be modified in a few seconds'));
    }

    private function isDisablingFineGrainedPermissions(GitRepository $repository, $enable_fine_grained_permissions)
    {
        return (
            $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)
            && ! $enable_fine_grained_permissions
        );
    }

    /**
     * This method allows one to save any repository attribues changes from the web interface.
     */
    public function save(
        $projectId,
        $repoId,
        $repoAccess,
        $pane,
        $enable_fine_grained_permissions,
        array $added_branches_permissions,
        array $added_tags_permissions,
        array $updated_permissions,
        bool $enable_regexp,
    ) {
        $controller = $this->getController();
        if (empty($repoId)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            $controller->redirect('/plugins/git/?action=index&group_id=' . $projectId);
            return false;
        }
        $repository = $this->factory->getRepositoryById($repoId);
        if ($repository === null) {
            $this->getController()->addError(dgettext('tuleap-git', 'The repository does not exist'));
            $project = $repository->getProject();
            $controller->redirect('/plugins/git/' . urlencode($project->getUnixNameLowerCase()) . '/');
            return false;
        }
        if (empty($repoAccess)) {
            $this->getController()->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
            $this->redirectToRepo($repository);
            return false;
        }

        if (
            $this->isDisablingFineGrainedPermissions($repository, $enable_fine_grained_permissions)
            && ! (isset($repoAccess[Git::PERM_WRITE]) && isset($repoAccess[Git::PERM_WPLUS]))
        ) {
            $this->getController()->addError(dgettext('tuleap-git', 'Please define both Write and Rewind permissions.'));
            $this->redirectToRepoManagement($projectId, $repoId, $pane);
            return false;
        }

        $are_there_changes = false;
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            $are_there_changes = $this->permission_changes_detector->areThereChangesInPermissionsForRepository(
                $repository,
                $repoAccess,
                $enable_fine_grained_permissions,
                $added_branches_permissions,
                $added_tags_permissions,
                $updated_permissions
            );

            $repository->getBackend()->savePermissions($repository, $repoAccess);
        } else {
            if ($repository->getAccess() != $repoAccess) {
                $controller->addInfo(dgettext('tuleap-git', 'Repository access permissions are going to be modified in a few seconds'));
            }
        }

        if ($enable_fine_grained_permissions) {
            $this->fine_grained_updater->enableRepository($repository);
        } else {
            $this->fine_grained_updater->disableRepository($repository);
        }

        $save_regexp_usage = false;
        if ($enable_regexp && $this->regexp_retriever->areRegexpActivatedForRepository($repository) === false) {
            $this->regexp_enabler->enableForRepository($repository);

            $save_regexp_usage = true;
            $action            = dgettext('tuleap-git', 'enabled');
        } elseif (! $enable_regexp && $this->regexp_retriever->areRegexpActivatedForRepository($repository) === true) {
            $this->regexp_disabler->disableForRepository($repository);
            if ($this->permission_filter->filterNonRegexpPermissions($repository)) {
                $are_there_changes = true;
            }

            $save_regexp_usage = true;
            $action            = dgettext('tuleap-git', 'disabled');
        }

        foreach ($added_branches_permissions as $added_branch_permission) {
            $this->fine_grained_permission_saver->saveBranchPermission($added_branch_permission);
        }

        foreach ($added_tags_permissions as $added_tag_permission) {
            $this->fine_grained_permission_saver->saveTagPermission($added_tag_permission);
        }

        foreach ($updated_permissions as $permission) {
            $this->fine_grained_permission_saver->updateRepositoryPermission($permission);
        }

        if ($save_regexp_usage) {
            $this->history_dao->groupAddHistory(
                'regexp_activated_for_git_repository',
                sprintf(dgettext('tuleap-git', 'Regular expression %1$s for repository %2$s.'), $action, $repository->getName()),
                $projectId,
                [$repository->getName()]
            );
        }

        if ($are_there_changes) {
            $this->history_dao->groupAddHistory(
                'perm_granted_for_git_repository',
                $this->history_value_formatter->formatValueForRepository($repository),
                $projectId,
                [$repository->getName()]
            );
        }

        $this->git_system_event_manager->queueRepositoryUpdate($repository);

        $controller->addInfo(dgettext('tuleap-git', 'Repository informations have been saved'));
        $this->redirectToRepoManagement($projectId, $repoId, $pane);
        return;
    }

    /**
     * Internal method called by SystemEvent_PROJECT_IS_PRIVATE
     * @param int $projectId
     * @param bool $isPrivate
     */
    public static function changeProjectRepositoriesAccess($projectId, $isPrivate, GitDao $dao, GitRepositoryFactory $factory)
    {
        //if the project is private, then no changes may be applied to repositories,
        //in other words only if project is set to private, its repositories have to be set to private
        if (empty($isPrivate)) {
            return;
        }
        $repositories = $dao->getProjectRepositoryList($projectId);
        foreach ($repositories as $repoId => $repoData) {
            $r = $factory->getRepositoryById($repoId);
            if (! $r) {
                continue;
            }
            if ($r->getAccess() == GitRepository::PRIVATE_ACCESS) {
                continue;
            }
            $r->setAccess(GitRepository::PRIVATE_ACCESS);
            $r->changeAccess();
            unset($r);
        }
    }

    public function _loadRepository($projectId, $repositoryId)
    {
        $repository = $this->getGitRepository($repositoryId);
        if ($repository) {
            $this->addData(['repository' => $repository]);
            return $repository;
        } else {
            $c = $this->getController();
            $this->getController()->addError(dgettext('tuleap-git', 'The repository does not exist'));
            $c->redirect('/plugins/git/?action=index&group_id=' . $projectId);
        }
    }

    /**
     * Wrapper used for tests to get a new GitRepository
     */
    public function getGitRepository($repositoryId)
    {
        return $this->factory->getRepositoryById($repositoryId);
    }

    /**
     * Fork a bunch of repositories in a project for a given user
     *
     * @param array  $repos       The array of id of repositories to fork
     * @param string $namespace       The namespace where the new repositories will live
     * @param PFUser   $user            The owner of those new repositories
     * @param Layout $response        The response object
     * @param array  $forkPermissions Permissions to be applied for the new repository
     */
    public function fork(array $repos, Project $to_project, $namespace, $scope, PFUser $user, Layout $response, $redirect_url, array $forkPermissions)
    {
        try {
            if ($this->manager->forkRepositories($repos, $to_project, $user, $namespace, $scope, $forkPermissions)) {
                $this->history_dao->groupAddHistory(
                    "git_fork_repositories",
                    (string) $to_project->getID(),
                    (int) $to_project->getID()
                );

                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-git', 'Successfully forked'));
                $response->redirect($redirect_url);
            }
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }

    /**
     * Prepare data for fork permissions action
     *
     * @param array  $repos     Repositories Ids we want to fork
     * @param array  $project   The project Id where repositories would be forked
     * @param string $namespace The namespace where the new repositories will live
     * @param string $scope     The scope of the fork: personal or cross project.
     *
     * @return void
     */
    public function forkRepositoriesPermissions($repos, $project, $namespace, $scope)
    {
        $this->addData(['repos'     => join(',', $repos),
            'group_id'  => $project,
            'namespace' => $namespace,
            'scope'     => $scope,
        ]);
    }

    public function migrateToGerrit(GitRepository $repository, $remote_server_id, $gerrit_template_id, PFUser $user)
    {
        try {
            $this->migration_handler->migrate($repository, $remote_server_id, $gerrit_template_id, $user);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function redirectToRepo(GitRepository $repository)
    {
        $this->getController()->redirect($this->url_manager->getRepositoryBaseUrl($repository));
    }

    public function disconnectFromGerrit(GitRepository $repository)
    {
        $disconnect_option = $this->request->get(Pane\Gerrit::OPTION_DISCONNECT_GERRIT_PROJECT);
        try {
            $this->migration_handler->disconnect($repository, $disconnect_option);
        } catch (RepositoryNotMigratedException $e) {
            return true;
        } catch (DeletePluginNotInstalledException $e) {
            return false;
        }
    }

    public function updateGitAdminGroups(Project $project, PFUser $user, array $selected_group_ids)
    {
        if (! $this->checkIfProjectIsValid($project) || ! $this->isUserAdmin($user, $project)) {
            return;
        }

        $selected_group_ids = $this->removeUndesiredUgroupsFromRequest($selected_group_ids);

        /** @psalm-suppress DeprecatedFunction */
        list ($return_code, $feedback) = permission_process_selection_form(
            $project->getId(),
            Git::PERM_ADMIN,
            $project->getID(),
            $selected_group_ids
        );

        if (! $return_code) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, sprintf(dgettext('tuleap-git', '%1$s'), $feedback));
            return;
        }

        $GLOBALS['Response']->addFeedback(Feedback::INFO, sprintf(dgettext('tuleap-git', '%1$s'), $feedback));

        $this->history_dao->groupAddHistory(
            "git_admin_groups",
            '',
            (int) $project->getID()
        );

        $this->redirectToGitHomePageIfUserIsNoMoreGitAdmin($user, $project);
    }

    private function redirectToGitHomePageIfUserIsNoMoreGitAdmin(PFUser $user, Project $project)
    {
        if (! $this->isUserAdmin($user, $project)) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'Further to an administration permissions update, you no-longer have access to the Git service administration.'));
            $GLOBALS['HTML']->redirect('/plugins/git/?action=index&group_id=' . $project->getId());
        }
    }

    private function removeUndesiredUgroupsFromRequest(array $selected_group_ids)
    {
        $selected_group_ids = $this->removeNobodyFromSelectGroups($selected_group_ids);
        $selected_group_ids = $this->removeAnonymousFromSelectGroups($selected_group_ids);
        $selected_group_ids = $this->removeRegisteredUsersFromSelectGroups($selected_group_ids);

        return $selected_group_ids;
    }

    private function removeNobodyFromSelectGroups(array $select_group_ids)
    {
        return array_diff($select_group_ids, [ProjectUGroup::NONE]);
    }

    private function removeAnonymousFromSelectGroups(array $select_group_ids)
    {
        return array_diff($select_group_ids, [ProjectUGroup::ANONYMOUS]);
    }

    private function removeRegisteredUsersFromSelectGroups(array $select_group_ids)
    {
        return array_diff($select_group_ids, [ProjectUGroup::REGISTERED]);
    }

    private function checkIfProjectIsValid(Project $project)
    {
        if ($project->isError()) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-git', 'Invalid Project ID'));
            return;
        }

        return true;
    }

    private function isUserAdmin(PFUser $user, Project $project)
    {
        try {
            $this->checkUserIsAdmin($project, $user);
        } catch (GitUserNotAdminException $e) {
            return;
        }

        return true;
    }

    public function restoreRepository(int $repo_id, int $project_id): never
    {
        $repository = $this->factory->getDeletedRepository($repo_id);
        $url        = '/admin/show_pending_documents.php?group_id=' . urlencode((string) $project_id);
        (new CSRFSynchronizerToken($url))->check();
        assert($GLOBALS['Response'] instanceof \Tuleap\Layout\BaseLayout);
        if (! $repository) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Unable to restore Git repository : Invalid repository id'));
            $GLOBALS['Response']->redirect($url);
        }
        $active_repository = $this->factory->getRepositoryByPath($project_id, $repository->getPath());
        if ($active_repository instanceof GitRepository) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Unable to restore Git repository : repository with the same name already exist'));
        } else {
            $this->git_system_event_manager->queueRepositoryRestore($repository);
            $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-git', 'System event created to restore repository') . ' : ' . $repository->getName());
        }
        $GLOBALS['Response']->redirect($url . '&focus=git_repository');
    }
}
