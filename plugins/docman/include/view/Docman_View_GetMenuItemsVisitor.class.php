<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_GetMenuItemsVisitor
*/


class Docman_View_GetMenuItemsVisitor /* implements Visitor*/ {
    var $actions;
    function Docman_View_GetMenuItemsVisitor(&$user, $groupId) {
        $this->dPm =& Docman_PermissionsManager::instance($groupId);
        $this->user =& $user;
        $this->if =& Docman_ItemFactory::instance($groupId);
        $this->actions = array();
    }
    
    function visitItem(&$item, $params = array()) {
        if($this->dPm->userCanManage($this->user, $item->getId())) {
            $this->actions['canPermissions'] = true;
        }
        // Permissions related stuff:
        // There are 2 permissions to take in account to decide whether
        // someone can move a file or not:
        // - the permission to 'remove' the file from a folder.
        //   - user need to have 'write' perm on both item and parent
        //     folder.
        // - and the permission to 'add' the file in another folder.
        //   - check if there is at least one folder writable in the
        //     docman.
        // But as the first step requires to have one folder writable,
        // we don't need specific test for the second one.
        // The only case we don't take in account is the possibility to
        // have only one file in only one writable folder (so it
        // shouldn't be movable). But this case is not worth the time
        // to develop and compute that case.
        if($this->if->isMoveable($item) && $this->dPm->userCanWrite($this->user, $item->getId()) && $this->dPm->userCanWrite($this->user, $item->getParentId())) {
            $this->actions['canMove'] = true;
        }
        if(!$this->if->isRoot($item) && $this->dPm->userCanWrite($this->user, $item->getId()) && $this->dPm->userCanWrite($this->user, $item->getParentId())) {
            $this->actions['canDelete'] = true;
        }
        $this->actions['canApproval'] = true;
        return $this->actions;
    }
    
    function visitFolder(&$item, $params = array()) {
        if($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canNewDocument'] = true;
            $this->actions['canNewFolder']   = true;
            if($this->if->getCopyPreference($this->user) != false) {
                $this->actions['canPaste'] = true;
            }
        }
        return $this->visitItem($item, $params);
    }
    
    function visitDocument($item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    
    function visitWiki(&$item, $params = array()) {
        if($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canUpdate'] = true;
        }
        return $this->visitDocument($item, $params);
    }
    
    function visitLink(&$item, $params = array()) {
        if($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canUpdate'] = true;
        }
        return $this->visitDocument($item, $params);
    }
    
    function visitFile(&$item, $params = array()) {
        if($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canNewVersion'] = true;
        }
        return $this->visitDocument($item, $params);
    }
    
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        if($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canUpdate'] = true;
        }
        $actions = $this->visitDocument($item, $params);
        unset($actions['canApproval']); // No approval table for empty docs
        return $actions;
    }
}
?>