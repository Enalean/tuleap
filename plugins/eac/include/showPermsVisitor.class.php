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

class showPermsVisitor {
 
    var  $allTreeItems = array();
    var  $sep          = ',';
   
    public function showPermsVisitor() {
    }
   
    
    /**
     * Method visitFolder which memorize Folder id and title and walk through its tree 
     * @param Tree  $item is the Folder tree
     * @param Array $docmanItem containing information about Docman items
     * @return null
     */
   
    public function visitFolder($item ,$docmanItem) {
        $id              = $item->getId();
        $title           = $item->getTitle();  
        $docmanItem[$id] = $title;
        $this->allTreeItems[] = $docmanItem;
        // Walk through the tree
        $items = $item->getAllItems();
        $it    = $items->iterator();
        while($it->valid()) {
            $i = $it->current();
            $i->accept($this, $docmanItem);
            $it->next();
        }
    }

    /**
     * Method visitWiki which memorize Wikipage id and title
     * @param Tree  $item is the Wikipage
     * @param Array $docmanItem is the array containing information about each Docman items
     * @return null
     */
   
    public function visitWiki($item ,$docmanItem) {
        $id                   = $item->getId();
        $title                = $item->getTitle(); 
        $docmanItem[$id]      = $title;
        $this->allTreeItems[] = $docmanItem;
    }

    /**
     * Method visitEmpty which memorize Empty file id and title
     * @param Tree $item is the Empty file
     * @param Array $docmanItem is the array containing information about each Docman items
     * @return null
     */

    public function visitEmpty($item ,$docmanItem) {
        $id                   = $item->getId();
        $title                = $item->getTitle(); 
        $docmanItem[$id]      = $title;
        $this->allTreeItems[] = $docmanItem;
    }
    
    /**
     * Method visitEmbeddedFile which memorize Embedded File id and title
     * @param Tree $item is the Embedded File
     * @param Array $docmanItem is the array containing information about each Docman items
     * @return null
     */
    
    public function visitEmbeddedFile($item ,$docmanItem) {
        $id                   = $item->getId();
        $title                = $item->getTitle(); 
        $docmanItem[$id]      = $title;
        $this->allTreeItems[] = $docmanItem;
    }
    
    /**
     * Method visitLink which  memorize Link id and title
     * @param Tree $item is the Link
     * @param Array $docmanItem is the array containing information about each Docman items
     * @return null
     */
    
    public function visitLink($item ,$docmanItem) {
        $id                   = $item->getId();
        $title                = $item->getTitle(); 
        $docmanItem[$id]      = $title;
        $this->allTreeItems[] = $docmanItem;
    } 
    
    /**
     * Method visitFile which memorize File  id and title
     * @param Tree $item is the Link
     * @param Array $docmanItem is the array containing information about each Docman items
     * @return null
     */
    
    public function visitFile($item ,$docmanItem) {
        $id                   = $item->getId();
        $title                = $item->getTitle(); 
        $docmanItem[$id]      = $title;
        $this->allTreeItems[] = $docmanItem;
    }
    
    /**
     *  Method itemFullName which append the full name  of the Doc/Folder and memorize its Id and its full name in listItem
     * @param Array Id contains item's id and short name
     * @param Array listItem will memorize item id and its full name
     * @return null
     */ 
    
    public function itemFullName($tableId , $listItem) { 
        $item_full_name = '';
        $item_id        = '';
        foreach($tableId as $id => $idDoc) {     
            $item_full_name .= $idDoc."/";
            $item_id         = $id;
        }
        $listItem[$item_id] = $item_full_name;
    }
    
    /**
     * Method cvsFormatting which extract information from arraies and print them in the csv format
     * @param Array ugroups will memorize all ugroup's name and id
     * @param Array listItem contains items ids and names
     * @param int   group_id project id
     * @return null
     */
    
    public function csvFormatting($ugroups, $listItem ,$group_id) {
        header('Content-Disposition: filename=export_permissions.csv');
        header('Content-Type: text/csv');
        
        echo "Document/Folder,User group,Read,Write,Manage\n";
        
        $table_perms = array();
        $ugroups     = $this->listUgroups($group_id,$ugroups);
        foreach ($this->allTreeItems as $folder_id ) {
             $this->itemFullName($folder_id,$listItem);  
        }

        foreach($listItem as $item_id => $item) { 
             $permission_type = 'PLUGIN_DOCMAN%';
             $table_perms     = $this->extractPermissions($group_id, $item_id, $permission_type);
             foreach ($table_perms as $row_permissions ) {
                 $permission = $this->permissionFormatting($row_permissions['permission_type']);
                 echo $item."".$this->sep."".$row_permissions['name']."".$this->sep."".$permission."".PHP_EOL;
             }
        }  
    }
    
    /**
     * Method permissionFormatting which print information about permission in csv format
     * @param Tree item is the Embedded File
     * @return String 
     */
    
    public function permissionFormatting($permissionType) {
        if($permissionType == 'PLUGIN_DOCMAN_MANAGE') {
            return 'yes'.$this->sep.'yes'.$this->sep.'yes';
        } else if ($permissionType == 'PLUGIN_DOCMAN_READ') {
            return 'yes'.$this->sep.'no'.$this->sep.'no';
        } else if($permissionType == 'PLUGIN_DOCMAN_WRITE') {
            return 'yes'.$this->sep.'yes'.$this->sep.'no';
        }
    }

    /**
     * Method listUgroup which extract user groups list for a given project
     * @param int   group_id project id
     * @param Array ugroups will memorize all ugroup's name and id
     * @return null
     */
    
    public function listUgroups($group_id,&$ugroups) {
        $requete_liste_ugroups =  sprintf('SELECT Ugrp.ugroup_id, Ugrp.name'.
                                          ' FROM  ugroup Ugrp '.
                                          ' WHERE Ugrp.group_id = %d',db_ei($group_id));
        
        $resultat_liste_ugroups = db_query($requete_liste_ugroups);
        if($resultat_liste_ugroups && !db_error($resultat_liste_ugroups)) {
            while($row_liste_ugroups = db_fetch_array($resultat_liste_ugroups)) {
                $ugroup_id = $row_liste_ugroups['ugroup_id'];
                $ugroups[] = $row_liste_ugroups;
            }
            return $ugroups;
        } else {
            //echo 'DB error:'.$GLOBALS['Response']->addFeedback('plugin_eac','db_error');
        }
    }
    
    /**
     * Method extractPermissions which extract permissions of a  user group on an item
     * @param int group_id project id
     * @param int ugroup_id user group id
     * @param int item_id item id
     * @return array row_perms permission information
     */
    
    public function extractPermissions($group_id, $item_id, $permission_type){
        $table_perms =array();
        
        $requete_perms = sprintf('SELECT  Ugrp.ugroup_id, Ugrp.name, P.permission_type, PDI.title'.
                                 ' FROM ugroup Ugrp '.
                                 ' INNER JOIN permissions P ON(P.ugroup_id=Ugrp.ugroup_id and P.permission_type LIKE %s)'.
                                 ' INNER JOIN plugin_docman_item PDI ON(PDI.item_id=P.object_id AND PDI.group_id=Ugrp.group_id)'.
                                 ' WHERE Ugrp.group_id= %d AND PDI.item_id= %d',DataAccess::quoteSmart($permission_type),db_ei($group_id),db_ei($item_id));
        
        $resultat_perms = db_query($requete_perms);  
        
        if($resultat_perms && !db_error($resultat_perms)) {
	        while ($row_perms = db_fetch_array($resultat_perms)) {
	            $table_perms[] = $row_perms;
            }
        } else {
            //echo 'DB error:'.$GLOBALS['Response']->addFeedback('plugin_eac','db_error');
        }
        return $table_perms;
     }
}
?>	
