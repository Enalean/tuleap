<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Ikram BOUOUD, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('common/plugin/Plugin.class.php');

class EacPlugin extends Plugin {
    
    function __construct($id) {
        $this->Plugin($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('plugin_load_language_file', 'loadPluginLanguageFile', false);
        $this->_addHook('project_data_export_table', 'project_data_export_table', false);
        $this->_addHook('project_data_export_table_users', 'project_data_export_table_users', false);
    }
    
    /**
     *  method to get the plugin info to be displayed in the plugin administration
     *  @param void
     *  @return void
     */
         
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'EacPluginInfo')) {
            require_once('EacPluginInfo.class.php');
            $this->pluginInfo =& new EacPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     *  hook to load tab file related to the eac plugin
     *  @param void
     *  @return void
     */
         
    function loadPluginLanguageFile() {
        $GLOBALS['Language']->loadLanguageMsg('eac', 'eac');
    }
   
    /**
     *  hook to display the link to export project data
     *  @param void
     *  @return void
     */   
   
    function project_data_export_table($params) {
        $url  = $this->getPluginPath();
        $url .= '/export_permissions?group_id='.$params['group_id'];
        $link = '<td align="center"><a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_eac','Export_perms').'</a><br><a href ="">'.$GLOBALS['Language']->getText('plugin_eac','Show_format').'</a></td> <td align="center">-<br>-</td><td align="center">-<br>-</td>';
        echo '<tr class="'.util_get_alt_row_color($params['row_color']++).'"><td><b>'.$GLOBALS['Language']->getText('plugin_eac','Project_access_permission').''. $link.'</tr>';
    }

    /**
     *  hook to display the link to export project list of users
     *  @param void
     *  @return void
     */   
     
    function project_data_export_table_users($group_id) {
        echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td align="center"><a href="'.$this->getPluginPath().'/export_users_ugroups.php?group_id='.$group_id.'">' .
                '<b>'.$GLOBALS['Language']->getText('plugin_eac','export_definitions').'</b>' .
                        '</a></td></tr>';
    }
}

?>
