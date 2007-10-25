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

require_once('Docman_PermissionsManagerDao.class.php');
require_once('Docman_SubItemsWritableVisitor.class.php');

class Docman_PermissionsManager {
    var $groupId;
    var $cache_access;
    var $cache_read;
    var $cache_write;
    var $cache_manage;
    var $cache_admin;
    var $dao;
    var $currentUser;
    var $item_factory;

    // No cache, just convenient accessor.
    var $subItemsWritableVisitor;

    function Docman_PermissionsManager($groupId) {
        $this->groupId = $groupId;
        $this->cache_access = array();
        $this->cache_read = array();
        $this->cache_write = array();
        $this->cache_manage = array();
        $this->cache_admin = array();
        $this->dao = null;
        $this->currentUser = null;
        $this->item_factory = null;

        $this->subItemsWritableVisitor = null;
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

    function &_getItemFactory($groupId=0) {
        if (!isset($this->item_factory[$groupId])) {
            $this->item_factory[$groupId] =& new Docman_ItemFactory($groupId);
        }
        return $this->item_factory[$groupId];
    }

    /**
    * Return true if the user can access the item
    * 
    * can access = user can read the item && user can access its parent
    * 
    * @return boolean
    */
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
    
    /**
    * Return true if the user can read the item
    * 
    * User can read an item if:
    * - he is super user,
    * - he is admin of the current docman,
    * - he can write the item (@see userCanWrite),
    * - or one of his ugroups has READ permission on the item
    * @return boolean
    */
    function userCanRead(&$user, $item_id) {
        if(!isset($this->cache_read[$user->getId()][$item_id])) {
            $pm =& $this->_getPermissionManagerInstance();
            $this->cache_read[$user->getId()][$item_id] = $user->isSuperUser() 
                || $this->userCanAdmin($user) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_READ', $user->getUgroups($this->groupId, array())) 
                || $this->userCanWrite($user, $item_id);
                
        }
        return $this->cache_read[$user->getId()][$item_id];
    }

    /**
    * Return true if the user can write the item
    * 
    * User can read an item if:
    * - he is super user,
    * - he is admin of the current docman,
    * - he can manage the item (@see userCanManage),
    * - or one of his ugroups has WRITE permission on the item
    * @return boolean
    */
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

    /**
    * Return true if the user can write the item
    * 
    * User can read an item if:
    * - he is super user,
    * - he is admin of the current docman,
    * - or one of his ugroups has MANAGE permission on the item
    * @return boolean
    */
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

    /**
    * Return true if the user has one of his ugroups with ADMIN permission on docman
    * @return boolean
    * @access protected
    */
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

    /**
    * Return true if the user can administrate the current docman
    * @return boolean
    */
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

    /**
    * Return true if the current user can administrate the current docman
    * @return boolean
    * @see userCanAdmin
    */
    function currentUserCanAdmin() {
        $user =& $this->getCurrentUser();
        return $this->userCanAdmin($user);
    }

    /**
     * Check if the current logged user has write access on a item tree.
     *
     * @param $itemId Integer the parent item id.
     * @see userCanWriteSubItems
     * @return boolean
     */
    function currentUserCanWriteSubItems($itemId) {
        $user =& $this->getCurrentUser();
        return $this->userCanWriteSubItems($user, $itemId);
    }

    /**
     * Check if given user has write access on a item tree.
     *
     * @param $user   User User object.
     * @param $itemId Integer The parent item id.
     * @return boolean
     */
    function userCanWriteSubItems(&$user, $itemId) {
        $item    =& $this->_getItemTreeForPermChecking($itemId, $user);
        $this->subItemsWritableVisitor = new Docman_SubItemsWritableVisitor($this->groupId, $user);
        return $item->accept($this->subItemsWritableVisitor);
    }

    /**
     * Get a item tree without permission checking.
     *
     * Get all sub-items, not deleted, not obsolete regardtheless of the
     * permissions of the user.
     * WARNING: use the result tree carfully as you may expose protected data
     * by mistake.
     */
    function &_getItemTreeForPermChecking($itemId, $user) {
        $itemFactory = $this->_getItemFactory($this->groupId);
        $item = $itemFactory->getItemSubTree($itemId,
                                             array('user' => &$user,
                                                   'ignore_perms' => true,
                                                   'ignore_collapse' => true));
        return $item;
    }

    /**
     * Setup the 'IsWritable' visitor object.
     */
    function &getSubItemsWritableVisitor() {
        return $this->subItemsWritableVisitor;
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

    function &getCurrentUser() {
        if($this->currentUser === null) {
            $um =& UserManager::instance();
            $this->currentUser = $um->getCurrentUser();
        }
        return $this->currentUser;
    }

}

?>
