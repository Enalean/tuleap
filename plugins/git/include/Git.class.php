<?php

/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
  * Copyright (c) Enalean, 2011-2016. All Rights Reserved.
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

require_once('common/valid/ValidFactory.class.php');

/**
 * Git
 * @author Guillaume Storchi
 */
class Git extends PluginController {

    const PERM_READ  = 'PLUGIN_GIT_READ';
    const PERM_WRITE = 'PLUGIN_GIT_WRITE';
    const PERM_WPLUS = 'PLUGIN_GIT_WPLUS';

    const PERM_ADMIN         = 'PLUGIN_GIT_ADMIN';
    const SPECIAL_PERM_ADMIN = 'PROJECT_ADMIN';

    const SCOPE_PERSONAL = 'personal';

    const REFERENCE_KEYWORD = 'git';
    const REFERENCE_NATURE  = 'git_commit';

    /**
     * Lists all git-related permission types.
     *
     * @return array
     */
    public static function allPermissionTypes() {
        return array(Git::PERM_READ, Git::PERM_WRITE, Git::PERM_WPLUS);
    }

    /**
     * @var Git_Backend_Gitolite
     */
    private $backend_gitolite;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;

    /**
     * @var Logger
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
     * @var GitPlugin
     */
    private $plugin;

    /**
     * @var Git_RemoteServer_GerritServerFactory
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

    /** @var PluginManager */
    private $plugin_manager;

    /** @var Git_Driver_Gerrit_ProjectCreator */
    private $project_creator;

    /** @var GitPermissionsManager */
    private $permissions_manager;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /** @var Project */
    private $project;

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
        PluginManager $plugin_manager,
        Codendi_Request $request,
        Git_Driver_Gerrit_ProjectCreator $project_creator,
        Git_Driver_Gerrit_Template_TemplateFactory $template_factory,
        GitPermissionsManager $permissions_manager,
        Git_GitRepositoryUrlManager $url_manager,
        Logger $logger,
        Git_Backend_Gitolite $backend_gitolite,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper
    ) {
        parent::__construct($user_manager, $request);

        $this->userManager              = $user_manager;
        $this->projectManager           = $project_manager;
        $this->factory                  = $git_repository_factory;
        $this->gerrit_server_factory    = $gerrit_server_factory;
        $this->driver_factory           = $driver_factory;
        $this->repository_manager       = $repository_manager;
        $this->git_system_event_manager = $system_event_manager;
        $this->gerrit_usermanager       = $gerrit_usermanager;
        $this->plugin_manager           = $plugin_manager;
        $this->project_creator          = $project_creator;
        $this->template_factory         = $template_factory;
        $this->permissions_manager      = $permissions_manager;
        $this->plugin                   = $plugin;
        $this->url_manager              = $url_manager;
        $this->logger                   = $logger;
        $this->backend_gitolite         = $backend_gitolite;
        $this->mirror_data_mapper       = $mirror_data_mapper;

        $url = new Git_URL(
            $this->projectManager,
            $this->factory,
            $_SERVER['REQUEST_URI']
        );
        $this->routeGitSmartHTTP($url);
        $this->routeUsingFriendlyURLs($url);
        $this->routeUsingStandardURLs($url);

        $valid = new Valid_GroupId('group_id');
        $valid->required();
        if($this->request->valid($valid)) {
            $this->groupId = (int)$this->request->get('group_id');
        }
        $valid = new Valid_String('action');
        $valid->required();
        if($this->request->valid($valid)) {
            $this->action = $this->request->get('action');
        }

        if (  empty($this->action) ) {
            $this->action = 'index';
        }
        if ( empty($this->groupId) ) {
            $this->addError('Bad request');
            $this->redirect('/');
        }

        $this->project     = $this->projectManager->getProject($this->groupId);
        $this->projectName = $this->project->getUnixName();
        if ( !$this->plugin_manager->isPluginAllowedForProject($this->plugin, $this->groupId) ) {
            $this->addError( $this->getText('project_service_not_available') );
            $this->redirect('/projects/'.$this->projectName.'/');
        }

        $this->permittedActions = array();
    }

    protected function instantiateView() {
        return new GitViews($this, new Git_GitRepositoryUrlManager($this->getPlugin()), $this->mirror_data_mapper, $this->permissions_manager);
    }

    private function routeGitSmartHTTP(Git_URL $url) {
        if (! $url->isSmartHTTP()) {
            return;
        }

        $repository = $url->getRepository();
        if (! $repository) {
            return;
        }

        $logger = new WrapperLogger($this->logger, 'http');

        $logger->debug('REQUEST_URI '.$_SERVER['REQUEST_URI']);

        $command_factory = new Git_HTTP_CommandFactory(
            $this->factory,
            new User_LoginManager(
                EventManager::instance(),
                UserManager::instance(),
                new User_PasswordExpirationChecker(),
                PasswordHandlerFactory::getPasswordHandler()
            ),
            PermissionsManager::instance(),
            new URLVerification(),
            $logger
        );

        $http_wrapper = new Git_HTTP_Wrapper($logger);
        $http_wrapper->stream($command_factory->getCommandForRepository($repository, $url));
        exit;
    }

    private function routeUsingFriendlyURLs(Git_URL $url) {
        if (! $this->getPlugin()->areFriendlyUrlsActivated()) {
            return;
        }

        if (! $url->isFriendly()) {
            return;
        }

        $repository = $url->getRepository();
        if (! $repository) {
            return;
        }

        $this->request->set('action', 'view');
        $this->request->set('group_id', $repository->getProjectId());
        $this->request->set('repo_id', $repository->getId());

        $this->addUrlParametersToRequest($url);
    }

    private function addUrlParametersToRequest(Git_URL $url) {
        $url_parameters_as_string = $url->getParameters();
        if (! $url_parameters_as_string) {
            return;
        }

        parse_str($url_parameters_as_string, $_GET);
        parse_str($url_parameters_as_string, $_REQUEST);

        parse_str($url_parameters_as_string, $url_parameters);
        foreach ($url_parameters as $key => $value) {
            $this->request->set($key, $value);
        }
    }

    private function routeUsingStandardURLs(Git_URL $url) {
        if (! $url->isStandard()) {
            return;
        }

        $repository = $url->getRepository();
        if (! $repository) {
            $this->addError('Bad request');
            $this->redirect('/');
            return;
        }

        $project = $url->getProject();
        $this->redirectIfTryingToViewRepositoryAndUserFriendlyURLsActivated($project, $repository, $url->getParameters());

        $this->request->set('group_id', $project->getId());
        $this->request->set('action', 'view');
        $this->request->set('repo_id', $repository->getId());
    }

    private function redirectIfTryingToViewRepositoryAndUserFriendlyURLsActivated(
        Project $project,
        GitRepository $repository,
        $parameters
    ) {
        if (! $this->getPlugin()->areFriendlyUrlsActivated()) {
            return;
        }

        $request_parameters = $parameters ? '?'.$parameters : '';
        $redirecting_url    = GIT_BASE_URL .'/'. $project->getUnixName() .'/'. $repository->getFullName() . $request_parameters;

        header("Location: $redirecting_url", TRUE, 301);
    }

    public function setPermissionsManager(GitPermissionsManager $permissions_manager) {
        $this->permissions_manager = $permissions_manager;
    }

    public function setProjectManager($projectManager) {
        $this->projectManager = $projectManager;
    }

    public function setFactory(GitRepositoryFactory $factory) {
        $this->factory = $factory;
    }

    public function setRequest(Codendi_Request $request) {
        $this->request = $request;
    }

    public function setUserManager(UserManager $userManager) {
        $this->userManager = $userManager;
    }

    public function setAction($action) {
        $this->action = $action;
    }

    public function setGroupId($groupId) {
        $this->groupId = $groupId;
    }

    public function setPermittedActions($permittedActions) {
        $this->permittedActions = $permittedActions;
    }

    protected function getText($key, $params = array()) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }

    /**
     * @return GitPlugin
     */
    public function getPlugin() {
        return $this->plugin;
    }

    protected function definePermittedActions(/* GitRepository */ $repository, $user) {
        if ($this->permissions_manager->userIsGitAdmin($user, $this->projectManager->getProject($this->groupId))) {
            $this->permittedActions = array(
                'index',
                'view' ,
                'edit',
                'clone',
                'add',
                'del',
                'create',
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
                'admin-default-settings',
                'fetch_git_config',
                'fetch_git_template',
                'fork_repositories_permissions',
                'do_fork_repositories',
                'view_last_git_pushes',
                'migrate_to_gerrit',
                'disconnect_gerrit',
                'delete_gerrit_project',
                'update_mirroring',
                'update_default_mirroring',
                'restore'
            );
            if ($this->areMirrorsEnabledForProject()) {
                $this->permittedActions[] = 'admin-mass-update';
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
                $this->addPermittedAction('clone');
                if ($repo->belongsTo($user)) {
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

    private function addAdditionalPermittedActions(PFUser $user, /* GitRepository */ $repository) {
        $permitted_actions = array();
        $params            = array(
            'repository'        => $repository,
            'user'              => $user,
            'permitted_actions' => &$permitted_actions
        );

        EventManager::instance()->processEvent(GIT_ADDITIONAL_PERMITTED_ACTIONS, $params);

        foreach ($permitted_actions as $permitted_action) {
            $this->addPermittedAction($permitted_action);
        }
    }

    public function request() {
        $valid = new Valid_String('repo_name');
        $valid->required();
        $repositoryName = null;
        if($this->request->valid($valid)) {
            $repositoryName = trim($this->request->get('repo_name'));
        }
        $valid = new Valid_UInt('repo_id');
        $valid->required();
        if($this->request->valid($valid)) {
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
        if ( empty($this->permittedActions) || !$this->isAPermittedAction($this->action) ) {
            $this->addError($this->getText('controller_access_denied'));
            $this->redirect('/plugins/git/?group_id='.$this->groupId);
            return;
        }

        $this->_informAboutPendingEvents($repository);

        $this->_dispatchActionAndView($this->action, $repository, $repoId, $repositoryName, $user);
    }

    public function _dispatchActionAndView($action, /* GitRepository */ $repository, $repo_id, $repositoryName, $user) {
        $pane = $this->request->get('pane');
        switch ($action) {
            #CREATE REF
            case 'create':
                $this->addView('create');
                break;
            #admin
            case 'view':
                $this->addAction( 'getRepositoryDetails', array($this->groupId, $repository->getId()));
                $this->addView('view');
                break;

            #ADD REF
            case 'add':
                $this->addAction('createReference', array($this->groupId, $repositoryName) );
                $this->addView('index');
                break;
             #DELETE a repository
            case 'del':
                $this->addAction( 'deleteRepository', array($this->groupId, $repository->getId()));
                $this->addView('index');
                break;
            #EDIT
            case 'edit':
                if (empty($repository)) {
                    $this->addError($this->getText('actions_params_error'));
                    $this->redirect('/plugins/git/?action=index&group_id='. $this->groupId);
                    return false;
                }
                if ( $this->isAPermittedAction('clone') && $this->request->get('clone') ) {
                    $valid = new Valid_UInt('parent_id');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $parentId = (int)$this->request->get('parent_id');
                    }
                    $this->addAction( 'cloneRepository', array($this->groupId, $repositoryName, $parentId) );
                    $this->addAction( 'getRepositoryDetails', array($this->groupId, $parentId) );
                    $this->addView('view');
                } else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {
                    $repoDesc = null;
                    if ($this->request->exist('repo_desc')) {
                        $repoDesc = GitRepository::DEFAULT_DESCRIPTION;
                        $valid = new Valid_Text('repo_desc');
                        $valid->required();
                        if($this->request->valid($valid)) {
                            $repoDesc = $this->request->get('repo_desc');
                        }
                    }
                    $repoAccess = null;
                    $valid = new Valid_String('repo_access');
                    $valid->required();
                    if($this->request->valid($valid) || is_array($this->request->get('repo_access'))) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('save', array($this->groupId, $repository->getId(), $repoAccess, $repoDesc, $pane) );
                    $this->addView('view');
                } else {
                    $this->addError( $this->getText('controller_access_denied') );
                    $this->redirect('/plugins/git/?group_id='.$this->groupId);
                }
                break;
            #repo_management
            case 'repo_management':
                if (empty($repository)) {
                    $this->redirectNoRepositoryError();
                    return false;
                }
                $this->addAction('repoManagement', array($repository));
                $this->addView('repoManagement');
                break;
            case 'mail':
                $this->processRepoManagementNotifications($pane, $repository->getId(), $repositoryName, $user);
                break;
            #fork
            case 'fork':
                $this->addAction('repoManagement', array($this->groupId, $repository->getId()));
                $this->addView('forkRepositories');
                break;
            #confirm_private
            case 'confirm_private':
                if ( $this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion') ) {
                    $this->addAction('confirmDeletion', array($this->groupId, $repository));
                    $this->addView('confirm_deletion', array( 0=>array('repo_id'=>$repository->getId()) ) );
                }
                else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {
                    $valid = new Valid_Text('repo_desc');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoDesc = $this->request->get('repo_desc');
                    }
                    $valid = new Valid_String('repo_access');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('confirmPrivate', array($this->groupId, $repository->getId(), $repoAccess, $repoDesc) );
                    $this->addView('confirmPrivate');
                }
                break;
             #SET TO PRIVATE
            case 'set_private':
                $this->addAction('setPrivate', array($this->groupId, $repository->getId()));
                $this->addView('view');
                break;
            case 'fork_repositories':
                $this->addAction('getProjectRepositoryList', array($this->groupId));
                $this->addView('forkRepositories');
                break;
            case 'admin-git-admins':
                if ($this->request->get('submit')) {
                    $valid = new Valid_Numeric(GitPresenters_AdminGitAdminsPresenter::GIT_ADMIN_SELECTBOX_NAME);
                    $project = $this->projectManager->getProject($this->groupId);

                    if ($this->request->validArray($valid)) {
                        $select_project_ids = $this->request->get(GitPresenters_AdminGitAdminsPresenter::GIT_ADMIN_SELECTBOX_NAME);

                        if ($select_project_ids) {
                            $this->addAction('updateGitAdminGroups', array($project, $user, $select_project_ids));
                        } else {
                            $this->addError($this->getText('no_data_retrieved'));
                        }
                    } else {
                        $this->addError($this->getText('not_valid_request'));
                    }
                }

                $this->addView(
                    'adminGitAdminsView',
                    array($this->areMirrorsEnabledForProject())
                );

                break;
            case 'admin':
            case 'admin-gerrit-templates':
                $project = $this->projectManager->getProject($this->groupId);

                if ($this->request->get('save')) {
                    $template_content = $this->request->getValidated('git_admin_config_data','text');
                    if ($this->request->getValidated('git_admin_template_id','uint')) {
                        $template_id = $this->request->get('git_admin_template_id');
                        $this->addAction('updateTemplate', array($project, $user, $template_content, $template_id));
                    } else {
                        $template_name = $this->request->getValidated('git_admin_file_name','string');
                        $this->addAction('createTemplate', array($project, $user, $template_content, $template_name));
                    }
                }

                if ($this->request->get('delete')) {
                    if ($this->request->getValidated('git_admin_template_id','uint')) {
                        $template_id = $this->request->get('git_admin_template_id');
                        $this->addAction('deleteGerritTemplate', array($template_id, $project, $user));
                    }
                }

                if ($this->permissions_manager->userIsGitAdmin($user, $project)) {
                    $this->addAction('generateGerritRepositoryAndTemplateList', array($project, $user));
                    $this->addView(
                        'adminGerritTemplatesView',
                        array($this->areMirrorsEnabledForProject())
                    );
                } else {
                    $this->addError($this->getText('controller_access_denied'));
                    $this->redirect('/plugins/git/?action=index&group_id='. $this->groupId);
                    return false;
                }

                break;
            case 'admin-mass-update':
                if ($this->request->get('save-mass-change') || $this->request->get('go-to-mass-change')) {
                    $this->checkSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=admin-mass-update');

                    $repositories = $this->getRepositoriesFromIds($this->request->get('repository_ids'));

                    if (! $repositories) {
                        $this->redirectNoRepositoryError();
                    }
                }

                if ($this->request->get('go-to-mass-change')) {
                    $this->addAction('setSelectedRepositories', array($repositories));
                    $this->addView('adminMassUpdateView');
                    return;
                }

                if ($this->request->get('save-mass-change')) {
                    $this->addAction('updateMirroring', array(
                        $this->request->getProject(),
                        $repositories,
                        $this->request->get('selected_mirror_ids')
                    ));
                }

                $this->addView('adminMassUpdateSelectRepositoriesView');

                break;
            case 'admin-default-settings':
                $this->addView(
                    'adminDefaultSettings',
                    array($this->areMirrorsEnabledForProject())
                );

                break;
            case 'fetch_git_config':
                $project = $this->projectManager->getProject($this->groupId);
                $this->setDefaultPageRendering(false);
                $this->addAction('fetchGitConfig', array($repository->getId(), $user, $project));
                break;
            case 'fetch_git_template':
                $project = $this->projectManager->getProject($this->groupId);
                $template_id = $this->request->getValidated('template_id','uint');
                $this->setDefaultPageRendering(false);
                $this->addAction('fetchGitTemplate', array($template_id, $user, $project));
                break;
            case 'fork_repositories_permissions':
                $scope = self::SCOPE_PERSONAL;
                $valid = new Valid_UInt('repos');
                $valid->required();
                if($this->request->validArray($valid)) {
                    $repos = $this->request->get('repos');
                }
                $valid = new Valid_UInt('to_project');
                if ($this->request->valid($valid)) {
                    $toProject = $this->request->get('to_project');
                }
                $valid = new Valid_String('path');
                $valid->required();
                $path = '';
                if($this->request->valid($valid)) {
                    $path = $this->request->get('path');
                }
                $valid = new Valid_String('choose_destination');
                $valid->required();
                if($this->request->valid($valid)) {
                    $scope = $this->request->get('choose_destination');
                }
                if (!empty($repos)) {
                    $this->addAction('forkRepositoriesPermissions', array($repos, $toProject, $path, $scope));
                    $this->addView('forkRepositoriesPermissions');
                } else {
                    $this->addError($this->getText('actions_params_error'));
                    $this->addAction('getProjectRepositoryList', array($this->groupId));
                    $this->addView('forkRepositories');
                }
                break;
            case 'do_fork_repositories':
                try {
                    if ($this->request->get('choose_destination') == self::SCOPE_PERSONAL) {
                        if ($this->user->isMember($this->groupId)) {
                            $this->_doDispatchForkRepositories($this->request, $user);
                        } else {
                            $this->addError($this->getText('controller_access_denied'));
                        }
                    } else {
                        $this->_doDispatchForkCrossProject($this->request, $user);
                    }
                } catch (MalformedPathException $e) {
                    $this->addError($this->getText('fork_malformed_path'));
                }
                $this->addAction('getProjectRepositoryList', array($this->groupId));
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
                if (ForgeConfig::get('sys_auth_type') !== ForgeConfig::AUTH_TYPE_LDAP) {
                    $this->redirect('/plugins/git/?group_id='. $this->groupId);
                    break;
                }

                $remote_server_id      = $this->request->getValidated('remote_server_id', 'uint');
                $gerrit_template_id    = $this->getValidatedGerritTemplateId($repository);

                if (empty($repository) || empty($remote_server_id) || empty($gerrit_template_id)) {
                    $this->addError($this->getText('actions_params_error'));
                    $this->redirect('/plugins/git/?group_id='. $this->groupId);
                } else {
                    try {
                        $project_exists = $this->gerritProjectAlreadyExists($remote_server_id, $repository);
                        if ($project_exists) {
                            $this->addError($this->getText('gerrit_project_exists'));
                        } else {
                            $this->addAction('migrateToGerrit', array($repository, $remote_server_id, $gerrit_template_id, $user));
                        }
                    } catch (Git_Driver_Gerrit_Exception $e) {
                        $this->addError($this->getText('gerrit_server_down'));
                    }
                    $this->addAction('redirectToRepoManagementWithMigrationAccessRightInformation', array($this->groupId, $repository->getId(), $pane));
                }
                break;
            case 'disconnect_gerrit':
                if (empty($repository)) {
                    $this->addError($this->getText('actions_params_error'));
                    $this->redirect('/plugins/git/?group_id='. $this->groupId);
                } else {
                    $this->addAction('disconnectFromGerrit', array($repository));
                    $this->addAction('redirectToRepoManagement', array($this->groupId, $repository->getId(), $pane));
                }
                break;
            case 'delete_gerrit_project':
                $server              = $this->gerrit_server_factory->getServerById($repository->getRemoteServerId());
                $project_gerrit_name = $this->driver_factory->getDriver($server)->getGerritProjectName($repository);

                try {
                    $this->driver_factory->getDriver($server)->deleteProject($server, $project_gerrit_name);
                } catch (ProjectDeletionException $exception) {
                    $this->addError($this->getText(
                        'project_deletion_not_possible',
                        array(
                            $project_gerrit_name,
                            $exception->getMessage()
                        )
                    ));
                } catch (Git_Driver_Gerrit_Exception $e) {
                    $this->addError($this->getText('gerrit_server_down'));
                }
                $migrate_access_right = $this->request->existAndNonEmpty('migrate_access_right');
                $this->addAction('redirectToRepoManagementWithMigrationAccessRightInformation', array($this->groupId, $repository->getId(), $pane));
                break;

            case 'update_mirroring':
                if (! $repository) {
                    $this->addError($this->getText('actions_repo_not_found'));
                }

                $selected_mirror_ids = $this->request->get('selected_mirror_ids');

                if (is_array($selected_mirror_ids)) {
                    $this->addAction('updateMirroring', array(
                        $this->request->getProject(),
                        array($repository),
                        $selected_mirror_ids
                    ));
                } else {
                    $this->addError($this->getText('actions_mirror_ids_not_valid'));
                }

                $this->addAction('redirectToRepoManagement', array($this->groupId, $repository->getId(), $pane));
                break;

            case 'update_default_mirroring':
                $project             = $this->request->getProject();
                $selected_mirror_ids = $this->request->get('selected_mirror_ids');

                if (is_array($selected_mirror_ids)) {
                    $this->addAction('updateDefaultMirroring', array($project, $selected_mirror_ids));
                } else {
                    $this->addError($this->getText('actions_mirror_ids_not_valid'));
                }

                $this->addView(
                    'adminDefaultSettings',
                    array($this->areMirrorsEnabledForProject())
                );

                break;
            case 'restore':
                $this->addAction('restoreRepository', array($repo_id, $this->groupId));
                break;

            #LIST
            default:
                $handled = $this->handleAdditionalAction($repository, $action);

                if (! $handled) {
                    $user_id = null;
                    $valid   = new Valid_UInt('user');
                    $valid->required();

                    if($this->request->valid($valid)) {
                        $user_id = $this->request->get('user');
                        $this->addData(array('user' => $user_id));
                    }

                    $this->addAction( 'getProjectRepositoryList', array($this->groupId, $user_id) );
                    $this->addView('index');
                }

                break;
        }
    }

    private function handleAdditionalAction(/* GitRepository */ $repository, $action) {
        $handled = false;
        $params  = array(
            'git_controller' => $this,
            'repository'     => $repository,
            'action'         => $action,
            'handled'        => &$handled
        );

        EventManager::instance()->processEvent(GIT_HANDLE_ADDITIONAL_ACTION, $params);

        return $handled;
    }

    private function getValidatedGerritTemplateId($repository) {
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

    private function gerritProjectAlreadyExists($remote_server_id, GitRepository $repo) {
        $gerrit_server       = $this->gerrit_server_factory->getServerById($remote_server_id);
        $driver              = $this->driver_factory->getDriver($gerrit_server);
        $gerrit_project_name = $driver->getGerritProjectName($repo);

        return $driver->doesTheProjectExist($gerrit_server, $gerrit_project_name);
    }

    private function processRepoManagementNotifications($pane, $repoId, $repositoryName, $user) {
        $this->addView('repoManagement');
        if ($this->request->exist('mail_prefix')) {
            $valid = new Valid_String('mail_prefix');
            $valid->required();
            $mailPrefix = $this->request->getValidated('mail_prefix', $valid, '');
            $this->addAction('notificationUpdatePrefix', array($this->groupId, $repoId, $mailPrefix, $pane));
        }
        $add_mail = $this->request->getValidated('add_mail');
        if ($add_mail) {
            $validMails = array();
            $mails      = array_map('trim', preg_split('/[,;]/', $add_mail));
            $rule       = new Rule_Email();
            $um         = UserManager::instance();
            foreach ($mails as $mail) {
                if ($rule->isValid($mail)) {
                    $validMails[] = $mail;
                } else {
                    $user = $um->findUser($mail);
                    if ($user) {
                        $mail = $user->getEmail();
                        if ($mail) {
                            $validMails[] = $mail;
                        } else {
                            $this->addError($this->getText('no_user_mail', array($mail)));
                        }
                    } else {
                        $this->addError($this->getText('no_user', array($mail)));
                    }
                }
            }
            $this->addAction('notificationAddMail', array($this->groupId, $repoId, $validMails, $pane));
        }
        $remove_mail = $this->request->get('remove_mail');
        if (is_array($remove_mail)) {
            $mails = array();
            $valid = new Valid_Email('remove_mail');
            $valid->required();
            if($this->request->validArray($valid)) {
                $mails = $this->request->get('remove_mail');
            }
            if (count($mails) > 0) {
                $this->addAction('notificationRemoveMail', array($this->groupId, $repoId, $mails, $pane));
            }
        }
        $this->addAction('redirectToRepoManagement', array($this->groupId, $repoId, $pane));
    }

    protected function _informAboutPendingEvents(/* GitRepository */ $repository) {
        $sem = SystemEventManager::instance();
        $dar = $sem->_getDao()->searchWithParam('head', $this->groupId, array('GIT_REPO_CREATE', 'GIT_REPO_DELETE'), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
        foreach ($dar as $row) {
            $p = explode(SystemEvent::PARAMETER_SEPARATOR, $row['parameters']);
            $deleted_repository = $this->factory->getDeletedRepository($p[1]);
            switch($row['type']) {
            case 'GIT_REPO_CREATE':
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_create', array($p[1])));
                break;

            case 'GIT_REPO_DELETE':
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_delete', array($deleted_repository->getFullName())));
                break;
            }
        }

        if ($repository && $repository->getId() !== 0) {
            $dar = $sem->_getDao()->searchWithParam('head', $repository->getId(), array('GIT_REPO_ACCESS'), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
            foreach ($dar as $row) {
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_access'));
            }
        }
    }

    /**
     * Instantiate an action based on a given name.
     *
     * Can be overriden to pass additionnal parameters to the action
     *
     * @param string $action The name of the action
     *
     * @return PluginActions
     */
    protected function instantiateAction($action) {
        $history_dao = new ProjectHistoryDao();

        return new $action(
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
            $this->backend_gitolite,
            $this->mirror_data_mapper,
            $history_dao,
            new GitRepositoryMirrorUpdater($this->mirror_data_mapper, $history_dao)
        );
    }

    public function _doDispatchForkCrossProject($request, $user) {
        $this->checkSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=fork_repositories');
        $validators = array(new Valid_UInt('to_project'), new Valid_String('repos'), new Valid_Array('repo_access'));

        foreach ($validators as $validator) {
            $validator->required();
            if (!$request->valid($validator)) {
                $this->addError($this->getText('missing_parameter_'. $validator->key));
                $this->redirect('/plugins/git/?group_id='.$this->groupId);
                return;
            }
        }
        $to_project_id   = $request->get('to_project');
        if ($this->permissions_manager->userIsGitAdmin($user, $this->projectManager->getProject($to_project_id))){
            $to_project      = $this->projectManager->getProject($to_project_id);
            $repos_ids       = explode(',', $request->get('repos'));
            $repos           = $this->getRepositoriesFromIds($repos_ids);
            $namespace       = '';
            $scope           = GitRepository::REPO_SCOPE_PROJECT;
            $redirect_url    = '/plugins/git/?group_id='. (int)$to_project_id;
            $forkPermissions = $this->getForkPermissionsFromRequest($request);

            $this->addAction('fork', array($repos, $to_project, $namespace, $scope, $user, $GLOBALS['HTML'], $redirect_url, $forkPermissions));
        } else {
            $this->addError($this->getText('must_be_admin_to_create_project_repo'));
        }
    }

    public function redirectNoRepositoryError() {
        $this->addError($this->getText('actions_repo_not_found'));
        $this->redirect('/plugins/git/?action=index&group_id='. $this->groupId);
    }

    protected function checkSynchronizerToken($url) {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    public function _doDispatchForkRepositories($request, $user) {
        $this->addAction('getProjectRepositoryList', array($this->groupId));
        $this->checkSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=fork_repositories');

        $repos_ids = array();

        $valid = new Valid_String('path');
        $valid->required();

        $path = '';
        if($request->valid($valid)) {
            $path = trim($request->get('path'));
        }
        $path = userRepoPath($user->getUserName(), $path);
        $forkPermissions = $this->getForkPermissionsFromRequest($request);

        $valid = new Valid_String('repos');
        $valid->required();
        $repos_ids = explode(',', $request->get('repos'));
        $to_project   = $this->projectManager->getProject($this->groupId);
        $repos        = $this->getRepositoriesFromIds($repos_ids);
        $scope        = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $redirect_url = '/plugins/git/?group_id='. (int)$this->groupId .'&user='. (int)$user->getId();
        $this->addAction('fork', array($repos, $to_project, $path, $scope, $user, $GLOBALS['HTML'], $redirect_url, $forkPermissions));

    }

    /**
     * @return array
     */
    private function getForkPermissionsFromRequest(Codendi_Request $request) {
        $fork_permissions = $request->get('repo_access');
        if ($fork_permissions) {
            return $fork_permissions;
        }
        // when we fork a gerrit repository, the repo rights cannot
        // be updated by the user on the intermediate screen and the
        // repo_access is false. Forcing it to empty array to avoid
        // fatal errors
        return array();
    }

    private function getRepositoriesFromIds($repository_ids) {
        $repositories = array();

        foreach($repository_ids as $repository_id) {
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
    public function logsDaily($params) {
        $logger  = new GitLog();
        $logger->logsDaily($params);
    }

    private function areMirrorsEnabledForProject() {
        return count($this->mirror_data_mapper->fetchAllForProject($this->project)) > 0;
    }
}
