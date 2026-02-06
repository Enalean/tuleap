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

use Psr\Log\LoggerInterface;
use Tuleap\Git\AsynchronousEvents\GitRepositoryChangeTask;
use Tuleap\Git\DefaultBranch\CannotSetANonExistingBranchAsDefaultException;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdater;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
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
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Git\Repository\DescriptionUpdater;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\User\InvalidEntryInAutocompleterCollection;
use Tuleap\User\RequestFromAutocompleter;

class Git extends PluginController //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const string ADMIN_ACTION                   = 'admin';
    public const string ADMIN_GERRIT_TEMPLATES_ACTION  = 'admin-gerrit-templates';
    public const string GIT_ADMIN_USER_GROUPS_ACTION   = 'admin-git-admins';
    public const string ADMIN_DEFAULT_SETTINGS_ACTION  = 'admin-default-settings';
    public const string ADMIN_GIT_ADMINS_ACTION        = 'admin-git-admins';
    public const string ADMIN_MIGRATE_TO_GERRIT_ACTION = 'migrate_to_gerrit';
    public const string DISCONNECT_GERRIT_ACTION       = 'disconnect_gerrit';

    public const string PERM_READ  = 'PLUGIN_GIT_READ';
    public const string PERM_WRITE = 'PLUGIN_GIT_WRITE';
    public const string PERM_WPLUS = 'PLUGIN_GIT_WPLUS';

    public const string READ_PERM = 'R';

    public const string DEFAULT_PERM_READ  = 'PLUGIN_GIT_DEFAULT_READ';
    public const string DEFAULT_PERM_WRITE = 'PLUGIN_GIT_DEFAULT_WRITE';
    public const string DEFAULT_PERM_WPLUS = 'PLUGIN_GIT_DEFAULT_WPLUS';

    public const string PERM_ADMIN         = 'PLUGIN_GIT_ADMIN';
    public const string SPECIAL_PERM_ADMIN = 'PROJECT_ADMIN';

    public const string REFERENCE_KEYWORD = 'git';
    public const string REFERENCE_NATURE  = 'git_commit';

    public const string TAG_REFERENCE_KEYWORD = 'git_tag';
    public const string TAG_REFERENCE_NATURE  = 'git_tag';

    public const string DEFAULT_GIT_PERMS_GRANTED_FOR_PROJECT = 'default_git_perms_granted_for_project';

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
     * @var int
     */
    protected $groupId;
    private GitRepositoryFactory $factory;
    private UserManager $userManager;
    private ProjectManager $projectManager;
    private GitPermissionsManager $permissions_manager;
    private Project $project;

    public function __construct(
        private readonly GitPlugin $plugin,
        private readonly Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        private readonly Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        private readonly Git_SystemEventManager $git_system_event_manager,
        private readonly \Tuleap\Queue\EnqueueTaskInterface $enqueuer,
        private readonly Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        GitRepositoryFactory $git_repository_factory,
        UserManager $user_manager,
        ProjectManager $project_manager,
        \Tuleap\HTTPRequest $request,
        private readonly Git_Driver_Gerrit_ProjectCreator $project_creator,
        private readonly Git_Driver_Gerrit_Template_TemplateFactory $template_factory,
        GitPermissionsManager $permissions_manager,
        private readonly Git_GitRepositoryUrlManager $url_manager,
        private readonly LoggerInterface $logger,
        private readonly Git_Driver_Gerrit_ProjectCreatorStatus $project_creator_status,
        private readonly GerritCanMigrateChecker $gerrit_can_migrate_checker,
        private readonly FineGrainedUpdater $fine_grained_updater,
        private readonly FineGrainedPermissionFactory $fine_grained_permission_factory,
        private readonly FineGrainedRetriever $fine_grained_retriever,
        private readonly FineGrainedPermissionSaver $fine_grained_permission_saver,
        private readonly DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        private readonly FineGrainedPermissionDestructor $fine_grained_permission_destructor,
        private readonly FineGrainedRepresentationBuilder $fine_grained_builder,
        private readonly HistoryValueFormatter $history_value_formatter,
        private readonly PermissionChangesDetector $permission_changes_detector,
        private readonly TemplatePermissionsUpdater $template_permission_updater,
        private readonly ProjectHistoryDao $history_dao,
        private readonly DefaultBranchUpdater $default_branch_updater,
        private readonly DescriptionUpdater $description_updater,
        private readonly RegexpFineGrainedRetriever $regexp_retriever,
        private readonly RegexpFineGrainedEnabler $regexp_enabler,
        private readonly RegexpFineGrainedDisabler $regexp_disabler,
        private readonly RegexpPermissionFilter $regexp_filter,
        private readonly UsersToNotifyDao $users_to_notify_dao,
        private readonly UgroupsToNotifyDao $ugroups_to_notify_dao,
        private readonly UGroupManager $ugroup_manager,
        private readonly HeaderRenderer $header_renderer,
        private readonly VerifyArtifactClosureIsAllowed $closure_verifier,
        private readonly ConfigureAllowArtifactClosure $configure_artifact_closure,
        private readonly User_ForgeUserGroupFactory $user_group_factory,
    ) {
        parent::__construct($user_manager, $request);

        $this->userManager         = $user_manager;
        $this->projectManager      = $project_manager;
        $this->factory             = $git_repository_factory;
        $this->permissions_manager = $permissions_manager;

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

        $this->permittedActions = [];
    }

    #[\Override]
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
            $this->user_group_factory,
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

    public function setRequest(\Tuleap\HTTPRequest $request)
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

    #[\Override]
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
                'set_private',
                'confirm_private',
                self::ADMIN_ACTION,
                self::GIT_ADMIN_USER_GROUPS_ACTION,
                self::ADMIN_GERRIT_TEMPLATES_ACTION,
                'admin-default-access-rights',
                'delete-permissions',
                'delete-default-permissions',
                'fetch_git_config',
                'fetch_git_template',
                'view_last_git_pushes',
                self::ADMIN_MIGRATE_TO_GERRIT_ACTION,
                self::DISCONNECT_GERRIT_ACTION,
                'delete_gerrit_project',
            ];
            if ($user->isSuperUser()) {
                $this->permittedActions[] = 'restore';
            }
        } else {
            $this->addPermittedAction('index');
            $this->addPermittedAction('view_last_git_pushes');

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

    #[\Override]
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
            $this->redirectToProjectRepositoriesList();
            return;
        }

        $this->_dispatchActionAndView($this->action, $repository, $repoId, $repositoryName, $user);
    }

    public function _dispatchActionAndView($action, /* GitRepository */ $repository, $repo_id, $repositoryName, $user) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
                    $repository_id                         = $repository->getId();
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
                    $this->redirectToProjectRepositoriesList();
                }
                break;
            // repo_management
            case 'repo_management':
                if (empty($repository)) {
                    $this->redirectNoRepositoryError();
                    return false;
                }
                $params = [$repository];
                $this->addAction('repoManagement', $params);
                $this->setDefaultPageRendering(false);
                $this->addView('repoManagement', $params);
                break;
            case 'mail':
                $this->defaultCSRFChecks($repository, 'mail');
                $this->processRepoManagementNotifications($pane, $repository->getId(), $repositoryName, $user);
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
            case self::GIT_ADMIN_USER_GROUPS_ACTION:
                if ($this->request->get('submit')) {
                    $this->defaultProjectAdminCSRFChecks(self::GIT_ADMIN_USER_GROUPS_ACTION);
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
            case self::ADMIN_ACTION:
            case self::ADMIN_GERRIT_TEMPLATES_ACTION:
                $project = $this->projectManager->getProject($this->groupId);

                if ($this->request->get('save')) {
                    GitViews::getGerritTemplatesCSRF($this->groupId)->check();
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
                    GitViews::getGerritTemplatesCSRF($this->groupId)->check();
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
            case 'view_last_git_pushes':
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
            case self::ADMIN_MIGRATE_TO_GERRIT_ACTION:
                $this->defaultCSRFChecks($repository, Gerrit::ID);
                if (! $this->gerrit_can_migrate_checker->canMigrate($repository->getProject())) {
                    $this->redirectToProjectRepositoriesList();
                    break;
                }

                $remote_server_id   = $this->request->getValidated('remote_server_id', 'uint');
                $gerrit_template_id = $this->getValidatedGerritTemplateId($repository);

                if (empty($remote_server_id) || empty($gerrit_template_id)) {
                    $this->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
                    $this->redirectToProjectRepositoriesList();
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
            case self::DISCONNECT_GERRIT_ACTION:
                if (empty($repository)) {
                    $this->addError(dgettext('tuleap-git', 'Empty required parameter(s)'));
                    $this->redirectToProjectRepositoriesList();
                } else {
                    $this->defaultCSRFChecks($repository, Gerrit::ID);
                    $this->addAction('disconnectFromGerrit', [$repository]);
                    $this->addAction('redirectToRepoManagement', [$this->groupId, $repository->getId(), $pane]);
                }
                break;
            case 'delete_gerrit_project':
                $server              = $this->gerrit_server_factory->getServerById($repository->getRemoteServerId());
                $project_gerrit_name = $this->driver_factory->getDriver($server)->getGerritProjectName($repository);
                $this->defaultCSRFChecks($repository, Gerrit::ID);

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

                $this->enqueuer->enqueue(GitRepositoryChangeTask::fromRepository($repository));

                $this->addAction('redirectToRepoManagement', [$this->groupId, $repository->getId(), $pane]);
                break;
            case 'delete-default-permissions':
                $url  = '?action=' . self::ADMIN_DEFAULT_SETTINGS_ACTION . '&pane=access_control&group_id=' . $this->groupId;
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
                $GLOBALS['Response']->permanentRedirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');

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
        $add_mail = $this->request->getValidated('add_mail', new Valid_Array());
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

    #[\Override]
    protected function instantiateAction($action)
    {
        $instance = new $action(
            $this,
            $this->git_system_event_manager,
            $this->enqueuer,
            $this->factory,
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
                $this->enqueuer,
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

    protected function redirectToProjectRepositoriesList(): void
    {
        $this->redirect('/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/');
    }
}
