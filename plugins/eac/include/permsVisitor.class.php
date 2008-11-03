<?php
class permsVisitor {
 
    var $group_id;
    var $allTreeItems = array();
    var $sep          = ',';
    var $node;
    
    public function permsVisitor($group_id) {
        require_once(dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
        require_once('common/user/UserManager.class.php');
        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        
        $params['user']            = $user;
        $params['ignore_collapse'] = true;
        $params['ignore_perms']    = true;
        $params['ignore_obsolete'] = false;
        
        $itemFactory    = new Docman_ItemFactory($group_id);
        $node           = $itemFactory->getItemTree(0, $params);
        $this->node     = $node;
        $this->group_id = $group_id;
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
     * @return Array listItem will memorize item id and its full name
     */ 
    
    private function itemFullName($tableId) { 
        $item_full_name = '';
        $item_id        = '';
        foreach($tableId as $id => $idDoc) {     
            $item_full_name .= $idDoc."/";
            $item_id         = $id;
        }
        $listItem[$item_id] = $item_full_name;
        return $listItem;
    }
    
    /**
     * Method cvsFormatting which extract information from arraies and print them in the csv format
     * @param Array ugroups will memorize all ugroup's name and id
     * @param Array listItem contains items ids and names
     * @param int   group_id project id
     * @return null
     */
    
    public function csvFormatting() {
       
        echo "Document/Folder,User group,Read,Write,Manage\n";
        
        $table_perms = array();
        $docmanItem  = array();
        $listItem    = array();
        
        $ugroups     = $this->listUgroups($ugroups);
        $this->visitFolder($this->node, $docmanItem);
        foreach ($this->allTreeItems as $folder_id ) {
            $listItem = $this->itemFullName($folder_id);  
        }

        foreach($listItem as $item_id => $item) { 
             $permission_type = 'PLUGIN_DOCMAN%';
             $table_perms     = $this->extractPermissions($item_id, $permission_type);
             foreach ($table_perms as $row_permissions) {
                 $permission = $this->permissionFormatting($row_permissions['permission_type']);
                 echo $item.$this->sep.$row_permissions['name'].$this->sep.$permission.PHP_EOL;
             }
        }  
    }
    
    /**
     * Method permissionFormatting which print information about permission in csv format
     * @param Tree item is the Embedded File
     * @return String 
     */
    
    private function permissionFormatting($permissionType) {
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
    
    private function listUgroups($ugroups) {
        $sql = sprintf('SELECT Ugrp.ugroup_id, Ugrp.name'.
                       ' FROM  ugroup Ugrp '.
                       ' WHERE Ugrp.group_id = %d',
                       db_ei($this->group_id));

        $result_list_ugroups = db_query($sql);
        if($result_list_ugroups && !db_error($result_list_ugroups)) {
            while($row_list_ugroups = db_fetch_array($result_list_ugroups)) {
                $ugroup_id = $row_list_ugroups['ugroup_id'];
                $ugroups[] = $row_list_ugroups;
            }
        }
        return $ugroups;
    }
    
    /**
     * Method extractPermissions which extract permissions of a  user group on an item
     * @param int group_id project id
     * @param int ugroup_id user group id
     * @param int item_id item id
     * @return array row_perms permission information
     */
    
    private function extractPermissions($item_id, $permission_type){
        $table_perms = array();
        
        $sql = sprintf('SELECT  Ugrp.ugroup_id, Ugrp.name, P.permission_type, PDI.title'.
                       ' FROM ugroup Ugrp '.
                       ' INNER JOIN permissions P ON(P.ugroup_id = Ugrp.ugroup_id AND P.permission_type LIKE \'%s\')'.
                       ' INNER JOIN plugin_docman_item PDI ON(PDI.item_id = P.object_id AND PDI.group_id = Ugrp.group_id)'.
                       ' WHERE Ugrp.group_id = %d AND PDI.item_id = %d',
                       db_es($permission_type), db_ei($this->group_id), db_ei($item_id));
        $result_perms = db_query($sql);
        
        if($result_perms && !db_error($result_perms)) {
            while ($row_perms = db_fetch_array($result_perms)) {
                $table_perms[] = $row_perms;
            }
        }
        return $table_perms;
     }
}
?>  
