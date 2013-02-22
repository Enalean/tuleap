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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/
 */

require_once 'constants.php';
require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');
require_once 'Git_GitoliteDriver.class.php';

/**
 * GitPlugin
 */
class GitPlugin extends Plugin {
    
    /**
     * Service short_name as it appears in 'service' table
     * 
     * Should be transfered in 'ServiceGit' class when we introduce it
     */
    const SERVICE_SHORTNAME = 'plugin_git';

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->_addHook('cssfile',                                         'cssFile',                                      false);
        $this->_addHook('javascript_file',                                 'jsFile',                                       false);
        $this->_addHook(Event::JAVASCRIPT,                                 'javascript',                                   false);
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS,                     'getSystemEventClass',                          false);
        $this->_addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES,  'getReferenceKeywords',                         false);
        $this->_addHook('get_available_reference_natures',                 'getReferenceNatures',                          false);
        $this->_addHook('SystemEvent_PROJECT_IS_PRIVATE',                  'changeProjectRepositoriesAccess',              false);
        $this->_addHook('SystemEvent_PROJECT_RENAME',                      'systemEventProjectRename',                     false);
        $this->_addHook('project_is_deleted',                              'project_is_deleted',                           false);
        $this->_addHook('file_exists_in_data_dir',                         'file_exists_in_data_dir',                      false);

        // Stats plugin
        $this->_addHook('plugin_statistics_disk_usage_collect_project',    'plugin_statistics_disk_usage_collect_project', false);
        $this->_addHook('plugin_statistics_disk_usage_service_label',      'plugin_statistics_disk_usage_service_label',   false);
        $this->_addHook('plugin_statistics_color',                         'plugin_statistics_color',                      false);

        $this->_addHook('project_admin_remove_user',                       'projectRemoveUserFromNotification',            false);

        $this->_addHook(Event::DUMP_SSH_KEYS,                              'dump_ssh_keys',                                false);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES,                     'system_event_get_types',                       false);
        $this->_addHook(Event::CHECK_AUTHORIZED_KEYS,                      'check_authorized_keys',                        false);

        $this->_addHook('permission_get_name',                             'permission_get_name',                          false);
        $this->_addHook('permission_get_object_type',                      'permission_get_object_type',                   false);
        $this->_addHook('permission_get_object_name',                      'permission_get_object_name',                   false);
        $this->_addHook('permission_get_object_fullname',                  'permission_get_object_fullname',               false);
        $this->_addHook('permission_user_allowed_to_change',               'permission_user_allowed_to_change',            false);
        $this->_addHook('permissions_for_ugroup',                          'permissions_for_ugroup',                       false);

        $this->_addHook('statistics_collector',                            'statistics_collector',                         false);

        $this->_addHook('collect_ci_triggers',                             'collect_ci_triggers',                          false);
        $this->_addHook('save_ci_triggers',                                'save_ci_triggers',                             false);
        $this->_addHook('update_ci_triggers',                              'update_ci_triggers',                           false);
        $this->_addHook('delete_ci_triggers',                              'delete_ci_triggers',                           false);

        $this->_addHook('logs_daily',                                       'logsDaily',                                   false);
        $this->_addHook('widget_instance',                                  'myPageBox',                                   false);
        $this->_addHook('widgets',                                          'widgets',                                     false);
    }

    public function site_admin_option_hook() {
        $url  = $this->getPluginPath().'/admin/';
        $name = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        echo '<li><a href="', $url, '">', $name, '</a></li>';
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GitPluginInfo')) {
            require_once('GitPluginInfo.class.php');
            $this->pluginInfo = new GitPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Returns the configuration defined for given variable name
     *
     * @param String $key
     *
     * @return Mixed
     */
    public function getConfigurationParameter($key) {
        return $this->getPluginInfo()->getPropertyValueForName($key);
    }

    public function cssFile($params) {
        // Only show the stylesheet if we're actually in the Git pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/gitphp.css" />';
        }
    }

    public function jsFile() {
        // Only show the javascript if we're actually in the Git pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/git.js"></script>';
        }
    }
    
    public function javascript($params) {
        include $GLOBALS['Language']->getContent('script_locale', null, 'git');
    }

    /**
     *This callback make SystemEvent manager knows about git plugin System Events
     * @param <type> $params
     */
    public function getSystemEventClass($params) {
        switch($params['type']) {
            case 'GIT_REPO_CREATE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_REPO_CREATE.class.php');
                $params['class'] = 'SystemEvent_GIT_REPO_CREATE';
                break;
            case 'GIT_REPO_DELETE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_REPO_DELETE.class.php');
                $params['class'] = 'SystemEvent_GIT_REPO_DELETE';
                break;
            case 'GIT_REPO_ACCESS':
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_REPO_ACCESS.class.php');
                $params['class'] = 'SystemEvent_GIT_REPO_ACCESS';
                break;
            case 'GIT_GERRIT_MIGRATION':
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_GERRIT_MIGRATION.class.php');
                $params['class'] = 'SystemEvent_GIT_GERRIT_MIGRATION';
                $params['dependencies'] = array(
                    $this->getGitDao(),
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    new BackendLogger(),
                    $this->getProjectCreator(),
                );
                break;
            default:
                break;
        }
    }

    public function getReferenceKeywords($params) {
        $params['keywords'] = array_merge($params['keywords'], array('git') );
    }

    public function getReferenceNatures($params) {
        $params['natures'] = array_merge( $params['natures'],
        array( 'git_commit'=>array('keyword'=>'git', 'label'=> $GLOBALS['Language']->getText('plugin_git', 'reference_commit_nature_key') ) ) );
    }

    public function changeProjectRepositoriesAccess($params) {
        require_once('GitActions.class.php');
        $groupId   = $params[0];
        $isPrivate = $params[1];
        $dao       = new GitDao();
        $factory   = $this->getRepositoryFactory();
        GitActions::changeProjectRepositoriesAccess($groupId, $isPrivate, $dao, $factory);
    }

    public function systemEventProjectRename($params) {
        require_once('GitActions.class.php');
        GitActions::renameProject($params['project'], $params['new_name']);
    }

    public function file_exists_in_data_dir($params) {
        require_once('GitActions.class.php');
        $params['result'] = GitActions::isNameAvailable($params['new_name'], $params['error']);
    }

    public function process() {
        require_once('Git.class.php');
        $controler = new Git($this, $this->getGerritServerFactory(), $this->getGerritDriver());
        $controler->process();
    }

    /**
     * We expect that the check fo access right to this method has already been done by the caller
     */
    public function processAdmin(Codendi_Request $request) {
        require_once GIT_BASE_DIR .'/Git/Admin.class.php';
        $admin = new Git_Admin($this->getGerritServerFactory(), new CSRFSynchronizerToken('/plugin/git/admin/'));
        $admin->process($request);
        $admin->display();
    }

    /**
     * Hook to collect docman disk size usage per project
     *
     * @param array $params
     */
    function plugin_statistics_disk_usage_collect_project($params) {
        $row = $params['project_row'];
        $sum = 0;

        // Git-Shell backend
        $path = $GLOBALS['sys_data_dir'].'/gitroot/'.strtolower($row['unix_group_name']);
        $sum += $params['DiskUsageManager']->getDirSize($path);

        // Gitolite backend
        $path = $GLOBALS['sys_data_dir'].'/gitolite/repositories/'.strtolower($row['unix_group_name']);
        $sum += $params['DiskUsageManager']->getDirSize($path);

        $params['DiskUsageManager']->_getDao()->addGroup($row['group_id'], self::SERVICE_SHORTNAME, $sum, $_SERVER['REQUEST_TIME']);
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     *
     * @param array $params
     */
    function plugin_statistics_disk_usage_service_label($params) {
        $params['services'][self::SERVICE_SHORTNAME] = 'Git';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     * 
     * @param array $params
     */
    function plugin_statistics_color($params) {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'palegreen';
        }
    }

    /**
     * Function called when a user is removed from a project
     * If a user is removed from a project wich having a private git repository, the
     * user should be removed from notification.
     *
     * @param array $params
     *
     * @return void
     */
    function projectRemoveUserFromNotification($params) {
        $groupId = $params['group_id'];
        $userId = $params['user_id'];

        $userManager = UserManager::instance();
        $user = $userManager->getUserById($userId);

        require_once('Git_PostReceiveMailManager.class.php');
        $notificationsManager = new Git_PostReceiveMailManager();
        $notificationsManager->removeMailByProjectPrivateRepository($groupId, $user);

    }

    /**
     * Called by backend to ensure that all ssh keys are in gitolite conf
     * 
     * As we are root we use a dedicated script to be run as codendiadm.
     * @see Git_Backend_Gitolite::glRenameProject
     *
     * @param array $params
     */
    public function dump_ssh_keys($params) {
        $retVal = 0;
        $output = array();
        $mvCmd  = $GLOBALS['codendi_dir'].'/src/utils/php-launcher.sh '.$GLOBALS['codendi_dir'].'/plugins/git/bin/gl-dump-sshkeys.php';
        if (isset($params['user'])) {
            $mvCmd .= ' '.$params['user']->getId();
        }
        $cmd    = 'su -l codendiadm -c "'.$mvCmd.' 2>&1"';
        exec($cmd, $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Exception('Unable to dump ssh keys (error code: '.$retVal.'): '.implode("\n", $output));
            return false;
        }
    }
    
    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
                case 'PLUGIN_GIT_READ':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_git', 'perm_R');
                    break;
                case 'PLUGIN_GIT_WRITE':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_git', 'perm_W');
                    break;
                case 'PLUGIN_GIT_WPLUS':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_git', 'perm_W+');
                    break;
                default:
                    break;
            }
        }
    }
    function permission_get_object_type($params) {
        if (!$params['object_type']) {
            if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
                $params['object_type'] = 'git_repository';
            }
        }
    }
    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
                require_once('GitRepository.class.php');
                $repository = new GitRepository();
                $repository->setId($params['object_id']);
                try {
                    $repository->load();
                    $params['object_name'] = $repository->getName();
                } catch (Exception $e) {
                    // do nothing
                }
            }
        }
    }
    function permission_get_object_fullname($params) {
        if (!$params['object_fullname']) {
            if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
                require_once('GitRepository.class.php');
                $repository = new GitRepository();
                $repository->setId($params['object_id']);
                try {
                    $repository->load();
                    $params['object_name'] = 'git repository '. $repository->getName();
                } catch (Exception $e) {
                    // do nothing
                }
            }
        }
    }
    function permissions_for_ugroup($params) {
        if (!$params['results']) {
            if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
                require_once('GitRepository.class.php');
                $repository = new GitRepository();
                $repository->setId($params['object_id']);
                try {
                    $repository->load();
                    $params['results']  = $repository->getName();
                } catch (Exception $e) {
                    // do nothing
                }
            }
        }
    }
    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            if (!$this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
                    require_once('GitRepository.class.php');
                    $repository = new GitRepository();
                    $repository->setId($params['object_id']);
                    try {
                        $repository->load();
                        //Only project admin can update perms of project repositories
                        //Only repo owner can update perms of personal repositories
                        $user = UserManager::instance()->getCurrentUser();
                        $this->_cached_permission_user_allowed_to_change = $repository->belongsTo($user) || $user->isMember($repository->getProjectId(), 'A');
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }
    
    public function system_event_get_types($params) {
        $params['types'][] = 'GIT_REPO_ACCESS';
        $params['types'][] = 'GIT_REPO_CREATE';
        $params['types'][] = 'GIT_REPO_DELETE';
        $params['types'][] = 'GIT_GERRIT_MIGRATION';
    }

    public function check_authorized_keys($params) {
        $authorized_keys_file = $this->getAuthorizedKeysPath();
        if (filesize($authorized_keys_file) == 0) {
            $params['backend']->log($authorized_keys_file." is empty", Backend::LOG_ERROR);
            throw new Exception($authorized_keys_file." is empty");
        }
    }

    private function getAuthorizedKeysPath() {
        if (!file_exists(Git_GitoliteDriver::OLD_AUTHORIZED_KEYS_PATH)) {
            return Git_GitoliteDriver::NEW_AUTHORIZED_KEYS_PATH;
        }
        return Git_GitoliteDriver::OLD_AUTHORIZED_KEYS_PATH;
    }

    /**
     * When project is deleted all its git repositories are archived and marked as deleted
     *
     * @param Array $params Parameters contining project id
     *
     * @return void
     */
    public function project_is_deleted($params) {
        if (!empty($params['group_id'])) {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            if ($project) {
                $repository_manager = $this->getRepositoryManager();
                $repository_manager->deleteProjectRepositories($project);
            }
        }
    }

    private function getRepositoryManager() {
        require_once 'GitRepositoryManager.class.php';
        return new GitRepositoryManager($this->getRepositoryFactory(), SystemEventManager::instance());
    }

    private function getRepositoryFactory() {
        require_once 'GitRepositoryFactory.class.php';
        return new GitRepositoryFactory($this->getGitDao(), ProjectManager::instance());
    }

    private function getGitDao() {
        require_once 'GitDao.class.php';
        return new GitDao();
    }

    private function getGerritDriver() {
        require_once 'Git/Driver/Gerrit.class.php';
        return new Git_Driver_Gerrit(
            new Git_Driver_Gerrit_RemoteSSHCommand(new BackendLogger()),
            new BackendLogger()
        );
    }

    private function getGerritServerFactory() {
        require_once GIT_BASE_DIR .'/Git/RemoteServer/GerritServerFactory.class.php';
        return new Git_RemoteServer_GerritServerFactory(new Git_RemoteServer_Dao(), $this->getGitDao());
    }

    /**
     * Display git backend statistics in CSV format
     *
     * @param Array $params parameters of the event
     *
     * @return void
     */
    public function statistics_collector($params) {
        if (!empty($params['formatter'])) {
            include_once('GitBackend.class.php');
            $formatter  = $params['formatter'];
            $gitBackend = Backend::instance('Git','GitBackend');
            echo $gitBackend->getBackendStatistics($formatter);
        }
    }

    /**
     * Add ci trigger information for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function collect_ci_triggers($params) {
        require_once('Git_Ci.class.php');
        $ci = new Git_Ci();
        $triggers = $ci->retrieveTriggers($params);
        $params['services'][] = $triggers;
    }

    /**
     * Save ci trigger for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function save_ci_triggers($params) {
        if (isset($params['job_id']) && !empty($params['job_id']) && isset($params['request']) && !empty($params['request'])) {
            $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
            if ($repositoryId) {
                $vRepoId = new Valid_Uint('hudson_use_plugin_git_trigger');
                $vRepoId->required();
                if($params['request']->valid($vRepoId)) {
                    require_once('Git_Ci.class.php');
                    $ci = new Git_Ci();
                    if (!$ci->saveTrigger($params['job_id'], $repositoryId)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','ci_trigger_not_saved'));
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','ci_bad_repo_id'));
                }
            }
        }
    }

    /**
     * Update ci trigger for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function update_ci_triggers($params) {
        if (isset($params['request']) && !empty($params['request'])) {
            $jobId        = $params['request']->get('job_id');
            $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
            if ($jobId) {
                $vJobId = new Valid_Uint('job_id');
                $vJobId->required();
                if($params['request']->valid($vJobId)) {
                    require_once('Git_Ci.class.php');
                    $ci = new Git_Ci();
                    $vRepoId = new Valid_Uint('hudson_use_plugin_git_trigger');
                    $vRepoId->required();
                    if ($params['request']->valid($vRepoId)) {
                        if (!$ci->saveTrigger($jobId, $repositoryId)) {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','ci_trigger_not_saved'));
                        }
                    } else {
                        if (!$ci->deleteTrigger($jobId)) {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','ci_trigger_not_deleted'));
                        }
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','ci_bad_repo_id'));
                }
            }
        }
    }

    /**
     * Delete ci trigger for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function delete_ci_triggers($params) {
        if (isset($params['job_id']) && !empty($params['job_id'])) {
            require_once('Git_Ci.class.php');
            $ci = new Git_Ci();
            if (!$ci->deleteTrigger($params['job_id'])) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','ci_trigger_not_deleted'));
            }
        }
    }

    /**
     * Add log access for git pushs
     * 
     * @param Array $params parameters of the event
     *
     * @return Void
     */
    function logsDaily($params) {
        $pm      = ProjectManager::instance();
        $project = $pm->getProject($params['group_id']);
        if ($project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            require_once('Git.class.php');
            $controler = new Git($this, $this->getGerritServerFactory(), $this->getGerritDriver());
            $controler->logsDaily($params);
        }
    }

    /**
     * Instanciate the corresponding widget
     *
     * @param Array $params Name and instance of the widget
     *
     * @return Void
     */
    function myPageBox($params) {
        switch ($params['widget']) {
            case 'plugin_git_user_pushes':
                require_once('Git_Widget_UserPushes.class.php');
                $params['instance'] = new Git_Widget_UserPushes($this->getPluginPath());
                break;
            case 'plugin_git_project_pushes':
                require_once('Git_Widget_ProjectPushes.class.php');
                $params['instance'] = new Git_Widget_ProjectPushes($this->getPluginPath());
                break;
            default:
                break;
        }
    }

    /**
     * List plugin's widgets in customize menu
     *
     * @param Array $params List of widgets
     *
     * @return Void
     */
    function widgets($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            $params['codendi_widgets'][] = 'plugin_git_user_pushes';
        }
        $request = HTTPRequest::instance();
        $groupId = $request->get('group_id');
        $pm      = ProjectManager::instance();
        $project = $pm->getProject($groupId);
        if ($project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_GROUP) {
                $params['codendi_widgets'][] = 'plugin_git_project_pushes';
            }
        }
    }

    private function getProjectCreator() {
        require_once GIT_BASE_DIR. '/Git/Driver/Gerrit/UserFinder.class.php';
        $user_finder = new Git_Driver_Gerrit_UserFinder(PermissionsManager::instance(), new UGroupManager());
        //$dir, Git_Driver_Gerrit $driver, Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_UserFinder $user_finder
        $tmp_dir = Config::get('tmp_dir') .'/gerrit_'. uniqid();
        return new Git_Driver_Gerrit_ProjectCreator($tmp_dir, $this->getGerritDriver(), $user_finder);
    }
}

?>
