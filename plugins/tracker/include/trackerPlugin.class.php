<?php

/*
 * Copyright (c) Xerox, 2011. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2011. Xerox Codendi Team.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/plugin/Plugin.class.php');

define('TRACKER_BASE_URL', '/plugins/tracker');

/**
 * trackerPlugin
 */
class trackerPlugin extends Plugin {
	
	public function __construct($id) {
		parent::__construct($id);
		$this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook(Event::SERVICE_CLASSNAMES, 'service_classnames', false);
        $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);
	}
	
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'trackerPluginInfo')) {
            include_once('trackerPluginInfo.class.php');
            $this->pluginInfo = new trackerPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssFile($params) {
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/print.css" media="print" />';
        }
    }
    
    public function service_classnames($params) {
        include_once 'ServiceTracker.class.php';
        $params['classnames']['plugin_tracker'] = 'ServiceTracker';
    }
    
    public function combined_scripts($params) {
        array_merge(
            $params['scripts'],
            array(
                '/plugins/tracker/scripts/TrackerReports.js',
                '/plugins/tracker/scripts/TrackerBinds.js',
                '/plugins/tracker/scripts/ReorderColumns.js',
                '/plugins/tracker/scripts/TrackerTextboxLists.js',
                '/plugins/tracker/scripts/TrackerAdminFields.js',
                '/plugins/tracker/scripts/TrackerArtifact.js',
                '/plugins/tracker/scripts/TrackerArtifactLink.js',
                '/plugins/tracker/scripts/TrackerFormElementFieldPermissions.js',
                '/plugins/tracker/scripts/TrackerFieldDependencies.js',
            )
        );
        $params['classnames']['plugin_tracker'] = 'ServiceTracker';
    }
}

?>