<?php
/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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

class Docman_Path {
    var $path;
    function Docman_Path() {
        $this->path = array();
    }
    function get(&$item) {
        if (!isset($this->path[$item->getId()])) {
            $this->path[$item->getId()] = '';
            if ($item->getParentId()) {
                $if =& $this->_getItemFactory();
                $parent =& $if->getItemFromDb($item->getParentId(), array('ignore_deleted' => true));
                if ($parent) {
                    $this->path[$item->getId()] = $this->get($parent) .'/';
                }
            }
            $this->path[$item->getId()] .= $item->getTitle();
        }
        return $this->path[$item->getId()];
    }
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
}

