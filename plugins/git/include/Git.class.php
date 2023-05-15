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
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

use Tuleap\Git\DefaultBranch\CannotSetANonExistingBranchAsDefaultException;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdater;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\PathJoinUtil;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionDestructor;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PermissionChangesDetector;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpPermissionFilter;
use Tuleap\Git\Permissions\TemplatePermissionsUpdater;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\Repository\DescriptionUpdater;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\User\InvalidEntryInAutocompleterCollection;
use Tuleap\User\RequestFromAutocompleter;

/**
 * Git
 */
class Git extends PluginController
{
    /**
     * @var DescriptionUpdater
     */
    private $description_updater;
    private DefaultBranchUpdater $default_branch_updater;

    public const PERM_READ  = 'PLUGIN_GIT_READ';
    public const PERM_WRITE = 'PLUGIN_GIT_WRITE';
    public const PERM_WPLUS = 'PLUGIN_GIT_WPLUS';

    public const READ_PERM = 'R';

    public const DEFAULT_PERM_READ  = 'PLUGIN_GIT_DEFAULT_READ';
    public const DEFAULT_PERM_WRITE = 'PLUGIN_GIT_DEFAULT_WRITE';
    public const DEFAULT_PERM_WPLUS = 'PLUGIN_GIT_DEFAULT_WPLUS';

    public const PERM_ADMIN         = 'PLUGIN_GIT_ADMIN';
    public const SPECIAL_PERM_ADMIN = 'PROJECT_ADMIN';

    public const SCOPE_PERSONAL = 'personal';

    public const REFERENCE_KEYWORD = 'git';
    public const REFERENCE_NATURE  = 'git_commit';

    public const TAG_REFERENCE_KEYWORD = 'git_tag';
    public const TAG_REFERENCE_NATURE  = 'git_tag';

    public const DEFAULT_GIT_PERMS_GRANTED_FOR_PROJECT = 'default_git_perms_granted_for_project';

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_enabler;
    /**
     * @var RegexpFineGrainedDisabler
     */
    private $regexp_disabler;
    /**
     * @var RegexpPermissionFilter
     */
    private $regexp_filter;

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
    /**
     * @var Git_Driver_Gerrit_Template_TemplateFactory
     */
    private $template_factory;
    private VerifyArtifactClosureIsAllowed $closure_verifier;
    private ConfigureAllowArtifactClosure $configure_artifact_closure;
    private string $action;

    /**
     * Lists all git-related permission types.
     *
     * @return array
     */
    public static function allPermissionTypes()
    {
        return [self::PERM_READ, self::PERM_WRITE, self::PERM_WPLUS];
    }

    /**
     * @return array
     */
    public static function allDefaultPermissionTypes()
    {
        return [self::DEFAULT_PERM_READ, self::DEFAULT_PERM_WRITE, self::DEFAULT_PERM_WPLUS];
    }

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    protected $groupId;

    /**
     * @var GitRepositoryFactory
     */
    protected $factory;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * @var GitPlugin
     */
    private $plugin;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    private $driver_factory;

    /** @var GitRepositoryManager */
    private $repository_manager;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    private $gerrit_usermanager;

    /** @var Git_Driver_Gerrit_ProjectCreator */
    private $project_creator;

    /** @var GitPermissionsManager */
    private $permissions_manager;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /** @var Project */
    private $project;

    /**
     * @var Git_Driver_Gerrit_ProjectCreatorStatus
     */
    private $project_creator_status;

    /**
     * @var GerritCanMigrateChecker
     */
    private $gerrit_can_migrate_checker;

    /**
     * @var FineGrainedUpdater
     */
    private $fine_grained_updater;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_permission_factory;

    /**
     * @var FineGrainedPermissionSaver
     */
    private $fine_grained_permission_saver;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_permission_factory;

    /**
     * @var FineGrainedPermissionDestructor
     */
    private $fine_grained_permission_destructor;

    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;

    /**
     * @var HistoryValueFormatter
     */
    private $history_value_formatter;

    /**
     * @var PermissionChangesDetector
     */
    private $permission_changes_detector;

    /**
     * @var TemplatePermissionsUpdater
     */
    private $template_permission_updater;

    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    /**
     * @var HeaderRenderer
     */
    private $header_renderer;

    public function __construct(
        GitPlugin $plugin,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        GitRepositoryManager $repository_manager,
        Git_SystemEventManager $system_event_manager,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        GitRepositoryFactory $git_repository_factory,
        UserManager $user_manager,
        ProjectManager $project_manager,
        HTTPRequest $request,
        Git_Driver_Gerrit_ProjectCreator $project_creator,
        Git_Driver_Gerrit_Template_TemplateFactory $template_factory,
        GitPermissionsManager $permissions_manager,
        Git_GitRepositoryUrlManager $url_manager,
        \Psr\Log\LoggerInterface $logger,
        Git_Driver_Gerrit_ProjectCreatorStatus $project_creator_status,
        GerritCanMigrateChecker $gerrit_can_migrate_checker,
        FineGrainedUpdater $fine_grained_updater,
        FineGrainedPermissionFactory $fine_grained_permission_factory,
        FineGrainedRetriever $fine_grained_retriever,
        FineGrainedPermissionSaver $fine_grained_permission_saver,
        DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        FineGrainedPermissionDestructor $fine_grained_permission_destructor,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        HistoryValueFormatter $history_value_formatter,
        PermissionChangesDetector $permission_changes_detector,
        TemplatePermissionsUpdater $template_permission_updater,
        ProjectHistoryDao $history_dao,
        DefaultBranchUpdater $default_branch_updater,
        DescriptionUpdater $description_updater,
        RegexpFineGrainedRetriever $regexp_retriever,
        RegexpFineGrainedEnabler $regexp_enabler,
        RegexpFineGrainedDisabler $regexp_disabler,
        RegexpPermissionFilter $regexp_filter,
        UsersToNotifyDao $users_to_notify_dao,
        UgroupsToNotifyDao $ugroups_to_notify_dao,
        UGroupManager $ugroup_manager,
        HeaderRenderer $header_renderer,
        VerifyArtifactClosureIsAllowed $closure_verifier,
        ConfigureAllowArtifactClosure $configure_artifact_closure,
    ) {
        parent::__construct($user_manager, $request);

        $this->userManager                = $user_manager;
        $this->projectManager             = $project_manager;
        $this->factory                    = $git_repository_factory;
        $this->gerrit_server_factory      = $gerrit_server_factory;
        $this->driver_factory             = $driver_factory;
        $this->repository_manager         = $repository_manager;
        $this->git_system_event_manager   = $system_event_manager;
        $this->gerrit_usermanager         = $gerrit_usermanager;
        $this->project_creator            = $project_creator;
        $this->template_factory           = $template_factory;
        $this->permissions_manager        = $permissions_manager;
        $this->plugin                     = $plugin;
        $this->url_manager                = $url_manager;
        $this->logger                     = $logger;
        $this->project_creator_status     = $project_creator_status;
        $this->gerrit_can_migrate_checker = $gerrit_can_migrate_checker;

        $valid = new Valid_GroupId('group_id');
        $valid->required();
        if ($this->request->valid($valid)) {
            $this->groupId = (int) $this->request->get('group_id');
        }
        $valid = new Valid_String('action');
        $valid->required();
        if ($this->request->valid($valid)) {
            $this->action = $this->request->get('action');
        }

        if (empty($this->action)) {
            $this->action = 'index';
        }

        $this->project = $this->projectManager->getProject($this->groupId);

        $this->permittedActions                = [];
        $this->fine_grained_updater            = $fine_grained_updater;
        $this->fine_grained_permission_factory = $fine_grained_permission_factory;
        $this->fine_grained_retriever          = $fine_grained_retriever;
        $this->fine_grained_permission_saver   = $fine_grained_permission_saver;

        $this->default_fine_grained_permission_factory = $default_fine_grained_permission_factory;
        $this->fine_grained_permission_destructor      = $fine_grained_permission_destructor;
        $this->fine_grained_builder                    = $fine_grained_builder;
        $this->history_value_formatter                 = $history_value_formatter;
        $this->permission_changes_detector             = $permission_changes_detector;
        $this->template_permission_updater             = $template_permission_updater;
        $this->history_dao                             = $history_dao;
        $this->default_branch_updater                  = $default_branch_updater;
        $this->description_updater                     = $description_updater;
        $this->regexp_retriever                        = $regexp_retriever;
        $this->regexp_enabler                          = $regexp_enabler;
        $this->regexp_disabler                         = $regexp_disabler;
        $this->regexp_filter                           = $regexp_filter;
        $this->users_to_notify_dao                     = $users_to_notify_dao;
        $this->ugroups_to_notify_dao                   = $ugroups_to_notify_dao;
        $this->ugroup_manager                          = $ugroup_manager;
        $this->header_renderer                         = $header_renderer;
        $this->closure_verifier                        = $closure_verifier;
        $this->configure_artifact_closure              = $configure_artifact_closure;
    }

    protected function instantiateView()
    {
        return new GitViews(
            $this,
            $this->permissions_manager,
            $this->fine_grained_permission_factory,
            $this->fine_grained_retriever,
            $this->default_fine_grained_permission_factory,
            $this->fine_grained_builder,
            $this->regexp_retriever,
            $this->gerrit_server_factory,
            $this->header_renderer,
            $this->projectManager,
            $this->closure_verifier,
        );
    }

    public function setPermissionsManager(GitPermissionsManager $permissions_manager)
    {
        $this->permissions_manager = $permissions_manager;
    }

    public function setProjectManager($projectManager)
    {
        $this->projectManager = $projectManager;
    }

    public function setFactory(GitRepositoryFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setRequest(HTTPRequest $request)
    {
        $this->request = $request;
    }

    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @deprecated For old unit tests only, do NOT use it otherwise.
     */
    public function setProject(Project $project)
    {
        $this->groupId = $project->getID();
        $this->project = $project;
    }

    public function setPermittedActions($permittedActions)
    {
        $this->permittedActions = $permittedActions;
    }

    /**
     * @return GitPlugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    protected function definePermittedActions(/* GitRepository */ $repository, $user)
    {
        if ($this->permissions_manager->userIsGitAdmin($user, $this->projectManager->getProject($this->groupId))) {
            $this->permittedActions = [
                'index',
                'view' ,
                'edit',
                'edit-general-settings',
                'clone',
                'add',
                'del',
                'confirm_deletion',
                'save',
                'repo_management',
                'mail',
                'fork',
                'set_private',
                'confirm_private',
                'fork_repositories',
                'admin',
                'admin-git-admins',
                'admin-gerrit-templates',
                'admin-default-access-rights',
                'delete-permissions',
                'delete-default-permissions',
                'fetch_git_config',
                'fetch_git_template',
                'fork_repositories_permissions',
                'do_fork_repositories',
                'view_last_git_pushes',
                'migrate_to_gerrit',
                'disconnect_gerrit',
                'delete_gerrit_project',
            ];
            if ($user->isSuperUser()) {
                $this->permittedActions[] = 'restore';
            }
        } else {
            $this->addPermittedAction('index');
            $this->addPermittedAction('view_last_git_pushes');
            $this->addPermittedAction('fork_repositories');
            $this->addPermittedAction('fork_repositories_permissions');
            $this->addPermittedAction('do_fork_repositories');

            if ($repository && $repository->userCanRead($user)) {
                $this->addPermittedAction('view');
                $this->addPermittedAction('edit');
                $this->addPermittedAction('edit-general-settings');
                $this->addPermittedAction('clone');
                if ($repository->belongsTo($user)) {
                    $this->addPermittedAction('repo_management');
                    $this->addPermittedAction('mail');
                    $this->addPermittedAction('del');
                    $this->addPermittedAction('confirm_deletion');
                    $this->addPermittedAction('save');
                }
            }
        }

        $this->addAdditionalPermittedActions($user, $repository);
    }

    private function addAdditionalPermittedActions(PFUser $user, /* GitRepository */ $repository)
    {
        $permitted_actions = [];
        $params            = [
            'repository'        => $repository,
            'user'              => $user,
            'permitted_actions' => &$permitted_actions,
        ];

        EventManager::instance()->processEvent(GIT_ADDITIONAL_PERMITTED_ACTIONS, $params);

        foreach ($permitted_actions as $permitted_action) {
            $this->addPermittedAction($permitted_action);
        }
    }

    public function request()
    {
        $valid = new Valid_String('repo_name');
        $valid->required();
        $repositoryName = null;
        if ($this->request->valid($valid)) {
            $repositoryName = trim($this->request->get('repo_name'));
        }
        $valid = new Valid_UInt('repo_id');
        $valid->required();
        if ($this->request->valid($valid)) {
            $repoId = $this->request->get('repo_id');
        } else {
            $repoId = 0;
        }

        $user = $this->userManager->getCurrentUser();

        $repository = null;
        if ($repoId !== 0) {
            $repository = $this->factory->getRepositoryById($repoId);
        }

        //define access permissions
        $this->definePermittedActions($repository, $user);

        //check permissions
        if (empty($this->permittedActions) || ! $this->isAPermittedAction($this->action)) {
            $this->addError(dgettext('tuleap-git', 'You are not allowed to access this page'));
            $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
            return;
        }

        $this->_informAboutPendingEvents($repository);

        $this->_dispatchActionAndView($this->action, $repository, $repoId, $repositoryName, $user);
    }

    public function _dispatchActionAndView($action, /* GitRepository */ $repository, $repo_id, $repositoryName, $user)
    {
        $pane = $this->request->get('pane');
        switch ($action) {
             // DELETE a repository
            case 'del':
                $this->defaultCSRFChecks($repository, 'delete');
                $this->addAction('deleteRepository', [$this->groupId, $repository->getId()]);
                $this->addView('index');
                break;
            // EDIT
            case 'edit-general-settings':
                $this->defaultCSRFChecks($repository, 'settings');
                if ($this->request->exist('repo-default-branch')) {
                    try {
                        $this->default_branch_updater->updateDefaultBranch(
                            Git_Exec::buildFromRepository($repository),
                            $this->request->get('repo-default-branch')
                        );
                        $this->addInfo(dgettext('tuleap-git', 'Default branch successfully updated'));
                    } catch (CannotSetANonExistingBranchAsDefaultException $exception) {
                        $this->addError(dgettext('tuleap-git', 'The update of the default branch did not succeed'));
                    }
                }

                if ($this->request->exist('allow-artifact-closure')) {
                    $repository_id                         = (int) $repository->getId();
                    $is_artifact_closure_currently_allowed = $this->closure_verifier->isArtifactClosureAllowed($repository_id);
                    if ($this->request->get('allow-artifact-closure') === '1') {
                        if (! $is_artifact_closure_currently_allowed) {
                            $this->configure_artifact_closure->allowArtifactClosureForRepository($repository_id);
                            $this->addInfo(dgettext('tuleap-git', 'Artifact closure is now allowed for repository'));
                        }
                    } elseif ($is_artifact_closure_currently_allowed) {
                        $this->configure_artifact_closure->forbidArtifactClosureForRepository($repository_id);
                        $this->addInfo(dgettext('tuleap-git', 'Artifact closure is not allowed anymore for repository'));
                    }
                }

                if ($this->request->exist('repo_desc')) {
                    $description       = GitRepository::DEFAULT_DESCRIPTION;
                    $valid_descrpition = new Valid_Text('repo_desc');
                    $valid_descrpition->required();
                    if ($this->request->valid($valid_descrpition)) {
                        $description = $this->request->get('repo_desc');
                    }

                    $this->description_updater->updateDescription($repository, $description);
                }
                break;
            case 'edit':
                if (empty($repository)) {
                    $this->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
                    $this->redirect('/plugins/git/?action=index&group_id=' . $this->groupId);
                    return false;
                }
                if ($this->isAPermittedAction('clone') && $this->request->get('clone')) {
                    $valid_url = new Valid_UInt('parent_id');
                    $valid_url->required();
                    if ($this->request->valid($valid_url)) {
                        $parentId = (int) $this->request->get('parent_id');
                        $this->addAction('cloneRepository', [$this->groupId, $repositoryName, $parentId]);
                        $this->addAction('getRepositoryDetails', [$this->groupId, $parentId]);
                    }
                    $this->addView('view');
                } elseif ($this->isAPermittedAction('save') && $this->request->get('save')) {
                    $this->defaultCSRFChecks($repository, 'perms');
                    $repoAccess = null;
                    $valid_url  = new Valid_String('repo_access');
                    $valid_url->required();
                    if ($this->request->valid($valid_url) || is_array($this->request->get('repo_access'))) {
                        $repoAccess = $this->request->get('repo_access');
                    }

                    $enable_fine_grained_permissions = $this->request->exist('use-fine-grained-permissions');

                    $fine_grained_permissions_activated = $enable_fine_grained_permissions &&
                        ! $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository);

                    $current_permissions = $this->fine_grained_permission_factory->getBranchesFineGrainedPermissionsForRepository($repository)
                        + $this->fine_grained_permission_factory->getTagsFineGrainedPermissionsForRepository($repository);

                    $use_regexp = $this->request->exist('use-regexp');

                    $updated_permissions        = [];
                    $added_tags_permissions     = [];
                    $added_branches_permissions = [];

                    if ($fine_grained_permissions_activated && count($current_permissions) === 0) {
                        $added_tags_permissions     = $this->fine_grained_permission_factory->getDefaultTagsFineGrainedPermissionsForRepository($repository);
                        $added_branches_permissions = $this->fine_grained_permission_factory->getDefaultBranchesFineGrainedPermissionsForRepository($repository);
                    } else {
                        $added_tags_permissions = $this->fine_grained_permission_factory->getTagsFineGrainedPermissionsFromRequest(
                            $this->request,
                            $repository
                        );

                        $added_branches_permissions = $this->fine_grained_permission_factory->getBranchesFineGrainedPermissionsFromRequest(
                            $this->request,
                            $repository
                        );

                        if ($enable_fine_grained_permissions && ! $fine_grained_permissions_activated) {
                            $updated_permissions = $this->fine_grained_permission_factory->getUpdatedPermissionsFromRequest(
                                $this->request,
                                $repository
                            );
                        }
                    }

                    $this->addAction(
                        'save',
                        [
                            $this->groupId,
                            $repository->getId(),
                            $repoAccess,
                            $pane,
                            $enable_fine_grained_permissions,
                            $added_branches_permissions,
                            $added_tags_permissions,
                            $updated_permissions,
                            $use_regexp,
                        ]
                    );
                    $this->addView('view');
                } else {
                    $this->addError(dgettext('tuleap-git', 'You are not allowed to access this page'));
                    $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
                }
                break;
            // repo_management
            case 'repo_management':
                if (empty($repository)) {
                    $this->redirectNoRepositoryError();
                    return false;
                }
                $this->addAction('repoManagement', [$repository]);
                $this->setDefaultPageRendering(false);
                $this->addView('repoManagement');
                break;
            case 'mail':
                $this->defaultCSRFChecks($repository, 'mail');
                $this->processRepoManagementNotifications($pane, $repository->getId(), $repositoryName, $user);
                break;
            // fork
            case 'fork':
                $this->addAction('repoManagement', [$this->groupId, $repository->getId()]);
                $this->addView('forkRepositories');
                break;
            // confirm_private
            case 'confirm_private':
                if ($this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion')) {
                    $this->addAction('confirmDeletion', [$this->groupId, $repository]);
                    $this->addView('confirm_deletion', [0 => ['repo_id' => $repository->getId()]]);
                } elseif ($this->isAPermittedAction('save') && $this->request->get('save')) {
                    $valid_url = new Valid_Text('repo_desc');
                    $valid_url->required();
                    if ($this->request->valid($valid_url)) {
                        $description = $this->request->get('repo_desc');
                    }
                    $valid_url = new Valid_String('repo_access');
                    $valid_url->required();
                    if ($this->request->valid($valid_url)) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('confirmPrivate', [$this->groupId, $repository->getId(), $repoAccess, $description]);
                    $this->addView('confirmPrivate');
                }
                break;
             // SET TO PRIVATE
            case 'set_private':
                $this->addAction('setPrivate', [$this->groupId, $repository->getId()]);
                $this->addView('view');
                break;
            case 'fork_repositories':
                $this->addAction('getProjectRepositoryList', [$this->groupId]);
                $this->addView('forkRepositories');
                break;
            case 'admin-git-admins':
                if ($this->request->get('submit')) {
                    $this->defaultProjectAdminCSRFChecks($action);
                    $valid_url = new Valid_Numeric(GitPresenters_AdminGitAdminsPresenter::GIT_ADMIN_SELECTBOX_NAME);
                    $project   = $this->projectManager->getProject($this->groupId);

                    if ($this->request->validArray($valid_url)) {
                        $select_project_ids = $this->request->get(GitPresenters_AdminGitAdminsPresenter::GIT_ADMIN_SELECTBOX_NAME);

                        if ($select_project_ids) {
                            $this->addAction('updateGitAdminGroups', [$project, $user, $select_project_ids]);
                        } else {
                            $this->addError(dgettext('tuleap-git', 'No data retrieved from the request'));
                        }
                    } else {
                        $this->addError(dgettext('tuleap-git', 'The request is not valid.'));
                    }
                }

                $this->setDefaultPageRendering(false);
                $this->addView(
                    'adminGitAdminsView',
                );

                break;
            case 'admin':
            case 'admin-gerrit-templates':
                $project = $this->projectManager->getProject($this->groupId);

                if ($this->request->get('save')) {
                    $this->defaultProjectAdminCSRFChecks('admin-gerrit-templates');
                    $template_content = $this->request->getValidated('git_admin_config_data', 'text');
                    if ($this->request->getValidated('git_admin_template_id', 'uint')) {
                        $template_id = $this->request->get('git_admin_template_id');
                        $this->addAction('updateTemplate', [$project, $user, $template_content, $template_id]);
                    } else {
                        $template_name = $this->request->getValidated('git_admin_file_name', 'string');
                        $this->addAction('createTemplate', [$project, $user, $template_content, $template_name]);
                    }
                }

                if ($this->request->get('delete')) {
                    $this->defaultProjectAdminCSRFChecks('admin-gerrit-templates');
                    if ($this->request->getValidated('git_admin_template_id', 'uint')) {
                        $template_id = $this->request->get('git_admin_template_id');
                        $this->addAction('deleteGerritTemplate', [$template_id, $project, $user]);
                    }
                }

                if ($this->permissions_manager->userIsGitAdmin($user, $project)) {
                    $this->addAction('generateGerritRepositoryAndTemplateList', [$project, $user]);
                    $this->setDefaultPageRendering(false);
                    $this->addView(
                        'adminGerritTemplatesView',
                    );
                } else {
                    $this->addError(dgettext('tuleap-git', 'You are not allowed to access this page'));
                    $this->redirect('/plugins/git/?action=index&group_id=' . $this->groupId);
                    return false;
                }

                break;
            case 'admin-default-access-rights':
                if ($this->request->get('save')) {
                    $this->template_permission_updater->updateProjectTemplatePermissions($this->request);
                }

                $this->addRedirectToDefaultSettingsAction();

                break;
            case 'fetch_git_config':
                $project = $this->projectManager->getProject($this->groupId);
                $this->setDefaultPageRendering(false);
                $this->addAction('fetchGitConfig', [$repository->getId(), $user, $project]);
                break;
            case 'fetch_git_template':
                $project     = $this->projectManager->getProject($this->groupId);
                $template_id = $this->request->getValidated('template_id', 'uint');
                $this->setDefaultPageRendering(false);
                $this->addAction('fetchGitTemplate', [$template_id, $user, $project]);
                break;
            case 'fork_repositories_permissions':
                $scope     = self::SCOPE_PERSONAL;
                $valid_url = new Valid_UInt('repos');
                $valid_url->required();
                if ($this->request->validArray($valid_url)) {
                    $repos = $this->request->get('repos');
                }
                $valid_url = new Valid_UInt('to_project');
                if ($this->request->valid($valid_url)) {
                    $toProject = $this->request->get('to_project');
                }
                $valid_url = new Valid_String('path');
                $valid_url->required();
                $path = '';
                if ($this->request->valid($valid_url)) {
                    $path = $this->request->get('path');
                }
                $valid_url = new Valid_String('choose_destination');
                $valid_url->required();
                if ($this->request->valid($valid_url)) {
                    $scope = $this->request->get('choose_destination');
                }
                if (! empty($repos)) {
                    $this->addAction('forkRepositoriesPermissions', [$repos, $toProject, $path, $scope]);
                    $this->addView('forkRepositoriesPermissions');
                } else {
                    $this->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
                    $this->addAction('getProjectRepositoryList', [$this->groupId]);
                    $this->addView('forkRepositories');
                }
                break;
            case 'do_fork_repositories':
                try {
                    if ($this->request->get('choose_destination') == self::SCOPE_PERSONAL) {
                        if ($this->user->isMember($this->groupId)) {
                            $this->_doDispatchForkRepositories($this->request, $user);
                        } else {
                            $this->addError(dgettext('tuleap-git', 'You are not allowed to access this page'));
                        }
                    } else {
                        $this->_doDispatchForkCrossProject($this->request, $user);
                    }
                } catch (MalformedPathException $e) {
                    $this->addError(dgettext('tuleap-git', 'Path cannot contain double dots (..)'));
                }
                $this->addAction('getProjectRepositoryList', [$this->groupId]);
                $this->addView('forkRepositories');
                break;
            case "view_last_git_pushes":
                $vGroupId = new Valid_GroupId();
                $vGroupId->required();
                if ($this->request->valid($vGroupId)) {
                    $groupId = $this->request->get('group_id');
                }
                $vWeeksNumber = new Valid_UInt('weeks_number');
                if ($this->request->valid($vWeeksNumber)) {
                    $weeksNumber = $this->request->get('weeks_number');
                }
                if (empty($weeksNumber) || $weeksNumber > Git_LastPushesGraph::MAX_WEEKSNUMBER) {
                    $weeksNumber = 12;
                }
                $imageRenderer = new Git_LastPushesGraph($groupId, $weeksNumber);
                $imageRenderer->display();
                break;
            case 'migrate_to_gerrit':
                $this->defaultCSRFChecks($repository, 'gerrit');
                if (! $this->gerrit_can_migrate_checker->canMigrate($repository->getProject())) {
                    $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
                    break;
                }

                $remote_server_id   = $this->request->getValidated('remote_server_id', 'uint');
                $gerrit_template_id = $this->getValidatedGerritTemplateId($repository);

                if (empty($remote_server_id) || empty($gerrit_template_id)) {
                    $this->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
                    $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
                } else {
                    try {
                        $project_exists = $this->gerritProjectAlreadyExists($remote_server_id, $repository);
                        if ($project_exists) {
                            $this->addError(dgettext('tuleap-git', 'A Gerrit project with that name already exists on that server'));
                        } else {
                            $this->addAction('migrateToGerrit', [$repository, $remote_server_id, $gerrit_template_id, $user]);
                        }
                    } catch (Git_Driver_Gerrit_Exception $e) {
                        $this->addError(dgettext('tuleap-git', 'Cannot connect to remote Gerrit server') . ' ' . $e->getMessage());
                    } catch (Git_RemoteServer_NotFoundException $e) {
                        $this->addError(dgettext('tuleap-git', 'The requested Gerrit server does not exist.') . ' ' . $e->getMessage());
                    }
                    $this->addAction('redirectToRepoManagementWithMigrationAccessRightInformation', [$this->groupId, $repository->getId(), $pane]);
                }
                break;
            case 'disconnect_gerrit':
                if (empty($repository)) {
                    $this->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
                    $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
                } else {
                    $this->defaultCSRFChecks($repository, 'gerrit');
                    $this->addAction('disconnectFromGerrit', [$repository]);
                    $this->addAction('redirectToRepoManagement', [$this->groupId, $repository->getId(), $pane]);
                }
                break;
            case 'delete_gerrit_project':
                $server              = $this->gerrit_server_factory->getServerById($repository->getRemoteServerId());
                $project_gerrit_name = $this->driver_factory->getDriver($server)->getGerritProjectName($repository);
                $this->defaultCSRFChecks($repository, 'gerrit');

                try {
                    $this->driver_factory->getDriver($server)->deleteProject($server, $project_gerrit_name);
                } catch (ProjectDeletionException $exception) {
                    $this->addError(sprintf(dgettext('tuleap-git', 'Cannot delete project %1$s on Gerrit: %2$s.'), $project_gerrit_name, $exception->getMessage()));
                } catch (Git_Driver_Gerrit_Exception $e) {
                    $this->addError(dgettext('tuleap-git', 'Cannot connect to remote Gerrit server'));
                }
                $migrate_access_right = $this->request->existAndNonEmpty('migrate_access_right');
                $this->addAction('redirectToRepoManagementWithMigrationAccessRightInformation', [$this->groupId, $repository->getId(), $pane]);
                break;
            case 'restore':
                $this->addAction('restoreRepository', [$repo_id, $this->groupId]);
                break;
            case 'delete-permissions':
                $url  = '?action=repo_management&pane=perms&group_id=' . $this->groupId;
                $csrf = new CSRFSynchronizerToken($url);
                $csrf->check();

                $permission_id = $this->getPermissionId();
                if (! $permission_id) {
                    return;
                }

                $deleted = $this->fine_grained_permission_destructor->deleteRepositoryPermissions(
                    $repository,
                    $permission_id
                );

                $this->emitFeedbackForPermissionDeletion($deleted);

                $this->history_dao->groupAddHistory(
                    'perm_granted_for_git_repository',
                    $this->history_value_formatter->formatValueForRepository($repository),
                    $this->groupId,
                    [$repository->getName()]
                );

                $this->git_system_event_manager->queueRepositoryUpdate($repository);

                $this->addAction('redirectToRepoManagement', [$this->groupId, $repository->getId(), $pane]);
                break;
            case 'delete-default-permissions':
                $url  = '?action=admin-default-settings&pane=access_control&group_id=' . $this->groupId;
                $csrf = new CSRFSynchronizerToken($url);
                $csrf->check();

                $permission_id = $this->getPermissionId();
                if (! $permission_id) {
                    return;
                }

                $deleted = $this->fine_grained_permission_destructor->deleteDefaultPermissions(
                    $this->request->getProject(),
                    $permission_id
                );

                $this->emitFeedbackForPermissionDeletion($deleted);

                $this->history_dao->groupAddHistory(
                    self::DEFAULT_GIT_PERMS_GRANTED_FOR_PROJECT,
                    $this->history_value_formatter->formatValueForProject($this->request->getProject()),
                    $this->groupId,
                    [$this->groupId]
                );

                $this->addRedirectToDefaultSettingsAction();
                break;
            // LIST
            default:
                $GLOBALS['Response']->permanentRedirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . "/");

                break;
        }
    }

    private function emitFeedbackForPermissionDeletion($deleted)
    {
        if ($deleted) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-git', 'Permission successfully deleted.')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'An error occured while deleting permission.')
            );
        }
    }

    private function getPermissionId()
    {
        $permission_id = $this->request->get('permission_id');
        if (! $permission_id) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Bad request.')
            );

            return;
        }

        return $permission_id;
    }

    private function addRedirectToDefaultSettingsAction()
    {
        $pane           = \Tuleap\Git\DefaultSettings\Pane\AccessControl::NAME;
        $requested_pane = $this->request->get('pane');
        if ($requested_pane) {
            $pane = $requested_pane;
        }

        $this->addAction('redirectToDefaultSettings', [$this->groupId, $pane]);
    }

    private function getValidatedGerritTemplateId($repository)
    {
        if (empty($repository)) {
            return null;
        }
        $template_id = $this->request->getValidated('gerrit_template_id', 'string');

        if ($template_id && ($template_id == Git_Driver_Gerrit_ProjectCreator::NO_PERMISSIONS_MIGRATION || $template_id == Git_Driver_Gerrit_ProjectCreator::DEFAULT_PERMISSIONS_MIGRATION)) {
            return $template_id;
        }

        $template_id = $this->request->getValidated('gerrit_template_id', 'uint');

        if ($template_id) {
            try {
                $this->template_factory->getTemplate($template_id);
            } catch (Git_Template_NotFoundException $e) {
                return null;
            }
        }

        if ($this->project_creator->checkTemplateIsAvailableForProject($template_id, $repository)) {
            return $template_id;
        }

        return null;
    }

    private function gerritProjectAlreadyExists($remote_server_id, GitRepository $repo)
    {
        $gerrit_server       = $this->gerrit_server_factory->getServerById($remote_server_id);
        $driver              = $this->driver_factory->getDriver($gerrit_server);
        $gerrit_project_name = $driver->getGerritProjectName($repo);

        return $driver->doesTheProjectExist($gerrit_server, $gerrit_project_name);
    }

    private function processRepoManagementNotifications($pane, $repoId, $repositoryName, $user)
    {
        $this->addView('repoManagement');
        if ($this->request->exist('mail_prefix')) {
            $valid = new Valid_String('mail_prefix');
            $valid->required();
            $mailPrefix = $this->request->getValidated('mail_prefix', $valid, '');
            $this->addAction('notificationUpdatePrefix', [$this->groupId, $repoId, $mailPrefix, $pane]);
        }
        $add_mail = $this->request->getValidated('add_mail');
        if ($add_mail) {
            $invalid_entries = new InvalidEntryInAutocompleterCollection();
            $autocompleter   = new RequestFromAutocompleter(
                $invalid_entries,
                new Rule_Email(),
                UserManager::instance(),
                $this->ugroup_manager,
                $user,
                $this->request->getProject(),
                $add_mail
            );
            $invalid_entries->generateWarningMessageForInvalidEntries();

            $emails = $autocompleter->getEmails();
            if ($emails) {
                $this->addAction('notificationAddMail', [$this->groupId, $repoId, $emails, $pane]);
            }

            $users = $autocompleter->getUsers();
            if ($users) {
                $this->addAction('notificationAddUsers', [$this->groupId, $repoId, $users]);
            }

            $ugroups = $autocompleter->getUgroups();
            if ($ugroups) {
                $this->addAction('notificationAddUgroups', [$this->groupId, $repoId, $ugroups]);
            }
        }
        $remove_mail = $this->request->get('remove_mail');
        if (is_array($remove_mail)) {
            $mails = [];
            $valid = new Valid_Email('remove_mail');
            $valid->required();
            if ($this->request->validArray($valid)) {
                $mails = $this->request->get('remove_mail');
            }
            if (count($mails) > 0) {
                $this->addAction('notificationRemoveMail', [$this->groupId, $repoId, $mails, $pane]);
            }
        }
        $users_to_remove = $this->request->get('remove_user');
        if (is_array($users_to_remove) && count($users_to_remove) > 0) {
            $this->addAction('notificationRemoveUser', [$this->groupId, $repoId, $users_to_remove]);
        }
        $ugrops_to_remove = $this->request->get('remove_ugroup');
        if (is_array($ugrops_to_remove) && count($ugrops_to_remove) > 0) {
            $this->addAction('notificationRemoveUgroup', [$this->groupId, $repoId, $ugrops_to_remove]);
        }
        $this->addAction('redirectToRepoManagement', [$this->groupId, $repoId, $pane]);
    }

    protected function _informAboutPendingEvents(/* GitRepository */ $repository)
    {
        $sem = SystemEventManager::instance();
        $dar = $sem->_getDao()->searchWithParam('head', $this->groupId, ['GIT_REPO_CREATE', 'GIT_REPO_DELETE'], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]);
        foreach ($dar as $row) {
            $p                  = explode(SystemEvent::PARAMETER_SEPARATOR, $row['parameters']);
            $deleted_repository = $this->factory->getDeletedRepository($p[1]);
            switch ($row['type']) {
                case 'GIT_REPO_CREATE':
                    $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-git', 'There is an event in queue for a repository creation (%1$s), it will be processed in one minute or two. Please be patient!'), $p[1]));
                    break;

                case 'GIT_REPO_DELETE':
                    $repository_name = '';
                    if ($deleted_repository !== null) {
                        $repository_name = $deleted_repository->getFullName();
                    }
                    $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-git', 'There is an event in queue for repository \'%1$s\' deletion, it will be processed in one minute or two. Please be patient!'), $repository_name));
                    break;
            }
        }

        if ($repository && $repository->getId() !== 0) {
            $dar = $sem->_getDao()->searchWithParam('head', $repository->getId(), ['GIT_REPO_ACCESS'], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]);
            foreach ($dar as $row) {
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-git', 'There is an event in queue for a repository permissions change, it will be processed in one minute or two. Please be patient!'));
            }
        }
    }

    protected function instantiateAction($action)
    {
        $instance = new $action(
            $this,
            $this->git_system_event_manager,
            $this->factory,
            $this->repository_manager,
            $this->gerrit_server_factory,
            $this->driver_factory,
            $this->gerrit_usermanager,
            $this->project_creator,
            $this->template_factory,
            $this->projectManager,
            $this->permissions_manager,
            $this->url_manager,
            $this->logger,
            $this->history_dao,
            new MigrationHandler(
                $this->git_system_event_manager,
                $this->gerrit_server_factory,
                $this->driver_factory,
                $this->history_dao,
                $this->project_creator_status,
                $this->projectManager
            ),
            $this->gerrit_can_migrate_checker,
            $this->fine_grained_updater,
            $this->fine_grained_permission_saver,
            $this->fine_grained_retriever,
            $this->history_value_formatter,
            $this->permission_changes_detector,
            $this->regexp_enabler,
            $this->regexp_disabler,
            $this->regexp_filter,
            $this->regexp_retriever,
            $this->users_to_notify_dao,
            $this->ugroups_to_notify_dao,
            $this->ugroup_manager
        );
        \assert($instance instanceof PluginActions);
        return $instance;
    }

    public function _doDispatchForkCrossProject($request, $user)
    {
        $this->checkSynchronizerToken('/plugins/git/?group_id=' . (int) $this->groupId . '&action=fork_repositories');
        $validators = [new Valid_UInt('to_project'), new Valid_String('repos'), new Valid_Array('repo_access')];

        foreach ($validators as $validator) {
            $validator->required();
            if (! $request->valid($validator)) {
                if ($validator->key === 'to_project') {
                    $this->addError(dgettext('tuleap-git', 'No project selected for the fork'));
                } elseif ($validator->key === 'repos') {
                    $this->addError(dgettext('tuleap-git', 'No repository selected for the fork'));
                } else {
                    $this->addError(dgettext('tuleap-git', 'No access selected for the fork'));
                }
                $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
                return;
            }
        }
        $to_project_id = $request->get('to_project');
        if ($this->permissions_manager->userIsGitAdmin($user, $this->projectManager->getProject($to_project_id))) {
            $to_project      = $this->projectManager->getProject($to_project_id);
            $repos_ids       = explode(',', $request->get('repos'));
            $repos           = $this->getRepositoriesFromIds($repos_ids);
            $namespace       = '';
            $scope           = GitRepository::REPO_SCOPE_PROJECT;
            $redirect_url    = '/plugins/git/' . urlencode($to_project->getUnixNameLowerCase()) . '/';
            $forkPermissions = $this->getForkPermissionsFromRequest($request);

            $this->addAction('fork', [$repos, $to_project, $namespace, $scope, $user, $GLOBALS['HTML'], $redirect_url, $forkPermissions]);
        } else {
            $this->addError(dgettext('tuleap-git', 'Only project administrator can create repositories'));
        }
    }

    public function redirectNoRepositoryError()
    {
        $this->addError(dgettext('tuleap-git', 'The repository does not exist'));
        $this->redirect('/plugins/git/?action=index&group_id=' . $this->groupId);
    }

    private function defaultCSRFChecks(GitRepository $repository, string $pane): void
    {
        $default_url = '/plugins/git/?' . http_build_query(['action' => 'repo_management', 'group_id' => $this->groupId, 'repo_id' => $repository->getId(), 'pane' => $pane]);
        if (! $this->request->isPost()) {
            $this->redirect($default_url);
        }
        $this->checkSynchronizerToken($default_url);
    }

    public function defaultProjectAdminCSRFChecks(string $action): void
    {
        $url = '/plugins/git/?' . http_build_query(['group_id' => $this->groupId, 'action' => $action]);
        if (! $this->request->isPost()) {
            $this->redirect($url);
        }
        $this->checkSynchronizerToken($url);
    }

    protected function checkSynchronizerToken($url)
    {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    public function _doDispatchForkRepositories($request, $user)
    {
        $this->addAction('getProjectRepositoryList', [$this->groupId]);
        $this->checkSynchronizerToken('/plugins/git/?group_id=' . (int) $this->groupId . '&action=fork_repositories');

        $repos_ids = [];

        $valid = new Valid_String('path');
        $valid->required();

        $path = '';
        if ($request->valid($valid)) {
            $path = trim($request->get('path'));
        }
        $path            = PathJoinUtil::userRepoPath($user->getUserName(), $path);
        $forkPermissions = $this->getForkPermissionsFromRequest($request);

        $valid = new Valid_String('repos');
        $valid->required();
        $repos_ids    = explode(',', $request->get('repos'));
        $to_project   = $this->projectManager->getProject($this->groupId);
        $repos        = $this->getRepositoriesFromIds($repos_ids);
        $scope        = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $redirect_url = '/plugins/git/?group_id=' . (int) $this->groupId . '&user=' . (int) $user->getId();
        $this->addAction('fork', [$repos, $to_project, $path, $scope, $user, $GLOBALS['HTML'], $redirect_url, $forkPermissions]);
    }

    /**
     * @return array
     */
    private function getForkPermissionsFromRequest(Codendi_Request $request)
    {
        $fork_permissions = $request->get('repo_access');
        if ($fork_permissions) {
            return $fork_permissions;
        }
        // when we fork a gerrit repository, the repo rights cannot
        // be updated by the user on the intermediate screen and the
        // repo_access is false. Forcing it to empty array to avoid
        // fatal errors
        return [];
    }

    private function getRepositoriesFromIds($repository_ids)
    {
        $repositories = [];

        foreach ($repository_ids as $repository_id) {
            $repository = $this->factory->getRepositoryById($repository_id);

            if (! $repository) {
                return false;
            }

            $repositories[] = $repository;
        }

        return $repositories;
    }

    /**
     * Add pushes' logs stuff
     *
     * @param Array $params
     *
     * @return Void
     */
    public function logsDaily($params)
    {
        $logger = new GitLog();
        $logger->logsDaily($params);
    }
}
