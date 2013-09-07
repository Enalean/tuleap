<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

require_once 'common/plugin/Plugin.class.php';
require_once 'autoload.php';

class testingPlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('cssfile');
    }

    /**
     * @return TestingPluginInfo
     */
    public function getPluginInfo() {
        if (! $this->pluginInfo) {
            include_once 'TestingPluginInfo.class.php';
            $this->pluginInfo = new TestingPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the testing pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function process(Codendi_Request $request) {
        $project = $request->getProject();
        $service = $project->getService('plugin_testing');
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_testing', 'descriptor_name'))
            );
        }

        $router = new TestingRouter();
        $router->route($request);
    }
}
