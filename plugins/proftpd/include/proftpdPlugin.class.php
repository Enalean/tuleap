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

    public function __construct($id) {
        parent::__construct($id);
        $this->addHook('cssfile');
        $this->addHook(Event::SERVICE_CLASSNAMES);
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
        return new ProftpdRouter(
            array(
                $this->getExplorerController(),
                $this->getAdminController(),
            )
        );
    }

    private function getExplorerController() {
        return new Proftpd_ExplorerController(
            new Proftpd_Directory_DirectoryParser($this->getPluginInfo()->getPropVal('proftpd_base_directory'))
        );
    }

    private function getAdminController() {
        return new Tuleap\ProFTPd\Admin\AdminController(
            new Tuleap\ProFTPd\Admin\PermissionsManager(
                PermissionsManager::instance(),
                new UGroupManager()
            )
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
}
