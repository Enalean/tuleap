<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Docman_Folder.class.php');
require_once('Docman_ItemFactory.class.php');

class Docman_FolderFactory extends Docman_ItemFactory
{

    public function __construct($groupId = null)
    {
        parent::__construct($groupId);
    }

    /**
     * Set a collapse preference for given folder for current (logged)
     * user. Stricly speaking, we should pass user in argument but there is no
     * existing function that handle prefences in this way.
     *
     * @param Docman_Folder $folder
     */
    public function collapse($folder)
    {
        user_del_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF . '_' . $folder->getGroupId() . '_' . $folder->getId());
    }

    /**
     * Set a expand preference for given folder for current user.
     *
     * @param Docman_Folder $folder
     */
    public function expand($folder)
    {
        user_set_preference(
            PLUGIN_DOCMAN_EXPAND_FOLDER_PREF . '_' . $folder->getGroupId() . '_' . $folder->getId(),
            PLUGIN_DOCMAN_EXPAND_FOLDER
        );
    }
}
