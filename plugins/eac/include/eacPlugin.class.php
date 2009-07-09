<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Ikram BOUOUD, 2008
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
 *
 * 
 */
require_once('common/plugin/Plugin.class.php');

class EacPlugin extends Plugin {
    
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('project_export_entry', 'project_export_entry', false);
    }
    
    /**
     *  method to get the plugin info to be displayed in the plugin administration
     *  @param void
     *  @return void
     */
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'EacPluginInfo')) {
            require_once('EacPluginInfo.class.php');
            $this->pluginInfo = new EacPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     *  hook to display the link to export project data
     *  @param void
     *  @return void
     */   
    function project_export_entry($params) {
        // Docman perms
        $url  = $this->getPluginPath().'/export_permissions.php?group_id='.$params['group_id'];
        $params['labels']['plugin_eac_docman']                           = $GLOBALS['Language']->getText('plugin_eac','Project_access_permission');
        $params['data_export_links']['plugin_eac_docman']                = $url.'&export=csv';
        $params['data_export_format_links']['plugin_eac_docman']         = $url.'&export=format';
        $params['history_export_links']['plugin_eac_docman']             = null;
        $params['history_export_format_links']['plugin_eac_docman']      = null;
        $params['dependencies_export_links']['plugin_eac_docman']        = null;
        $params['dependencies_export_format_links']['plugin_eac_docman'] = null;
        
        // UGroups
        $params['labels']['plugin_eac_ugroups']                           = $GLOBALS['Language']->getText('plugin_eac','export_definitions');
        $params['data_export_links']['plugin_eac_ugroups']                = $this->getPluginPath().'/export_users_ugroups.php?group_id='.$params['group_id'];
        $params['data_export_format_links']['plugin_eac_ugroups']         = null;
        $params['history_export_links']['plugin_eac_ugroups']             = null;
        $params['history_export_format_links']['plugin_eac_ugroups']      = null;
        $params['dependencies_export_links']['plugin_eac_ugroups']        = null;
        $params['dependencies_export_format_links']['plugin_eac_ugroups'] = null;
    }
}

?>
