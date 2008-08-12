<?php 
  /**
   *Document created by Ikram Bououd  
   *This document exports user group's members 
   *
   *
   */

require_once('pre.php'); 
 
 
Class UserGroupExportMembers
{   

    var $sep =','; 

  
    /**
     * Short desc:
     * 
     * userList  extracts list of  users of a user group
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
     * Short desc:
     * 
     * userList  extracts list of  users of a user group
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
        $resultat_ugroups = $this->listUgroups($group_id);
        while($row_ugroups = db_fetch_array($resultat_ugroups))
            {
                $ugroup_id = $row_ugroups['ugroup_id'];
                $ugroups[] = $row_ugroups;
            }
        foreach($ugroups as $ugrp)
            {
                $resultat_liste_user = $this->userList($ugrp['ugroup_id'],$group_id);
                while ($row_liste_user = db_fetch_array($resultat_liste_user))
                    {
                        echo $ugrp['name']."".$this->sep."".$row_liste_user['user_name']."".PHP_EOL;
                    }
            }
    }
 
    /**
     * Short desc:
     * 
     * listUgroup extracts user groups list for a given project
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
/**
 * Short desc:
 * 
 * identifyProject  identifies project id
 * 
 * @return int $group_id project id
 */

function identifyProject()
{
    $requete_Id_project = "SELECT G.group_id".
        " FROM groups G".
        " WHERE G.group_name='HTIProject'";//just for test

    $resultat_Id_project = db_query($requete_Id_project);
    $row_Id_project = db_fetch_array($resultat_Id_project);
    return $row_Id_project['group_id'];

}	  


$group_id = identifyProject();
$ugroups=array();	
//  $group_id=$request->get('group_id');
$MemberShower = new UserGroupExportMembers();
$MemberShower->listUserFormatting($ugroups, $group_id);

?>