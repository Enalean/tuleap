<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once 'constants.php';

class proftpdPlugin extends Plugin {
    const SERVICE_SHORTNAME = 'plugin_proftpd';

    public function __construct($id) {
        parent::__construct($id);
        $this->addHook('cssfile');
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook('service_is_used');
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES);
    }

    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'ProftpdPluginInfo')) {
            $this->pluginInfo = new ProftpdPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function process(HTTPRequest $request) {
        $this->getRouter()->route($request);
    }

    private function getRouter() {
        return new Tuleap\ProFTPd\ProftpdRouter(
            array(
                $this->getExplorerController(),
                $this->getAdminController(),
            )
        );
    }

    private function getExplorerController() {
        return new Tuleap\ProFTPd\Explorer\ExplorerController(
            new Tuleap\ProFTPd\Directory\DirectoryParser($this->getPluginInfo()->getPropVal('proftpd_base_directory')),
            $this->getPermissionsManager()
        );
    }

    private function getAdminController() {
        return new Tuleap\ProFTPd\Admin\AdminController(
            $this->getPermissionsManager(),
            $this->getProftpdSystemEventManager()
        );
    }

    private function getPermissionsManager() {
        return new Tuleap\ProFTPd\Admin\PermissionsManager(
            PermissionsManager::instance(),
            new UGroupManager()
        );
    }

    public function service_classnames(array $params) {
        $params['classnames']['plugin_proftpd'] = 'Tuleap\ProFTPd\ServiceProFTPd';
    }

    public function cssfile($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }

    public function getHooksAndCallbacks() {
        $this->addHook('logs_daily');
        return parent::getHooksAndCallbacks();
    }

    public function logs_daily($params) {
        $dao = new Tuleap\ProFTPd\Xferlog\Dao();

        $params['logs'][] = array(
            'sql'   => $dao->getLogQuery($params['group_id'], $params['logs_cond']),
            'field' => $GLOBALS['Language']->getText('plugin_proftpd', 'log_filepath'),
            'title' => $GLOBALS['Language']->getText('plugin_proftpd', 'log_title')
        );
    }

    public function service_is_used($params) {
        if ($params['shortname'] == self::SERVICE_SHORTNAME && $params['is_used']) {
            $group_id = $params['group_id'];
            $project_manager = ProjectManager::instance();
            $project = $project_manager->getProject($group_id);

            $this->getProftpdSystemEventManager()->queueDirectoryCreate($project->getUnixName());
        }
    }

    public function system_event_get_types($params) {
        $params['types'] = array_merge($params['types'], $this->getProftpdSystemEventManager()->getTypes());
    }

    /**
     * This callback make SystemEvent manager knows about proftpd plugin System Events
     */
    public function get_system_event_class($params) {
        $this->getProftpdSystemEventManager()->instanciateEvents(
            $params['type'],
            $params['dependencies']
        );
    }

    private function getProftpdSystemEventManager() {
        return new \Tuleap\ProFTPd\SystemEventManager(
            SystemEventManager::instance(),
            Backend::instance(),
            $this->getPermissionsManager(),
            ProjectManager::instance(),
            $this->getPluginInfo()->getPropVal('proftpd_base_directory')
        );
    }
}
