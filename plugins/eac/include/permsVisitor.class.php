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

class permsVisitor {
 
    var $group_id;
    var $allTreeItems = array();
    var $sep;
    var $node;
    
    public function permsVisitor($group_id) {
        require_once(dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
        require_once('common/user/UserManager.class.php');
        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        $itemFactory    = new Docman_ItemFactory($group_id);
        $rootItem       = $itemFactory->getRoot($group_id);
        $node           = $itemFactory->getItemTree($rootItem, $user, true, true, false);
        $this->sep      = get_csv_separator();
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
     *  Method getItemPath which append the full name  of the Doc/Folder and memorize its Id and its full name in listItem
     * @param  Array tableId contains item's id and short name
     * @param  Array visited_id contains item's which are already visited to donot visit them again
     * @return Array listItem will memorize item id and its full name
     */ 
    
    private function getItemPath($tableId, &$visited_ids) { 
        $item_full_name = '';
        $item_id        = '';
        $listItem = array();
        foreach($tableId as $id => $idDoc) {
            if (!isset($visited_ids[$id])) {
                $item_full_name .= $idDoc."/";
                $item_id         = $id;
                $listItem[$item_id] = $item_full_name;
                $visited_ids[$id] = true;
            }
        }
        return $listItem;
    }
    
    /**
     * Method render which print permissions of all documents in the csv format
     * @return csv text
     */
    
    public function render() {
        header('Content-Disposition: filename=export_permissions.csv');
        header('Content-Type: text/csv');
        echo "Document/Folder".$this->sep."User group".$this->sep."Read".$this->sep."Write".$this->sep."Manage".PHP_EOL;

        $table_perms = array();
        $docmanItem  = array();
        $listItem    = array();
        $visited_ids = array();
        $this->visitFolder($this->node, $docmanItem);
        require_once('permsDao.class.php');
        $permsD = new permsDao(CodendiDataAccess::instance());
        
        foreach ($this->allTreeItems as $folder_id ) {
            $listItem[] = $this->getItemPath($folder_id, $visited_ids);  
        }

        foreach($listItem as $item_ids => $items) {
            foreach ($items as $item_id => $item) {
                $table_perms     = $permsD->getPermissions($item_id,$this->group_id);
                foreach ($table_perms as $row_permissions) {
                    $permission = $this->getCSVConvertedPermissionType($row_permissions['permission_type']);
                    echo $item.$this->sep.$row_permissions['name'].$this->sep.$permission.PHP_EOL;
                }
            }
        }
    }
    
    public function renderDefinitionFormat() {
        project_admin_header(array('title'=>$GLOBALS['Language']->getText('plugin_eac','export_format')));
        
        echo '<h3>'.$GLOBALS['Language']->getText('plugin_eac','perm_exp_format').'</h3>';
        echo '<p>'.$GLOBALS['Language']->getText('plugin_eac','perm_exp_format_msg').'</p>';
        $title_arr = array(
            $GLOBALS['Language']->getText('plugin_eac','format_label'),
            $GLOBALS['Language']->getText('plugin_eac','format_sample'),
            $GLOBALS['Language']->getText('plugin_eac','format_description')
        );
        echo  html_build_list_table_top ($title_arr);
        echo "<tr class='". util_get_alt_row_color(0) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_eac','format_path')."</b></td>";
        echo "<td>Project Documentation/My Document</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_eac','format_path_desc')."</td>";
        echo "</tr>";
        echo "<tr class='". util_get_alt_row_color(1) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_eac','format_user_group')."</b></td>";
        echo "<td>Developper Group</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_eac','format_user_group_desc')."</td>";
        echo "</tr>";
        echo "<tr class='". util_get_alt_row_color(2) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_eac','format_read')."</b></td>";
        echo "<td>yes</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_eac','format_read_desc')."</td>";
        echo "</tr>";
        echo "<tr class='". util_get_alt_row_color(3) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_eac','format_write')."</b></td>";
        echo "<td>no</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_eac','format_write_desc')."</td>";
        echo "</tr>";
        echo "<tr class='". util_get_alt_row_color(4) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_eac','format_manage')."</b></td>";
        echo "<td>no</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_eac','format_manage_desc')."</td>";
        echo "</tr>";        
        echo "</table>";
        site_project_footer(array());
    }
    
    /**
     * Method getCSVConvertedPermissionType which print information about permission in csv format
     * @param Tree item is the Embedded File
     * @return String yes SEPERATOR NO SEPERATOR yes (yes,no,yes)
     */
    
    private function getCSVConvertedPermissionType($permissionType) {
        if($permissionType == 'PLUGIN_DOCMAN_MANAGE') {
            return 'yes'.$this->sep.'yes'.$this->sep.'yes';
        } else if ($permissionType == 'PLUGIN_DOCMAN_READ') {
            return 'yes'.$this->sep.'no'.$this->sep.'no';
        } else if($permissionType == 'PLUGIN_DOCMAN_WRITE') {
            return 'yes'.$this->sep.'yes'.$this->sep.'no';
        } else {
            return 'no'.$this->sep.'no'.$this->sep.'no';
        }
    }
}
?>