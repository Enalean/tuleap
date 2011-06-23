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

require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');
require_once('GitActions.class.php');
require_once('Git_PostReceiveMailManager.class.php');

/**
 * GitPlugin
 */
class GitPlugin extends Plugin {


    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass', false);
        $this->_addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES, 'getReferenceKeywords', false);
        $this->_addHook('get_available_reference_natures', 'getReferenceNatures', false);
        $this->_addHook('SystemEvent_PROJECT_IS_PRIVATE', 'changeProjectRepositoriesAccess', false);
        $this->_addHook('SystemEvent_PROJECT_RENAME', 'systemEventProjectRename', false);
        $this->_addHook('file_exists_in_data_dir',    'file_exists_in_data_dir',  false);

        // Stats plugin
        $this->_addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project', false);
        $this->_addHook('plugin_statistics_disk_usage_service_label',   'plugin_statistics_disk_usage_service_label',   false);
        $this->_addHook('plugin_statistics_color',                      'plugin_statistics_color',                      false);

        $this->_addHook('project_admin_remove_user', 'projectRemoveUserFromNotification', false);
        
        $this->_addHook(Event::EDIT_SSH_KEYS, 'edit_ssh_keys', false);
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GitPluginInfo')) {
            require_once('GitPluginInfo.class.php');
            $this->pluginInfo = new GitPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Git</a></li>';
    }

    public function cssFile($params) {
        // Only show the stylesheet if we're actually in the Git pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
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
            case 'GIT_REPO_CLONE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_REPO_CLONE.class.php');
                $params['class'] = 'SystemEvent_GIT_REPO_CLONE';
                break;
            case 'GIT_REPO_DELETE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_REPO_DELETE.class.php');
                $params['class'] = 'SystemEvent_GIT_REPO_DELETE';
                break;
            case 'GIT_REPO_ACCESS':
                require_once(dirname(__FILE__).'/events/SystemEvent_GIT_REPO_ACCESS.class.php');
                $params['class'] = 'SystemEvent_GIT_REPO_ACCESS';
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
        $groupId   = $params[0];
        $isPrivate = $params[1];
        GitActions::changeProjectRepositoriesAccess($groupId, $isPrivate);
    }

    public function systemEventProjectRename($params) {
        GitActions::renameProject($params['project'], $params['new_name']);
    }

    public function file_exists_in_data_dir($params) {
        $params['result'] = GitActions::isNameAvailable($params['new_name'], $params['error']);
    }

    public function process() {
        require_once('Git.class.php');
        $controler = new Git($this);
        $controler->process();
    }

    /**
     * Hook to collect docman disk size usage per project
     *
     * @param array $params
     */
    function plugin_statistics_disk_usage_collect_project($params) {
        $row  = $params['project_row'];
        $root = '/var/lib/codendi/gitroot';
        $path = $root.'/'.strtolower($row['unix_group_name']);
        $params['DiskUsageManager']->storeForGroup($row['group_id'], 'plugin_git', $path);
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     *
     * @param array $params
     */
    function plugin_statistics_disk_usage_service_label($params) {
        $params['services']['plugin_git'] = 'Git';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     * 
     * @param array $params
     */
    function plugin_statistics_color($params) {
        if ($params['service'] == 'plugin_git') {
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

        $notificationsManager = new Git_PostReceiveMailManager();
        $notificationsManager->removeMailByProjectPrivateRepository($groupId, $user);

    }

    /**
     * Called by hook when SSH keys of users are modified.
     *
     * @param array $params
     */
    public function edit_ssh_keys($params) {
        $user = UserManager::instance()->getUserById($params['user_id']);
        if ($user) {
            include_once 'GitoliteDriver.class.php';
            if (is_dir('/home/codendiadm/gitolite-admin')) {
                $gitolite = new Git_GitoliteDriver('/home/codendiadm/gitolite-admin');
                $gitolite->initUserKeys($user);
                $gitolite->push();
            }
        }
    }
}

?>
