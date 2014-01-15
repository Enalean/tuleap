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

    const BASE_DIRECTORY = '/tmp';

    public function __construct($id) {
        parent::__construct($id);
        $this->_addHook('cssfile', 'cssFile', false);
    }

    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'ProftpdPluginInfo')) {
            $this->pluginInfo = new ProftpdPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Same as process() but adds the Forge header and footer
     * @param HTTPRequest $request
     */
    public function processUiRequest(HTTPRequest $request) {
        $this->displayHeader($request);
        $this->process($request);
        $this->displayFooter();
    }

    public function process(HTTPRequest $request) {
        $router = new ProftpdRouter();
        $router->route($request);
    }

    private function displayHeader($request) {
        $params = array(
            'title'     => $GLOBALS['Language']->getText('plugin_proftpd', 'service_lbl_key'),
            'pagename'  => $GLOBALS['Language']->getText('plugin_proftpd', 'service_lbl_key'),
            'toptab'    => "proftpd",
        );

        if ($request->get('group_id')) {
            $params['group'] = $request->get('group_id');
        }

        site_header($params);
    }

    private function displayFooter() {
        site_footer(array());
    }

    public function cssFile($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
}