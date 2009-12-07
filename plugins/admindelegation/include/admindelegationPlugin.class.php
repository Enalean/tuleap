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
 */
class AdminDelegationPlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
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

    protected function _userCanViewWidget() {
        $allowed = false;
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();
        if ($user) {
            $usm = new AdminDelegation_UserServiceManager();
            $allowed = $usm->isUserGranted($user);
        }
        return $allowed;
    }

    public function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Admin delegation</a></li>';
    }

    public function widget_instance($params) {
        if ($params['widget'] == 'admindelegation' && $this->_userCanViewWidget()) {
            include_once 'AdminDelegation_UserWidget.class.php';
            $params['instance'] = new AdminDelegation_UserWidget($this);
        }
    }

    public function widgets($params) {
        include_once 'common/widget/WidgetLayoutManager.class.php';
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER  && $this->_userCanViewWidget()) {
            include_once 'AdminDelegation_UserWidget.class.php';
            $params['codendi_widgets'][] = 'admindelegation';
        }
    }
}

?>