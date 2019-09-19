<?php
/**
 * Copyright (c) Enalean, 2017-2018. All rights reserved
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Folder is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Folder extends Docman_Item
{

    function __construct($data = null)
    {
        parent::__construct($data);
        $this->_resetItems();
    }

    public function getType()
    {
        return $GLOBALS['Language']->getText('plugin_docman', 'doc_type_folder');
    }

    function toRow()
    {
        $row = parent::toRow();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
        return $row;
    }

    function isRoot()
    {
        return $this->parent_id == 0;
    }

    var $_items;
    function addItem(&$item)
    {
        $this->_items->add($item, -($item->getRank()));
    }
    function &getAllItems()
    {
        return $this->_items;
    }
    function removeAllItems()
    {
        $this->_resetItems();
    }
    function _resetItems()
    {
        if (isset($this->_items)) {
            unset($this->_items);
        }
        $this->_items = new PrioritizedList();
    }
    public function accept($visitor, $params = array())
    {
        return $visitor->visitFolder($this, $params);
    }
}
