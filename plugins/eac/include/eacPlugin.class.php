<?php

require_once('common/plugin/Plugin.class.php');

class EacPlugin extends Plugin {
    
    function EacPlugin($id) {
        $this->Plugin($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('project_data_export_table', 'project_data_export_table', false);
        $this->_addHook('project_data_export_table_users', 'project_data_export_table_users', false);
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'EacPluginInfo')) {
            require_once('EacPluginInfo.class.php');
            $this->pluginInfo =& new EacPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function project_data_export_table($params)
    {
        $url = $this->getPluginPath();
        $url .= '/export_permissions?group_id='.$params['group_id'];

        $link = '<td align="center"><a href="'.$url.'">Export</a><br><a href ="">Show Format</a></td> <td align="center">-<br>-</td><td align="center">-<br>-</td> ';

        echo '<tr class="'.util_get_alt_row_color($params['row_color']++).'"><td><b> Docman : Permissions information '.$link.'</tr>';
       
    }



    function project_data_export_table_users($group_id)
    {
        $url = $this->getPluginPath();
        $url .= '/export_users_ugroups.php?group_id='.$group_id;
        echo '<TR><TD> </TD> <TD> </TD> <TD>';
        echo '<br><TD align="right"><a href="'.$url.'"><B>Export Definitions</B></a></TD></TR>';
    }

}

?>
