<?php
/**
 * Copyright (c) STMicroelectronics, 2006,2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006,2007
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
 */

require_once('Docman_Folder.class.php');
require_once('Docman_ItemFactory.class.php');

class Docman_FolderFactory
extends Docman_ItemFactory {
    
    function Docman_FolderFactory($groupId=null) {
        parent::Docman_ItemFactory($groupId);
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
