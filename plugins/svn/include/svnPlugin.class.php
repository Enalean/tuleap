<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

use \Tuleap\Svn\Explorer\ExplorerController;
use \Tuleap\Svn\CodeBrowser\CodeBrowserController;
use \Tuleap\Svn\SvnRouter;
use \Tuleap\Svn\Repository\RepositoryManager;
use \Tuleap\Svn\Dao;

/**
 * SVN plugin
 */
class SvnPlugin extends Plugin {
    const SERVICE_SHORTNAME = 'plugin_svn';

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'SvnPluginInfo')) {
            $this->pluginInfo = new SvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    private function getRouter() {
        return new SvnRouter(
            array(
                $this->getExplorerController(),
                $this->getCodeBrowserController()
            )
        );
    }

    public function process(HTTPRequest $request) {
        $this->getRouter()->route($request);
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e804';
    }

    public function service_classnames(array $params) {
        $params['classnames'][$this->getServiceShortname()] = 'Tuleap\Svn\ServiceSvn';
    }

    private function getExplorerController() {
        return new ExplorerController(new RepositoryManager(new Dao()));
    }

    private function getCodeBrowserController() {
        return new CodeBrowserController();
    }
}