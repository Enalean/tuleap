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
require_once 'autoload.php';
require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');

/**
 * GitPlugin
 */
class GitPlugin extends Plugin {

    /**
     *
     * @var BackendLogger
     */
    private $logger;

    /**
     * @var Git_UserAccountManager
     */
    private $user_account_manager;
    
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
        $this->_addHook(Event::GET_AVAILABLE_REFERENCE_NATURE,             'getReferenceNatures',                          false);
        $this->addHook(Event::GET_REFERENCE);
        $this->_addHook('SystemEvent_PROJECT_IS_PRIVATE',                  'changeProjectRepositoriesAccess',              false);
        $this->_addHook('SystemEvent_PROJECT_RENAME',                      'systemEventProjectRename',                     false);
        $this->_addHook('project_is_deleted',                              'project_is_deleted',                           false);
        $this->_addHook('file_exists_in_data_dir',                         'file_exists_in_data_dir',                      false);

        // Stats plugin
        $this->_addHook('plugin_statistics_disk_usage_collect_project',    'plugin_statistics_disk_usage_collect_project', false);
        $this->_addHook('plugin_statistics_disk_usage_service_label',      'plugin_statistics_disk_usage_service_label',   false);
        $this->_addHook('plugin_statistics_color',                         'plugin_statistics_color',                      false);

        $this->_addHook(Event::LIST_SSH_KEYS,                              'getRemoteServersForUser',                      false);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES,                     'system_event_get_types',                       false);
        $this->_addHook(Event::DUMP_SSH_KEYS);
        $this->_addHook(Event::PROCCESS_SYSTEM_CHECK);

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

        // User Group membership modification
        $this->_addHook('project_admin_add_user');
        $this->_addHook('project_admin_ugroup_add_user');
        $this->_addHook('project_admin_remove_user');
        $this->_addHook('project_admin_ugroup_remove_user');
        $this->_addHook('project_admin_change_user_permissions');
        $this->_addHook('project_admin_ugroup_deletion');
        $this->_addHook('project_admin_remove_user_from_project_ugroups');
        $this->_addHook('project_admin_ugroup_creation');
        $this->_addHook(Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD);
        $this->_addHook(Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE);
    }

    public function site_admin_option_hook() {
        $url  = $this->getPluginPath().'/admin/';
        $name = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        echo '<li><a href="', $url, '">', $name, '</a></li>';
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GitPluginInfo')) {
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

    public function system_event_get_types($params) {
        $params['types'] = array_merge($params['types'], $this->getGitSystemEventManager()->getTypes());
    }

    /**
     *This callback make SystemEvent manager knows about git plugin System Events
     * @param <type> $params
     */
    public function getSystemEventClass($params) {
        switch($params['type']) {
            case SystemEvent_GIT_REPO_UPDATE::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_UPDATE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory()
                );
                break;
            case SystemEvent_GIT_REPO_DELETE::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_DELETE';
                break;
            case SystemEvent_GIT_REPO_ACCESS::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_ACCESS';
                break;
            case SystemEvent_GIT_GERRIT_MIGRATION::NAME:
                $params['class'] = 'SystemEvent_GIT_GERRIT_MIGRATION';
                $params['dependencies'] = array(
                    $this->getGitDao(),
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    $this->getLogger(),
                    $this->getProjectCreator(),
                );
                break;
            case SystemEvent_GIT_REPO_FORK::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_FORK';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory()
                );
                break;
            case SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME:
                $params['class'] = 'SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP';
                $params['dependencies'] = array(
                    $this->getGerritServerFactory(),
                    $this->getGitoliteSSHKeyDumper(),
                );
                break;
            default:
                break;
        }
    }

    public function getReferenceKeywords($params) {
        $params['keywords'] = array_merge(
            $params['keywords'],
            array(Git::REFERENCE_KEYWORD)
        );
    }

    public function getReferenceNatures($params) {
        $params['natures'] = array_merge(
            $params['natures'],
            array(
                Git::REFERENCE_NATURE => array(
                    'keyword' => Git::REFERENCE_KEYWORD,
                    'label'   => $GLOBALS['Language']->getText('plugin_git', 'reference_commit_nature_key')
                )
            )
        );
    }

    public function get_reference($params) {
        if ($params['keyword'] == Git::REFERENCE_KEYWORD) {
            $reference = false;
            if ($params['project']) {
                $git_reference_manager = new Git_ReferenceManager(
                    $this->getRepositoryFactory(),
                    $params['reference_manager']
                );
                $reference = $git_reference_manager->getReference(
                    $params['project'],
                    $params['keyword'],
                    $params['value']
                );
            }
            $params['reference'] = $reference;
        }
    }

    public function changeProjectRepositoriesAccess($params) {
        $groupId   = $params[0];
        $isPrivate = $params[1];
        $dao       = new GitDao();
        $factory   = $this->getRepositoryFactory();
        GitActions::changeProjectRepositoriesAccess($groupId, $isPrivate, $dao, $factory);
    }

    public function systemEventProjectRename($params) {
        GitActions::renameProject($params['project'], $params['new_name']);
    }

    public function file_exists_in_data_dir($params) {
        $params['result'] = GitActions::isNameAvailable($params['new_name'], $params['error']);
    }

    public function process() {
        $this->getGitController()->process();
    }

    /**
     * We expect that the check fo access right to this method has already been done by the caller
     */
    public function processAdmin(Codendi_Request $request) {
        require_once 'common/include/CSRFSynchronizerToken.class.php';
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
    private function projectRemoveUserFromNotification($params) {
        $groupId = $params['group_id'];
        $userId = $params['user_id'];

        $userManager = UserManager::instance();
        $user = $userManager->getUserById($userId);

        $notificationsManager = new Git_PostReceiveMailManager();
        $notificationsManager->removeMailByProjectPrivateRepository($groupId, $user);

    }

    /**
     * Hook. Call by backend when SSH keys are modified
     *
     * @param array $params Should contain two entries:
     *     'user' => PFUser,
     *     'original_keys' => string of concatenated ssh keys
     */
    public function dump_ssh_keys(array $params) {
        $this->dump_ssh_keys_gitolite($params);
        $this->dump_ssh_keys_gerrit($params);
    }

    /**
     * Called by backend to ensure that all ssh keys are in gitolite conf
     * 
     * As we are root we use a dedicated script to be run as codendiadm.
     * @see Git_Backend_Gitolite::glRenameProject
     *
     * @param array $params
     */
    protected function dump_ssh_keys_gitolite(array $params) {
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

    /**
     * Method called as a hook.
     *
     * @param array $params Should contain two entries:
     *     'user' => PFUser,
     *     'original_keys' => string of concatenated ssh keys
     * 
     * @return void
     */
    protected function dump_ssh_keys_gerrit(array $params) {
        if (! $user = $this->getUserFromParameters($params)) {
            return;
        }

        $user                     = $params['user'];
        $git_user_account_manager = $this->getUserAccountManager();
        $new_keys                 = $user->getAuthorizedKeysArray();
        $original_keys            = array();

        if (isset($params['original_keys']) && is_string($params['original_keys'])) {
            $original_keys = $this->getKeysFromString($params['original_keys']);
        }

        try {
            $git_user_account_manager->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $user
            );
        } catch (Git_UserSynchronisationException $e) {
            $this->getLogger()->error('Unable to propagate ssh keys for user: ' . $user->getUnixName());
        }
    }

    private function getKeysFromString($keys_as_string) {
        $user = new PFUser();
        $user->setAuthorizedKeys($keys_as_string);

        return array_filter($user->getAuthorizedKeysArray());
    }

    /**
     *
     * @param PFUser $user
     * @return \Git_UserAccountManager
     */
    private function getUserAccountManager() {
        if (! $this->user_account_manager) {
            $this->user_account_manager = new Git_UserAccountManager($this->getGerritDriver(), $this->getGerritServerFactory());
        }

        return $this->user_account_manager;
    }

    /**
     *
     * @param Git_UserAccountManager $manager
     */
    public function setUserAccountManager(Git_UserAccountManager $manager) {
        $this->user_account_manager = $manager;
    }

    /**
     * Method called as a hook.
     *
     * @param array $params Should contain two entries:
     *     'user' => PFUser,
     *     'html' => string An emty string of html output- passed by reference
     */
    public function getRemoteServersForUser(array $params) {
        if (! $user = $this->getUserFromParameters($params)) {
            return;
        }

        if (! isset($params['html']) || ! is_string($params['html'])) {
            return;
        }
        $html = $params['html'];

        $remote_servers = $this->getGerritServerFactory()->getRemoteServersForUser($user);

        if (count($remote_servers) > 0) {
            $html = '<br />
                <br />
                <hr />
                <br />'.
                $GLOBALS['Language']->getText('plugin_git', 'push_ssh_keys_info').
                '<ul>';

            foreach ($remote_servers as $server) {
                $html .= '<li>
                        <a href="'.$server->getHost().':'.$server->getHTTPPort().'/#/settings/ssh-keys">'.
                            $server->getHost().'
                        </a>
                    </li>';
            }

            $html .= '</ul>
                <form action="" method="post">
                    <input type="submit"
                        title="'.$GLOBALS['Language']->getText('plugin_git', 'push_ssh_keys_button_title').'"
                        value="'.$GLOBALS['Language']->getText('plugin_git', 'push_ssh_keys_button_value').'"
                        name="ssh_key_push"/>
                </form>';
        }

        if (isset($_POST['ssh_key_push'])) {
            $this->pushUserSSHKeysToRemoteServers($user);
            $GLOBALS['Response']->displayFeedback();
        }

        $params['html'] = $html;
    }

    /**
     * Method called as a hook.

     * Copies all SSH Keys to Remote Git Servers
     * @param PFUser $user
     */
    private function pushUserSSHKeysToRemoteServers(PFUser $user) {
        $this->getLogger()->info('Trying to push ssh keys for user: '.$user->getUnixName());
        $git_user_account_manager = $this->getUserAccountManager();

        try {
            $git_user_account_manager->pushSSHKeys(
                $user
            );
        } catch (Git_UserSynchronisationException $e) {
            $message = $GLOBALS['Language']->getText('plugin_git','push_ssh_keys_error');
            $GLOBALS['Response']->addFeedback('error', $message);

            $this->getLogger()->error('Unable to push ssh keys: ' . $e->getMessage());
            return;
        }

        $this->getLogger()->info('Successfully pushed ssh keys for user: '.$user->getUnixName());
    }

    private function getUserFromParameters($params) {
        if (! isset($params['user']) || ! $params['user'] instanceof PFUser) {
            $this->getLogger()->error('Invalid user passed in params: ' . print_r($params, true));
            return false;
        }

        return $params['user'];
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
    
    public function proccess_system_check($params) {
        $gitolite_driver = new Git_GitoliteDriver();
        $gitolite_driver->checkAuthorizedKeys();
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
            if ($params['request']->get('hudson_use_plugin_git_trigger_checkbox')) {
                $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
                if ($repositoryId) {
                    $vRepoId = new Valid_Uint('hudson_use_plugin_git_trigger');
                    $vRepoId->required();
                    if($params['request']->valid($vRepoId)) {
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
            $controler = $this->getGitController();
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
                $params['instance'] = new Git_Widget_UserPushes($this->getPluginPath());
                break;
            case 'plugin_git_project_pushes':
                $params['instance'] = new Git_Widget_ProjectPushes($this->getPluginPath());
                break;
            default:
                break;
        }
    }

    public function project_admin_remove_user_from_project_ugroups($params) {
        foreach ($params['ugroups'] as $ugroup_id) {
            $this->project_admin_ugroup_remove_user(
                array(
                    'group_id'  => $params['group_id'],
                    'user_id'   => $params['user_id'],
                    'ugroup_id' => $ugroup_id,
                )
            );
        }
    }

    public function project_admin_change_user_permissions($params) {
        if ($params['user_permissions']['admin_flags'] == 'A') {
            $params['ugroup_id'] = UGroup::PROJECT_ADMIN;
            $this->project_admin_ugroup_add_user($params);
        } else {
            $params['ugroup_id'] = UGroup::PROJECT_ADMIN;
            $this->project_admin_ugroup_remove_user($params);
        }
    }

    public function project_admin_ugroup_deletion($params) {
        $ugroup = $params['ugroup'];
        $users  = $ugroup->getMembers();

        foreach ($users as $user) {
            $calling = array(
                'group_id' => $params['group_id'],
                'user_id'  => $user->getId(),
                'ugroup'   => $ugroup
            );
            $this->project_admin_ugroup_remove_user($calling);
        }
    }

    public function project_admin_add_user($params) {
        $params['ugroup_id'] = UGroup::PROJECT_MEMBERS;
        $this->project_admin_ugroup_add_user($params);
    }

    public function project_admin_remove_user($params) {
        $params['ugroup_id'] = UGroup::PROJECT_MEMBERS;
        $this->project_admin_ugroup_remove_user($params);
        $this->projectRemoveUserFromNotification($params);
    }

    public function project_admin_ugroup_add_user($params) {
        $this->getGerritMembershipManager()->addUserToGroup(
            $this->getUserFromParams($params),
            $this->getUGroupFromParams($params)
        );
    }

    public function project_admin_ugroup_remove_user($params) {
        $this->getGerritMembershipManager()->removeUserFromGroup(
            $this->getUserFromParams($params),
            $this->getUGroupFromParams($params)
        );
    }

    public function project_admin_ugroup_creation($params) {
        $this->getGerritMembershipManager()->createGroupOnProjectsServers(
            $this->getUGroupFromParams($params)
        );
    }

    public function ugroup_manager_update_ugroup_binding_add($params) {
        $this->getGerritMembershipManager()->addUGroupBinding(
            $params['ugroup'],
            $params['source']
        );
    }

    public function ugroup_manager_update_ugroup_binding_remove($params) {
        $this->getGerritMembershipManager()->removeUGroupBinding(
            $params['ugroup']
        );
    }

    private function getUserFromParams(array $params) {
        return UserManager::instance()->getUserById($params['user_id']);
    }


    private function getUGroupFromParams(array $params) {
        if (isset($params['ugroup'])) {
            return $params['ugroup'];
        } else {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            return $this->getUGroupManager()->getUGroup($project, $params['ugroup_id']);
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
        $tmp_dir = Config::get('tmp_dir') .'/gerrit_'. uniqid();
        return new Git_Driver_Gerrit_ProjectCreator(
            $tmp_dir,
            $this->getGerritDriver(),
            $this->getGerritUserFinder(),
            $this->getUGroupManager(),
            $this->getGerritMembershipManager(),
            $this->getProjectManager()
        );
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    private function getGerritUserFinder() {
        return new Git_Driver_Gerrit_UserFinder(PermissionsManager::instance(), $this->getUGroupManager());
    }

    private function getGitController() {
        return new Git(
            $this,
            $this->getGerritServerFactory(),
            $this->getGerritDriver(),
            $this->getRepositoryManager(),
            $this->getGitSystemEventManager(),
            new Git_Driver_Gerrit_UserAccountManager($this->getGerritDriver(), $this->getGerritServerFactory())
        );
    }

    private function getGitSystemEventManager() {
        return new Git_SystemEventManager(SystemEventManager::instance());
    }

    private function getRepositoryManager() {
        return new GitRepositoryManager(
            $this->getRepositoryFactory(),
            $this->getGitSystemEventManager(),
            $this->getGitDao()
        );
    }

    private function getRepositoryFactory() {
        return new GitRepositoryFactory($this->getGitDao(), ProjectManager::instance());
    }

    private function getGitDao() {
        return new GitDao();
    }

    private function getGerritDriver() {
        return new Git_Driver_Gerrit(
            new Git_Driver_Gerrit_RemoteSSHCommand($this->getLogger()),
            $this->getLogger()
        );
    }

    /**
     *
     * @return BackendLogger
     */
    private function getLogger() {
        if (!$this->logger) {
            $this->logger = new BackendLogger();
        }
        return $this->logger;
    }

    /**
     *
     * @param BackendLogger $logger
     */
    public function setLogger(BackendLogger $logger) {
        $this->logger = $logger;
    }

    private function getGerritMembershipManager() {
        return new Git_Driver_Gerrit_MembershipManager(
            new Git_Driver_Gerrit_MembershipDao(),
            $this->getGerritDriver(),
            new Git_Driver_Gerrit_UserAccountManager($this->getGerritDriver(), $this->getGerritServerFactory()),
            $this->getGerritServerFactory(),
            $this->getLogger(),
            $this->getUGroupManager(),
            $this->getProjectManager()
        );
    }

    protected function getGerritServerFactory() {
        return new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            $this->getGitDao(),
            $this->getGitSystemEventManager()
        );
    }

    private function getGitoliteSSHKeyDumper() {
        $gitolite_admin_path = $GLOBALS['sys_data_dir'] . '/gitolite/admin';
        return new Git_Gitolite_SSHKeyDumper(
            $gitolite_admin_path,
            new Git_Exec($gitolite_admin_path)
        );
    }

    private function getUGroupManager() {
        return new UGroupManager();
    }
}

?>
