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

class userGroupExportMembers {   

    var $sep =','; 

    /**
     * Method userList which  extract list of  users of a user group
     * @param  int group_id project id
     * @param  Array ugroup_id user/group ids
     * @return null
     */

    public function userList($ugroup_id, $group_id) {
        $req_list = sprintf('SELECT  U.user_name, U.user_id, Ugrp.name, G.group_name'.
                                ' FROM user U'.
                                ' INNER JOIN ugroup_user UU ON(U.user_id=UU.user_id)'.
                                ' INNER JOIN ugroup Ugrp ON( UU.ugroup_id=Ugrp.ugroup_id)'.
                                ' INNER JOIN groups G ON (Ugrp.group_id=G.group_id)  '.
                                ' WHERE UU.ugroup_id= %d AND Ugrp.group_id= %d', db_ei($ugroup_id),db_ei($group_id));
        $result_list = db_query($req_list);
      
        if($result_list && !db_error($result_list)) {
            return  $result_list;
        } else {
            echo 'DB error:'.$GLOBALS['Response']->addFeedback('plugin_eac','db_error');
        }
    }
    
    /**
     * Method listUserFormatting to render a csv stream with all groups and related users
     * @param  Array ugroups contains user groups information
     * @param  int group_id project id
     * @return null
     */
    
    public function listUserFormatting(&$ugroups, $group_id) {
        header('Content-Disposition: filename=export_userGroups_members.csv');
        header('Content-Type: text/csv');
        echo "User group,User name\n";
        $result_ugroups  = $this->listUgroups($group_id);
        while($row_ugroups = db_fetch_array($result_ugroups)) {
            $ugroup_id = $row_ugroups['ugroup_id'];
            $ugroups[] = $row_ugroups;
        }
        foreach($ugroups as $ugrp) {
            $result_list_user   = $this->userList($ugrp['ugroup_id'],$group_id);
            while ($row_list_user = db_fetch_array($result_list_user)) {
                echo $ugrp['name']."".$this->sep."".$row_list_user['user_name']."".PHP_EOL;
            }
        }
    }
 
    /**
     * Method listUgroup which  extract user groups list for a given project
     * @param int $group_id project id
     * @return null
     */

    public function listUgroups($group_id) {
        $req_list_ugroups = sprintf('SELECT Ugrp.ugroup_id, Ugrp.name'.
                                         ' FROM ugroup Ugrp'.
                                         ' WHERE Ugrp.group_id= %d', db_ei($group_id));

        $result_list_ugroups = db_query($req_list_ugroups);
        if($result_list_ugroups && !db_error($result_list_ugroups)){
            return $result_list_ugroups;
        } else {
            echo 'DB error:'.$GLOBALS['Response']->addFeedback('plugin_eac','db_error');
        }
    }
}
?>
