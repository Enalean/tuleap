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
require_once('Docman_FileStorage.class.php');
require_once('Docman_VersionFactory.class.php');
class DocmanActionsDeleteVisitor /* implements Visitor */ {
    
    function DocmanActionsDeleteVisitor(&$file_storage, &$docman) {
        $this->file_storage =& $file_storage;
        $this->docman       =& $docman;
    }
    
    function visitFolder(&$item, $params = array()) {
        //delete all sub items before
        $items = $item->getAllItems();
        $parent =& $params['parent'];
        $one_item_has_not_been_deleted = false;
        if ($items->size()) {
            $it =& $items->iterator();
            while($it->valid()) {
                $o =& $it->current();
                $params['parent'] =& $item;
                if (!$o->accept($this, $params)) {
                    $one_item_has_not_been_deleted = true;
                }
                $it->next();
            }
        }
        
        if ($one_item_has_not_been_deleted) {
            $this->docman->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_delete_notempty', $item->getTitle()));
            return false;
        } else {
            //Mark the folder as deleted;
            $params['parent'] =& $parent;
            return $this->_deleteItem($item, $params);
        }
    }
    function visitDocument(&$item, $params = array()) {
        //Mark the document as deleted
        return $this->_deleteItem($item, $params);
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        if ($this->docman->userCanWrite($item->getId())) {
            //Delete all versions before
            $version_factory =& $this->_getVersionFactory();
            if ($versions = $version_factory->getAllVersionForItem($item)) {
                if (count($versions)) {
                    foreach ($versions as $key => $nop) {
                        $this->file_storage->delete($versions[$key]->getPath());
                    }
                }
            }
            return $this->visitDocument($item, $params);
        } else {
            $this->docman->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete_item', $item->getTitle()));
            return false;
        }
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function _deleteItem($item, $params) {
        if ($this->docman->userCanWrite($item->getId())) {
            $item->setDeleteDate(time());
            $dao = $this->_getItemDao();
            $dao->updateFromRow($item->toRow());
            $em =& $this->_getEventManager();
            $em->processEvent(PLUGIN_DOCMAN_EVENT_DEL, array(
                'group_id' => $item->getGroupId(),
                'item'     => &$item,
                'parent'   => &$params['parent'],
                'user'     => &$params['user'])
            );
            return true;
        } else {
            $this->docman->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete_item', $item->getTitle()));
            return false;
        }
    }
    function &_getEventManager() {
        return EventManager::instance();
    }
    function &_getVersionFactory() {
        $f = new Docman_VersionFactory();
        return $f;
    }
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
    function &_getFileStorage() {
        $fs = new Docman_FileStorage();
        return $fs;
    }
    function &_getItemDao() {
        $dao = new Docman_ItemDao(CodexDataAccess::instance());
        return $dao;
    }
}
?>
