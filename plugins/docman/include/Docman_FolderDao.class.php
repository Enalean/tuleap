<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
require_once('Docman_Item.class.php');
require_once('Docman_ItemDao.class.php');

class Docman_FolderDao extends Docman_ItemDao {

    function Docman_FolderDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    
    /**
     * Return all 'Folder' entries in plugin_docman_item for a given group_id
     * @return DataAccessResult
     */
    function searchAllByType($group_id) {
        return parent::searchAllByType($group_id, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
    }

    /**
     * Create a row in the table plugin_docman_item 
     * @return true or id(auto_increment) if there is no error
     */
    function create($parent_id, $group_id, $title, $description, $create_date, 
                    $update_date, $user_id, $rank) {
        
        return parent::create($parent_id, $group_id, $title, $description, 
                              $create_date, $update_date, $user_id, $rank, 
                              PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, null, null, null);		
    }

    /**
     * Update a row in the table plugin_docman_item
     * @return true or id(auto_increment) if there is no error
     */
    function updateById($item_id, $parent_id=null, $group_id=null, $title=null,
                    $description=null, $create_date=null, $update_date=null, 
                    $user_id=null, $rank=null) {       
       
        return parent::updateById($item_id, $parent_id, $group_id, $title,
                                  $description, $create_date, $update_date, 
                                  $user_id, $rank, null, null, null, null);
    }
}

?>