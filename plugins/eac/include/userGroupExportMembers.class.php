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

require_once('pre.php');
require_once('www/project/export/project_export_utils.php');
require_once('www/project/admin/ugroup_utils.php');

class userGroupExportMembers {   

    var $sep; 
    
    public function __construct() {
        $this->sep = get_csv_separator();
    }
    
    /**
     * Method render to render a csv stream with all groups and related users
     * @param  int group_id project id
     * @return null
     */
    public function render($group_id) {
        header('Content-Disposition: filename=export_userGroups_members.csv');
        header('Content-Type: text/csv');
        echo $GLOBALS['Language']->getText('plugin_eac', 'export_user_group');
        echo $this->sep.$GLOBALS['Language']->getText('plugin_eac', 'export_user_username', array($GLOBALS['sys_name']));
        echo $this->sep.$GLOBALS['Language']->getText('plugin_eac', 'export_user_realname').PHP_EOL;
        $ugs = ugroup_db_get_existing_ugroups($group_id, array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $um  = UserManager::instance();
        while($ugrp = db_fetch_array($ugs)) {
            if ($ugrp['ugroup_id'] <= 100) {
                $sqlUsers = ugroup_db_get_dynamic_members($ugrp['ugroup_id'], false, $group_id);
            } else {
                $sqlUsers = ugroup_db_get_members($ugrp['ugroup_id']);
            }
            $users = db_query($sqlUsers);
            while ($user = db_fetch_array($users)) {
                echo util_translate_name_ugroup($ugrp['name']).$this->sep.$user['user_name'].$this->sep.$um->getUserById($user['user_id'])->getRealname().PHP_EOL;
            }
        }
    }
}
?>
