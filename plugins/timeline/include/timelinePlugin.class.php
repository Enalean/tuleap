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

require_once 'common/plugin/Plugin.class.php';

/**
 * TimelinePlugin
 */
class TimelinePlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('cssfile',                'cssfile',                false);
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->_addHook('widget_instance',        'widget_instance',        false);
        $this->_addHook('widgets',                'widgets',                false);
    }

    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'TimelinePluginInfo.class.php';
            $this->pluginInfo = new TimelinePluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the widget pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    /**
     * Hook: event raised when widget are instanciated
     * 
     * @param Array $params
     */
    public function widget_instance($params) {
        if ($params['widget'] == 'timeline_user') {
            include_once 'Timeline_Widget_User.class.php';
            $params['instance'] = new Timeline_Widget_User($this);
        }
    }

    /**
     * Hook: event raised when user lists all available widget
     *
     * @param Array $params
     */
    public function widgets($params) {
        include_once 'common/widget/WidgetLayoutManager.class.php';

        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            include_once 'Timeline_Widget_User.class.php';
            $params['codendi_widgets'][] = 'timeline_user';
        }
    }
}

?>