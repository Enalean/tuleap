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
 * $Id$
 */

require_once('Docman_PermissionsManagerDao.class.php');

class Docman_PermissionsManager {
    var $groupId;
    var $cache_access;
    var $cache_read;
    var $cache_write;
    var $cache_manage;
    var $cache_admin;
    var $dao;

    function Docman_PermissionsManager($groupId) {
        $this->groupId = $groupId;
        $this->cache_access = array();
        $this->cache_read = array();
        $this->cache_write = array();
        $this->cache_manage = array();
        $this->cache_admin = array();
        $this->dao = null;
    }

    /**
     * The manager is a singleton
     */
    function &instance($groupId) {
        static $_docman_permissionmanager_instance;
        if (!isset($_docman_permissionmanager_instance[$groupId])) {
            $_docman_permissionmanager_instance[$groupId] = new Docman_PermissionsManager($groupId);
        }
        return $_docman_permissionmanager_instance[$groupId];
    }

    function &_getPermissionManagerInstance() {
        $pm =& PermissionsManager::instance();
        return $pm;
    }

    function &getDao() {
        if($this->dao === null) {
            $this->dao = new Docman_PermissionsManagerDao(CodexDataAccess::instance(), $this->groupId);
        }
        return $this->dao;
    }
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
    function userCanAccess(&$user, $item_id) {
        if (!isset($this->cache_access[$user->getId()][$item_id])) {
            $can_read = $this->userCanRead($user, $item_id);
            if ($can_read) {
                $item_factory =& $this->_getItemFactory();
                $item =& $item_factory->getItemFromDb($item_id);
                $can_access_parent = $item->getParentId() == 0 || $this->userCanAccess($user, $item->getParentId());
                $this->cache_access[$user->getId()][$item_id] = $can_access_parent;
            } else {
                $this->cache_access[$user->getId()][$item_id] = false;
            }
        }
        return $this->cache_access[$user->getId()][$item_id];
    }
    function userCanRead(&$user, $item_id) {
        if(!isset($this->cache_read[$user->getId()][$item_id])) {
            $pm =& $this->_getPermissionManagerInstance();
            $this->cache_read[$user->getId()][$item_id] = $user->isSuperUser() 
                || $this->userCanAdmin($user, $item_id) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_READ', $user->getUgroups($this->groupId, array())) 
                || $this->userCanWrite($user, $item_id);
                
        }
        return $this->cache_read[$user->getId()][$item_id];
    }

    function userCanWrite(&$user, $item_id) {
        if(!isset($this->cache_write[$user->getId()][$item_id])) {
            $pm =& $this->_getPermissionManagerInstance();
            $this->cache_write[$user->getId()][$item_id] = $user->isSuperUser() 
                || $this->userCanAdmin($user) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_WRITE', $user->getUgroups($this->groupId, array())) 
                || $this->userCanManage($user, $item_id);

            if($this->cache_write[$user->getId()][$item_id] == true) {
                $this->cache_read[$user->getId()][$item_id] = true;
            }
        }
        return $this->cache_write[$user->getId()][$item_id];
    }

    function userCanManage(&$user, $item_id) {
        if(!isset($this->cache_manage[$user->getId()][$item_id])) {
            $pm =& $this->_getPermissionManagerInstance();
            $this->cache_manage[$user->getId()][$item_id] = $user->isSuperUser() 
                || $this->userCanAdmin($user) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_MANAGE', $user->getUgroups($this->groupId, array())) ;
            if($this->cache_manage[$user->getId()][$item_id] == true) {
                $this->cache_write[$user->getId()][$item_id] = true;
                $this->cache_read[$user->getId()][$item_id] = true;
            }
        }
        return $this->cache_manage[$user->getId()][$item_id];
    }

    function _isUserDocmanAdmin($user) {
        $has_permission = false;

        $permission_type = 'PLUGIN_DOCMAN_ADMIN';
        $object_id       = $this->groupId;

        // permissions set for this object.
        $res = permission_db_authorized_ugroups($permission_type, (int)$object_id);
        if (db_numrows($res) < 1 && $permission_type == 'PLUGIN_DOCMAN_ADMIN') {
            // No ugroup defined => no permissions set => get default permissions only for admin permission
            $res=permission_db_get_defaults($permission_type);
        } 
        while (!$has_permission && ($row = db_fetch_array($res))) {
            // should work even for anonymous users
            $has_permission = ugroup_user_is_member($user->getId(), $row['ugroup_id'], $this->groupId);
        }

        return $has_permission;
    }

    function userCanAdmin(&$user) {
        if(!isset($this->cache_admin[$user->getId()][$this->groupId])) {
            //Todo: see if this code already exists in permission_xxx
                        
            // Super-user has all rights...
            $has_permission = $user->isSuperUser();
            if (!$has_permission) {
                $has_permission = $this->_isUserDocmanAdmin($user);
            }
            $this->cache_admin[$user->getId()][$this->groupId] = $has_permission;
        }
        return $this->cache_admin[$user->getId()][$this->groupId];
    }

    function cloneItemPermissions($srcItemId, $dstItemId, $toGroupId) {
        $perms = array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE');
        $pm =& $this->_getPermissionManagerInstance();
        $pm->clonePermissions($srcItemId, $dstItemId, $perms, $toGroupId);
    }

    function cloneDocmanPermissions($srcGroupId, $dstGroupId) {
        $perms = array('PLUGIN_DOCMAN_ADMIN');
        $pm =& $this->_getPermissionManagerInstance();
        $pm->clonePermissions($srcGroupId, $dstGroupId, $perms, $dstGroupId);
    }

    function setDefaultItemPermissions($itemId, $force=false) {
        $dao =& $this->getDao();

        $dao->setDefaultPermissions($itemId, 'PLUGIN_DOCMAN_READ', $force);
        $dao->setDefaultPermissions($itemId, 'PLUGIN_DOCMAN_WRITE', $force);
        $dao->setDefaultPermissions($itemId, 'PLUGIN_DOCMAN_MANAGE', $force);
    }

    function setDefaultDocmanPermissions($groupId) {
        $dao =& $this->getDao();

        $dao->setDefaultPermissions($groupId, 'PLUGIN_DOCMAN_ADMIN');
    }

    function retreiveReadPermissionsForItems($itemsIds, $user){
        $dao =& $this->getDao();

        $userId = $user->getId();

        // do not compute a perm twice
        if(!isset($this->cache_read[$userId]) || count($this->cache_read[$userId]) > 0) {
            $objIds = array();
            foreach($itemsIds as $itemid) {
                if(!isset($this->cache_read[$userId][$itemid])) {
                    $objIds[] = $itemid;
                }
            }
        }
        else {
            $objIds = $itemsIds;
        }

        if(count($objIds) > 0) {
            $perms = array("'PLUGIN_DOCMAN_READ'", "'PLUGIN_DOCMAN_WRITE'", "'PLUGIN_DOCMAN_MANAGE'");
            $dar = $dao->retreivePermissionsForItems($objIds, $perms, $user->getUgroups($this->groupId, array()));
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                
                $oid = $row['object_id'];
                switch($row['permission_type']) {
                case 'PLUGIN_DOCMAN_MANAGE':
                    $this->cache_manage[$userId][$oid] = true;
                case 'PLUGIN_DOCMAN_WRITE':
                    $this->cache_write[$userId][$oid] = true;
                case 'PLUGIN_DOCMAN_READ':
                    $this->cache_read[$userId][$oid] = true;
                }
                
                $dar->next();
            }
        }
    }

    function oneFolderIsWritable($user) {
        $oneWritable = false;

        $dao =& $this->getDao();

        if($this->userCanAdmin($user)) {
            $oneWritable = true;
        }
        else {
            $oneWritable = $dao->oneFolderIsWritable($this->groupId, $user->getUgroups($this->groupId, array()));
        }

        return $oneWritable;
    }

}

?>
