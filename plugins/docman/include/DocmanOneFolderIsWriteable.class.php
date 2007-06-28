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

class DocmanOneFolderIsWriteable /* implements Visitor */ {
    
    var $docman;
    function DocmanOneFolderIsWriteable(&$docman) {
        $this->docman = $docman;
    }
    
    function visitFolder(&$item, $params = array()) {
        $b = false;
        if ($this->docman->userCanWrite($item->getId())) {
            $b = true;
        } else {
            $items = $item->getAllItems();
            $it =& $items->iterator();
            while(!$b && $it->valid()) {
                $o =& $it->current();
                $b = $o->accept($this);
                $it->next();
            }
        }
        return $b;
    }
    
    function visitWiki(&$item, $params = array()) {
        return false;
    }
    function visitLink(&$item, $params = array()) {
        return false;
    }
    function visitFile(&$item, $params = array()) {
        return false;
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return false;
    }
}
?>