<?php 
  /**
   *Document created by Ikram Bououd  
   *This document exports user group's permissions on 
   *folders and documents in CSV format
   *
   */

require_once('pre.php');
require_once(dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
require_once('common/user/UserManager.class.php');

/**
 *ShowPermsVisitor
 *
 */

class ShowPermsVisitor
{
 
    var  $allTreeItems = array();
    var  $sep          = ',';
    /**
     * Short desc:
     * visitFolder memorize Folders id and title and walk through its tree 
     * 
     * @param Tree  $item is the Folder tree
     * @param Array $docmanItem containing information about Docman items
     *
     * @return null
     */
   
    function visitFolder($item ,$docmanItem)
    {
       
        $Id = $item->getId();
        $Title = $item->getTitle();  
        $docmanItem[$Id] = $Title;
        $this->allTreeItems[] = $docmanItem;
        // Walk through the tree
        $items = $item->getAllItems();
        $it =& $items->iterator();
        while($it->valid()){
            $i =& $it->current();
            $i->accept($this, $docmanItem);
            $it->next();
        }
    }
    /**
     * Short desc:
     * visitWiki memorize Wikipage id and title
     * 
     * @param Tree  $item is the Wikipage
     * @param Array $docmanItem is the array containing information about each Docman items
     *
     * @return null
     */
   
    function visitWiki($item ,$docmanItem)
    {
        $Id = $item->getId();
        $Title = $item->getTitle(); 
        $docmanItem[$Id] = $Title;
        $this->allTreeItems[] = $docmanItem;
    }
    /**
     * Short desc:
     * visitEmpty memorize Empty file id and title
     * 
     * @param Tree $item is the Empty file
     * @param Array $docmanItem is the array containing information about each Docman items
     *
     * @return null
     */
    function visitEmpty($item ,$docmanItem)
    {
        $Id = $item->getId();
        $Title = $item->getTitle(); 
        $docmanItem[$Id] = $Title;
        $this->allTreeItems[] = $docmanItem;
    }
    /**
     * Short desc:
     * visitEmbeddedFile memorize Embedded File id and title
     * 
     * @param Tree $item is the Embedded File
     * @param Array $docmanItem is the array containing information about each Docman items
     *
     * @return null
     */
    function visitEmbeddedFile($item ,$docmanItem)
    {
        $Id = $item->getId();
        $Title = $item->getTitle(); 
        $docmanItem[$Id] = $Title;
        $this->allTreeItems[] = $docmanItem;
    }
    /**
     * Short desc:
     * visitLink memorize Link id and title
     * 
     * @param Tree $item is the Link
     * @param Array $docmanItem is the array containing information about each Docman items
     *
     * @return null
     */
    function visitLink($item ,$docmanItem)
    {
        $Id = $item->getId();
        $Title = $item->getTitle(); 
        $docmanItem[$Id] = $Title;
        $this->allTreeItems[] = $docmanItem;
       
    } 
     /**
     * Short desc:
     * visitFile memorize File  id and title
     * 
     * @param Tree $item is the Link
     * @param Array $docmanItem is the array containing information about each Docman items
     *
     * @return null
     */
    function visitFile($item ,$docmanItem)
    {
        $Id = $item->getId();
        $Title = $item->getTitle(); 
        $docmanItem[$Id] = $Title;
        $this->allTreeItems[] = $docmanItem;
       
    }
    /**
     * Short desc:
     *  itemFullName append the full name  of the Doc/Folder and memorize its Id and its full name in listItem
     * 
     * @param Array $Id contains item's id and short name
     * @param Array $listItem will memorize item id and its full name
     *
     * @return null
     *
     */ 
    function itemFullName($Id ,&$listItem)
    { 
        $item_full_name = '';
        $item_id = '';
        foreach($Id as $id=>$idDoc)     
            {     
                $item_full_name .= $idDoc."/";
                $item_id = $id;
            }
        $listItem[$item_id] = $item_full_name;

    }
    /**
     * Short desc:
     * 
     * cvsFormatting extracts information from arraies and print them in the csv format
     * 
     * @param Array &$ugroups will memorize all ugroup's name and id
     * @param Array &$listItem contains items ids and names
     * @param int   $group_id project id
     *
     * @return null
     */
    function csvFormatting(&$ugroups ,&$listItem ,$group_id)
    {
        header('Content-Disposition: filename=export_permissions.csv');
        header('Content-Type: text/csv');

        echo "Document/Folder,User group,Read,Write,Manage\n";
        $resultat_ugroups = $this->listUgroups($group_id);
        while($row_ugroups = db_fetch_array($resultat_ugroups))
            {
                $ugroup_id = $row_ugroups['ugroup_id'];
                $ugroups[] = $row_ugroups;
            }
        foreach ($this->allTreeItems as $folder_id )
            {
                $this->itemFullName($folder_id,$listItem);  
            }

        foreach($listItem as $item_id=>$item)
            {
                foreach($ugroups as $ugrp){
                    $resultat_permissions = $this->extractPermissions($group_id,$ugrp['ugroup_id'],$item_id);
                    while ($row_permissions = db_fetch_array($resultat_permissions))
                        {
                            $permission = $this->permissionFormatting($row_permissions['permission_type']);
                            echo $item."".$this->sep."".$ugrp['name']."".$this->sep."".$permission."".PHP_EOL;
                        }
      
                }
            }
    }
    /**
     * Short desc:
     * permissionFormatting print information about permission in csv format
     * 
     * @param Tree $item is the Embedded File
     *
     * @return String 
     */
    function permissionFormatting($permissionType)
    {
        if($permissionType == 'PLUGIN_DOCMAN_MANAGE')
            {
                return '1,1,1';
            }
        else if ($permissionType == 'PLUGIN_DOCMAN_READ')
            {
                return '1,0,0';
            }
        else if($permissionType == 'PLUGIN_DOCMAN_WRITE')
            {
                return '1,1,0';
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
    function listUgroups($group_id){
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
    /**
     * Short desc:
     * 
     * extractPermissions  extracts permissions of a  user group on an item
     * 
     * @param int $group_id project id
     * @param int $ugroup_id user group id
     * @param int $item_id item id
     *
     * @return null
     */
    function extractPermissions($group_id, $ugroup_id, $item_id){
        $requete_perms = "SELECT  Ugrp.name, P.permission_type, PDI.title".
            "   FROM ugroup Ugrp ".
            "   INNER JOIN permissions P ON(P.ugroup_id=Ugrp.ugroup_id and P.permission_type LIKE 'PLUGIN_DOCMAN%')".
            "   INNER JOIN plugin_docman_item PDI ON(PDI.item_id=P.object_id AND PDI.group_id=Ugrp.group_id)".
            " WHERE P.ugroup_id='".$ugroup_id."'AND Ugrp.group_id='".$group_id."' AND PDI.item_id='".$item_id."'";
        $resultat_perms = db_query($requete_perms);  
        if($resultat_perms && !db_error($resultat_perms))
            {
                return $resultat_perms;
            } else {
            echo "DB error: ".db_error()."<br>";
        }
    }
 
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
    function listUserFormatting($ugroups, $group_id)
    {
        echo "User group,User name\n";
        foreach($ugroups as $ugrp)
            {
                $resultat_liste_user = $this->userList($ugrp['ugroup_id'],$group_id);
                while ($row_liste_user = db_fetch_array($resultat_liste_user))
                    {
                        echo $ugrp['name']."".$this->sep."".$row_liste_user['user_name']."".PHP_EOL;
                    }
            }
    }	
   
}

$GLOBALS['Language']->loadLanguageMsg('docman', 'docman');

/**
 * Short desc:
 * 
 * identifyProject  identifies project id
 * 
 * @return int $group_id project id
 */

//function identifyProject()
//{
//   $requete_Id_project = "SELECT G.group_id".
//       " FROM groups G".
//        " WHERE G.group_name='HTIProject'";//just for test
//
//   $resultat_Id_project = db_query($requete_Id_project);
//   $row_Id_project = db_fetch_array($resultat_Id_project);
//   return $row_Id_project['group_id'];

//}	  


//$group_id = identifyProject();
$group_id=$request->get('group_id');
$docmanItem = array();


$um = UserManager::instance();
$user = $um->getCurrentUser();
$Params['user'] = $user;
$Params['ignore_collapse'] = true;
$Params['ignore_perms'] = true;
$Params['ignore_obsolete'] = false;
$itemFactory = new Docman_ItemFactory($group_id);
$node = $itemFactory->getItemTree(0, $Params);
$visitor = new ShowPermsVisitor();
$visitor->visitFolder($node, $docmanItem);
$listItem = array();
$ugroups = array();
$visitor->csvFormatting($ugroups, $listItem, $group_id);
$visitor->listUserFormatting($ugroups, $group_id);
?> 