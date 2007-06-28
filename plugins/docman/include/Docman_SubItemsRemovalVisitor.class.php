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
class Docman_SubItemsRemovalVisitor /* implements Visitor */ {
    
    function visitFolder(&$item, $params = array()) {
        $item->removeAllItems();
    }
    function visitDocument(&$item, $params = array()) {
        //Do nothing
    }
    function visitWiki(&$item, $params = array()) {
        $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }
    
    function visitEmpty(&$item, $params = array()) {
        $this->visitDocument($item, $params);
    }
}
?>
