<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_GetMenuItemsVisitor
*/


class Docman_View_GetMenuItemsVisitor /* implements Visitor*/ {
    var $items;
    function Docman_View_GetMenuItemsVisitor() {
        $this->items = array();
    }
    function visitItem(&$item, $params = array()) {
        $this->items[30] =& new Docman_ItemActionDetails($item);
        $this->items[40] =& new Docman_ItemActionNotifications($item);
        $this->items[50] =& new Docman_ItemActionHistory($item);
        $this->items[70] =& new Docman_ItemActionPermissions($item);
        $this->items[80] =& new Docman_ItemActionMove($item);
        $this->items[90] =& new Docman_ItemActionDelete($item);
        ksort($this->items);
        return $this->items;
    }
    function visitFolder(&$item, $params = array()) {
        $this->items[10] =& new Docman_ItemActionNewDocument($item);
        $this->items[20] =& new Docman_ItemActionNewFolder($item);
        return $this->visitItem($item, $params);
    }
    function visitDocument($item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    function visitWiki(&$item, $params = array()) {
        $this->_addUpdate($item, $params);
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        $this->_addUpdate($item, $params);
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        $this->items[60] =& new Docman_ItemActionNewVersion($item);
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }
    
    function _addUpdate(&$item, $params) {
        $this->items[60] =& new Docman_ItemActionUpdate($item);
    }
}
?>