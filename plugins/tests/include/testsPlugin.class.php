<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class testsPlugin extends Plugin {

	public function __construct($id) {
		parent::__construct($id);
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->addHook('cssfile', 'cssFile', false);
	}

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'testsPluginInfo')) {
            require_once('testsPluginInfo.class.php');
            $this->pluginInfo =& new testsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function siteAdminHooks($params)
    {
        $params['plugins'][] = array(
            'label' => 'tests',
            'href'  => $this->getPluginPath() . '/'
        );
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the tests pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

}
