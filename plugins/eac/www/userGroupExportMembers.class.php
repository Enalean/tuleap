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

require_once('pre.php'); 
 
 
Class userGroupExportMembers
{   

    var $sep =','; 

  
    /**
     * 
     * Method userList which  extract list of  users of a user group
     * 
     * @param int group_id project id
     *
     * @return null
     */
    function userList($ugroup_id, $group_id)
    {
      
        $requete_list = "SELECT  U.user_name, Ugrp.name, G.group_name".
            " FROM user U".
            "   INNER JOIN ugroup_user UU ON(U.user_id=UU.user_id)".
            "   INNER JOIN ugroup Ugrp ON( UU.ugroup_id=Ugrp.ugroup_id)".
            "   INNER JOIN groups G ON (Ugrp.group_id=G.group_id)  ".
            " WHERE UU.ugroup_id='".$ugroup_id."'AND Ugrp.group_id='".$group_id."'";
        $resultat_list = db_query($requete_list);
        return  $resultat_list;
	
    }
    /**
     * Method userList which  extract list of  users of a user group
     * 
     * @param Array ugroups contains user groups information
     * @param int $group_id project id
     *
     * @return null
     */
    function listUserFormatting(&$ugroups, $group_id)
    {
        header('Content-Disposition: filename=export_permissions.csv');
        header('Content-Type: text/csv');
        echo "User group,User name\n";
        $resultat_ugroups  = $this->listUgroups($group_id);
        while($row_ugroups = db_fetch_array($resultat_ugroups))
            {
                $ugroup_id = $row_ugroups['ugroup_id'];
                $ugroups[] = $row_ugroups;
            }
        foreach($ugroups as $ugrp)
            {
                $resultat_liste_user   = $this->userList($ugrp['ugroup_id'],$group_id);
                while ($row_liste_user = db_fetch_array($resultat_liste_user))
                    {
                        echo $ugrp['name']."".$this->sep."".$row_liste_user['user_name']."".PHP_EOL;
                    }
            }
    }
 
    /**
     * 
     * Method listUgroup which  extract user groups list for a given project
     * 
     * @param int $group_id project id
     *
     * @return null
     */
    function listUgroups($group_id)
    {
        $requete_liste_ugroups = "SELECT Ugrp.ugroup_id, Ugrp.name".
            "  FROM ugroup Ugrp".
            "  WHERE Ugrp.group_id='".$group_id."'";

        $resultat_liste_ugroups = db_query($requete_liste_ugroups);
        if($resultat_liste_ugroups && !db_error($resultat_liste_ugroups)){
            return $resultat_iste_ugroups;
        } else {
            echo "DB error: ".db_error()."<br>";
        }
    }
    
}
?>
