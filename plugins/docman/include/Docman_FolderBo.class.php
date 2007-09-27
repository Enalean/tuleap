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
 * $Id$
 */
require_once('Docman_FolderDao.class.php');
require_once('Docman_ItemBo.class.php');
require_once('Docman_Folder.class.php');

/**
 * Folder Buisness object.
 *
 * This class aims to offer an high level access to 'Model' layer of the
 * application. 
 */
class Docman_FolderBo extends Docman_ItemBo {
    
    function Docman_FolderBo($groupId=0) {
        $this->Docman_ItemBo($groupId);
    }

    /**
     * Create a new folder
     *
     * @param Folder
     */
    function create($folder) {
        $folderDao =& new Docman_FolderDao(CodexDataAccess::instance());

        $folderDao->create($folder->getParentId(), 
                           $folder->getGroupId(), 
                           $folder->getTitle(), 
                           $folder->getDescription(),
                           $folder->getCreateDate(),
                           $folder->getUpdateDate(),
                           $folder->getOwnerId(), 
                           $folder->getRank());        
    }

    /**
     * Update an existing folder
     *
     * @param Folder
     */
    function update($folder) {
        $folderDao =& new Docman_FolderDao(CodexDataAccess::instance());

        $folderDao->updateById($folder->getId(),
                               $folder->getParentId(), 
                               $folder->getGroupId(), 
                               $folder->getTitle(), 
                               $folder->getDescription(),
                               $folder->getCreateDate(),
                               $folder->getUpdateDate(),
                               $folder->getOwnerId(), 
                               $folder->getRank());
    }

    /**
     * Delete an existing folder
     *
     * @param Folder
     */
    function delete($folder) {
        $folderDao =& new Docman_FolderDao(CodexDataAccess::instance());

        $folderDao->delete($folder->getId());
    }

    /**
     * Return an Iterator on the list of folders in the project
     *
     * @return ArrayIterator
     */
    function &getList() {
        $folderDao =& new Docman_FolderDao(CodexDataAccess::instance());

        $flist = array();

        $dar = $folderDao->searchAllByType($this->groupId);        
        while($dar->valid()) {
            $row =& $dar->current();

            $folder = new Docman_Folder();
            $folder->initFromRow($row);
            $flist[$folder->getId()] =& $folder;
            unset($folder);

            $dar->next();
        }

        $i = new ArrayIterator($flist);
        return $i;
    }

    /**
    * @return Folder
    */
    function &findById($id) {
        $item =& parent::findById($id);
        if ($item && is_a($item, 'Docman_Folder')) {
            return $folder;
        }
        return false;
    }
    /**
     * Set a collapse preference for given folder for current (logged)
     * user. Stricly speaking, we should pass user in argument but there is no
     * existing function that handle prefences in this way.
     *
     * @param Folder
     */
    function collapse($folder) {
        user_del_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF.'_'.$folder->getGroupId().'_'.$folder->getId());
    }

    /**
     * Set a expand preference for given folder for current user.
     *
     * @param Folder
     */
    function expand($folder) {
        user_set_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF.'_'.$folder->getGroupId().'_'.$folder->getId(),
                            PLUGIN_DOCMAN_EXPAND_FOLDER);
    }
    
}

?>