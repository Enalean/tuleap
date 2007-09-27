<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_GetShowViewVisitor
*/


class Docman_View_GetShowViewVisitor /* implements Visitor*/ {
    
    function visitFolder(&$item, $params = array()) {
        return Docman_View_Browse::getViewForCurrentUser($item->getGroupId(), $params);
    }
    function visitWiki(&$item, $params = array()) {
        return 'Redirect';
    }
    function visitLink(&$item, $params = array()) {
        return 'Redirect';
    }
    function visitFile(&$item, $params = array()) {
        return 'Download';
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return 'Embedded';
    }
    
    function visitEmpty(&$item, $params = array()) {
        return 'Empty';
    }
}
?>
