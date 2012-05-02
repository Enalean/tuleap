<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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
require_once 'AdminDelegation_UserServiceManager.class.php';

/**
 * AdminDelegationPlugin
 * 
 * This plugin is made of two parts:
 * - The admin one, that allows to delegate some rights (called services to 
 *   selected users).
 * - The user one, made of widget in personal page, for the granted (selected)
 *   user to access to the information.
 * 
 * Each admin action (grant/revoke) is logged but as of today, the log is only in
 * the database.
 * 
 * There is no table dedicated to store services, the services are identified by
 * their id and a label. The id is a constant in the AdminDelegation_Service class.
 * 
 * @see AdminDelegation_Service
 * 
 */
class AdminDelegationPlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->_addHook('cssfile',                'cssFile',                false);
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->_addHook('widget_instance',        'widget_instance',        false);
        $this->_addHook('widgets',                'widgets',                false);
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'AdminDelegationPluginInfo')) {
            include_once 'AdminDelegationPluginInfo.class.php';
            $this->pluginInfo = new AdminDelegationPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Check if current user is allowed to use given widget
     * 
     * @param String  $widget
     * 
     * @return Boolean
     */
    protected function _userCanViewWidget($widget) {
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();
        if ($user) {
            $service = AdminDelegation_Service::getServiceFromWidget($widget);
            if ($service) {
                $usm = new AdminDelegation_UserServiceManager();
                return $usm->isUserGrantedForService($user, $service);
            }
        }
        return false;
    }

    public function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    /**
     * Hook: admin link to plugin
     *
     * @param Array $params
     */
    public function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Admin delegation</a></li>';
    }

    /**
     * Hook: event raised when widget are instanciated
     * 
     * @param Array $params
     */
    public function widget_instance($params) {
        if ($params['widget'] == 'admindelegation' && $this->_userCanViewWidget('admindelegation')) {
            include_once 'AdminDelegation_UserWidget.class.php';
            $params['instance'] = new AdminDelegation_UserWidget($this);
        }
        if ($params['widget'] == 'admindelegation_projects' && $this->_userCanViewWidget('admindelegation_projects')) {
            include_once 'AdminDelegation_ShowProjectWidget.class.php';
            $params['instance'] = new AdminDelegation_ShowProjectWidget($this);
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
            if ($this->_userCanViewWidget('admindelegation')) {
                include_once 'AdminDelegation_UserWidget.class.php';
                $params['codendi_widgets'][] = 'admindelegation';
            }
            if ($this->_userCanViewWidget('admindelegation_projects')) {
                include_once 'AdminDelegation_ShowProjectWidget.class.php';
                $params['codendi_widgets'][] = 'admindelegation_projects';
            }
        }
    }
}

?>