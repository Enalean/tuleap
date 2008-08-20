<?php

require_once('common/plugin/Plugin.class.php');

class EacPlugin extends Plugin {
    function EacPlugin($id) {
       
        $this->Plugin($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('project_data_export_table', 'project_data_export_table', false);
        $this->_addHook('project_data_export_table_users', 'project_data_export_table_users', false);
        $this->_addHook('plugin_load_language_file', 'loadPluginLanguageFile', false);
        $this->_addHook('permissions_ugroup_properties', 'permissions_ugroup_properties', false);
      
       
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'EacPluginInfo')) {
            require_once('EacPluginInfo.class.php');
            $this->pluginInfo =& new EacPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    function loadPluginLanguageFile() {
        $GLOBALS['Language']->loadLanguageMsg('docman_watermark', 'docman_watermark');
    }
   
    function project_data_export_table($params)
    {
      
        $url  = $this->getPluginPath();
        $url .= '/export_permissions?group_id='.$params['group_id'];
        $link = '<td align="center"><a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_eac','Export_perms').'</a><br><a href ="">'.$GLOBALS['Language']->getText('plugin_eac','Show_format').'</a></td> <td align="center">-<br>-</td><td align="center">-<br>-</td>';
        echo '<tr class="'.util_get_alt_row_color($params['row_color']++).'"><td><b>'.$GLOBALS['Language']->getText('plugin_eac','Project_access_permission').''. $link.'</tr>';
       
    }



    function project_data_export_table_users($group_id)
    {
       
        $url  = $this->getPluginPath();
        $url .= '/export_users_ugroups.php?group_id='.$group_id;
        echo '<tr><td> </td> <td> </td> <td>';
        echo '<br><td align="right"><a href="'.$url.'"><B>'.$GLOBALS['Language']->getText('plugin_eac','export_definitions').'</B></a></td></tr >';
    }
    function permissions_ugroup_properties($params)
    {
        $url  = $this->getPluginPath();
        $url2 =  $url.'/export_admin_permission_property.php?group_id='.$params['group_id'].'&object_id='.$params['object_id'].'&permission_type='.$params['permission_type'];
        $url .= '/export_admin_item_property.php?group_id='.$params['group_id'].'&object_id='.$params['object_id']; 
        
        echo '<td  align="center"><a href='.$url.'>Export properties</a><br><a href='.$url2.'>Permission info</a></TD>';
    }


}

?>
