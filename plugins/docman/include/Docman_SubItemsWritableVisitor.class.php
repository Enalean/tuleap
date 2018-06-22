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

/**
 * Check if all the sub items are writable by given user.
 */
class Docman_SubItemsWritableVisitor /* implements Visitor */ {
    var $dpm;
    var $user;
    var $docIdList;
    var $fldIdList;
    var $docCounter;
    var $fldCounter;

    function __construct($groupId, $user) {
        $this->dpm =& Docman_PermissionsManager::instance($groupId);
        $this->user = $user;
        $this->docIdList = array();
        $this->fldIdList = array();
        $this->docCounter = 0;
        $this->fldCounter = 0;
    }

    function visitFolder(&$item, $params = array()) {
        // Recurse
        $canWrite = true;
        $this->fldCounter++;

        if($this->_itemIsWritable($item, $params)) {
            $this->fldIdList[] = $item->getId();
            $items =& $item->getAllItems();
            if($items && $items->size() > 0) {
                $iter =& $items->iterator();
                $iter->rewind();
                while($iter->valid()) {
                    $child =& $iter->current();
                    $canWrite = ($canWrite && $child->accept($this, $params));
                    $iter->next();
                }
            }
        } else {
            $canWrite = false;
        }
        return $canWrite;
    }

    function visitDocument(&$item, $params = array()) {
        $this->docCounter++;
        if($this->_itemIsWritable($item, $params)) {
            $this->docIdList[] = $item->getId();
            return true;
        }
        return false;
    }

    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitFile(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }


    function _itemIsWritable($item, $params) {
        return $this->dpm->userCanWrite($this->user, $item->getId());
    }

    function getItemIdList() {
        return array_merge($this->fldIdList, $this->docIdList);
    }

    function getFolderIdList() {
        return $this->fldIdList;
    }

    function getDocumentIdList() {
        return $this->docIdList;
    }

    function getDocumentCounter() {
        return $this->docCounter;
    }
    function getFolderCounter() {
        return $this->fldCounter;
    }
}
?>
