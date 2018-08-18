<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_PermissionsManager {

    const PLUGIN_OPTION_DELETE = 'only_siteadmin_can_delete';
    const PLUGIN_DOCMAN_ADMIN  = 'PLUGIN_DOCMAN_ADMIN';

    protected $groupId;
    protected $cache_access = array();
    protected $cache_read   = array();
    protected $cache_write  = array();
    protected $cache_manage = array();
    protected $cache_admin  = array();
    protected $dao          = null;

    // No cache, just convenient accessor.
    protected $subItemsWritableVisitor = null;

    private $lockFactory = null;

    private static $instance = array();

    private $plugin;

    /**
     * Constructor, private to enforce singleton (use instance() instead)
     *
     * @param Integer $groupId
     *
     * @return void
     */
    private function __construct($groupId) {
        $this->groupId = $groupId;
        $this->plugin  = PluginManager::instance()->getPluginByName(docmanPlugin::SERVICE_SHORTNAME);
    }

    /**
     * The manager is a singleton
     *
     * @param Integer $groupId Project id
     *
     * @return Docman_PermissionsManager
     */
    public static function instance($groupId) {
        if(!isset(self::$instance[$groupId])) {
            self::$instance[$groupId] = new Docman_PermissionsManager($groupId);
        }
        return self::$instance[$groupId];
    }

    /**
     * A singleton cannot be cloned.
     *
     * @return void
     */
    public function __clone() {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    /**
     * Wrapper for PermissionManager
     *
     * @return PermissionsManager
     */
    function _getPermissionManagerInstance() {
        return PermissionsManager::instance();
    }

    /**
     * Wrapper for Docman_PermissionsManagerDao
     * @return Docman_PermissionsManagerDao
     */
    function getDao() {
        if($this->dao === null) {
            $this->dao = new Docman_PermissionsManagerDao(CodendiDataAccess::instance(), $this->groupId);
        }
        return $this->dao;
    }

    /**
     * Return an item factory
     *
     * @param Integer $groupId
     * @return Docman_ItemFactory
     */
    function _getItemFactory($groupId=0) {
        return Docman_ItemFactory::instance($groupId);
    }

    /**
     * Wrapper for LockFactory
     *
     * @return Docman_LockFactory
     */
    function getLockFactory() {
        if (!isset($this->lockFactory)) {
            $this->lockFactory = new Docman_LockFactory();
        }
        return $this->lockFactory;
    }

    /**
    * Return true if the user can access the item
    *
    * can access = user can read the item && user can access its parent
    *
    * @return boolean
    */
    function userCanAccess($user, $item_id) {
        if (!isset($this->cache_access[$user->getId()][$item_id])) {
            $can_read = $this->userCanRead($user, $item_id);
            if ($can_read) {
                $item_factory = $this->_getItemFactory();
                $item = $item_factory->getItemFromDb($item_id);
                if ($item) {
                    $can_access_parent = $item->getParentId() == 0 || $this->userCanAccess($user, $item->getParentId());
                    $this->cache_access[$user->getId()][$item_id] = $can_access_parent;
                } else {
                    $this->cache_access[$user->getId()][$item_id] = false;
                }
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
    * - he can write the item (@see _userHasWritePermission),
    *   --> Please note that we test the write permission and not is user can
    *       actually write the item (in case of lock). So user may not have
    *       'userCanWrite = true' but being able to see a document because of
    *       the access rights
    * - or one of his ugroups has READ permission on the item
    * @return boolean
    */
    function userCanRead($user, $item_id) {
        if(!isset($this->cache_read[$user->getId()][$item_id])) {
            $pm = $this->_getPermissionManagerInstance();
            $canRead = $user->isSuperUser()
                || $this->userCanAdmin($user) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_READ', $user->getUgroups($this->groupId, array()))
                || $this->_userHasWritePermission($user, $item_id);

            $this->_setCanRead($user->getId(), $item_id, $canRead);
        }
        return $this->cache_read[$user->getId()][$item_id];
    }

    /**
    * Return true if the user can write the item
    *
    * This method takes into account permissions and lock.
    *
    * User can read an item if:
    * - he is super user,
    * - he is admin of the current docman,
    * - he can manage the item (@see userCanManage),
    * - one of his ugroups has WRITE permission on the item
    * - item is not locked or if user is lock owner.
    *
    * @return boolean
    */
    function userCanWrite($user, $item_id) {
        if(!isset($this->cache_write[$user->getId()][$item_id])) {
            // Check permissions
            $hasWritePerm = $this->_userHasWritePermission($user, $item_id);
            // Check lock status
            $itemLocked = $this->_itemIsLockedForUser($user, $item_id);

            $canWrite = false;
            if(!$itemLocked) {
                $canWrite = $hasWritePerm;
            }

            $this->_setCanWrite($user->getId(), $item_id, $canWrite);
        }
        return $this->cache_write[$user->getId()][$item_id];
    }

    public function userCanDelete(PFUser $user, Docman_Item $item)
    {
        return (
            ! $this->cannotDeleteBecauseNotSuperadmin($user)
            && $this->userCanWrite($user, $item->getId())
            && $this->userCanWrite($user, $item->getParentId())
        );
    }

    private function cannotDeleteBecauseNotSuperadmin(PFUser $user)
    {
        return (
            $this->plugin->getPluginInfo()->getPropertyValueForName(self::PLUGIN_OPTION_DELETE)
            && ! $user->isSuperUser()
        );
    }

    /**
     * Check if user as write permission on item
     *
     * This method only deals with the permissions set on item. If user has
     * write permission, it will automatically gives read permission too.
     *
     * @param PFUser    $user
     * @param Integer $item_id
     *
     * @return Boolean
     */
    function _userHasWritePermission($user, $item_id) {
        $pm = $this->_getPermissionManagerInstance();
        $canWrite = $user->isSuperUser()
                || $this->userCanAdmin($user) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_WRITE', $user->getUgroups($this->groupId, array()))
                || $this->userCanManage($user, $item_id);
        if($canWrite) {
            $this->_setCanRead($user->getId(), $item_id, true);
        }
        return $canWrite;
    }

    /**
     * Check if the item is locked for the user.
     *
     * Return true if there is no lock on the item or if there is one but user
     * is owner of the lock. This method doesn't check write permission.
     *
     * @param PFUser    $user
     * @param Integer $item_id
     *
     * @return Boolean
     */
    function _itemIsLockedForUser($user, $item_id) {
        $locked = true;
        $lockFactory = $this->getLockFactory();
        if($lockFactory->itemIsLockedByItemId($item_id)) {
            if($lockFactory->userIsLockerByItemId($item_id, $user)) {
                $locked = false;
            } else {
                if($this->userCanManage($user, $item_id)) {
                    $locked = false;
                }
            }
        } else {
            $locked = false;
        }
        return $locked;
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
    function userCanManage($user, $item_id) {
        if(!isset($this->cache_manage[$user->getId()][$item_id])) {
            $pm = $this->_getPermissionManagerInstance();
            $canManage = $user->isSuperUser()
                || $this->userCanAdmin($user) //There are default perms for admin
                || $pm->userHasPermission($item_id, 'PLUGIN_DOCMAN_MANAGE', $user->getUgroups($this->groupId, array())) ;
            $this->_setCanManage($user->getId(), $item_id, $canManage);
        }
        return $this->cache_manage[$user->getId()][$item_id];
    }

    /**
    * Return true if the user has one of his ugroups with ADMIN permission on docman
    * @return boolean
    * @access protected
    */
    function _isUserDocmanAdmin($user) {
        require_once('www/project/admin/permissions.php');
        $has_permission = false;

        $permission_type = self::PLUGIN_DOCMAN_ADMIN;
        $object_id       = $this->groupId;

        // permissions set for this object.
        $res = permission_db_authorized_ugroups($permission_type, (int)$object_id);
        if (db_numrows($res) < 1 && $permission_type == self::PLUGIN_DOCMAN_ADMIN) {
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
    public function userCanAdmin(PFUser $user)
    {
        if (! isset($this->cache_admin[$user->getId()][$this->groupId])) {
            $has_permission = $user->isSuperUser() || $user->isAdmin($this->groupId);
            if (! $has_permission) {
                $has_permission = $this->_isUserDocmanAdmin($user);
            }
            $this->cache_admin[$user->getId()][$this->groupId] = $has_permission;
        }

        return $this->cache_admin[$user->getId()][$this->groupId];
    }

    /**
     * Check if the current logged user has write access on a item tree.
     *
     * @param $itemId Integer the parent item id.
     * @see userCanWriteSubItems
     * @return boolean
     */
    public function currentUserCanWriteSubItems($itemId)
    {
        $user = UserManager::instance()->getCurrentUser();

        return $this->userCanWriteSubItems($user, $itemId);
    }

    /**
     * Check if given user has write access on a item tree.
     *
     * @param $user   User User object.
     * @param $itemId Integer The parent item id.
     * @return boolean
     */
    function userCanWriteSubItems($user, $itemId) {
        $item = $this->_getItemTreeForPermChecking($itemId, $user);
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
    function _getItemTreeForPermChecking($itemId, $user) {
        $itemFactory = $this->_getItemFactory($this->groupId);
        $srcItem = $itemFactory->getItemFromDb($itemId);
        $item = $itemFactory->getItemSubTree($srcItem, $user, true, true);
        return $item;
    }

    /**
     * Setup the 'IsWritable' visitor object.
     */
    function getSubItemsWritableVisitor() {
        return $this->subItemsWritableVisitor;
    }

    function cloneItemPermissions($srcItemId, $dstItemId, $toGroupId) {
        $perms = array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE');
        $pm = $this->_getPermissionManagerInstance();
        $pm->clonePermissions($srcItemId, $dstItemId, $perms, $toGroupId);
    }

    function cloneDocmanPermissions($srcGroupId, $dstGroupId) {
        $perms = array(self::PLUGIN_DOCMAN_ADMIN);
        $pm = $this->_getPermissionManagerInstance();
        $pm->clonePermissions($srcGroupId, $dstGroupId, $perms, $dstGroupId);
    }

    function setDefaultItemPermissions($itemId, $force=false) {
        $dao = $this->getDao();

        $dao->setDefaultPermissions($itemId, 'PLUGIN_DOCMAN_READ', $force);
        $dao->setDefaultPermissions($itemId, 'PLUGIN_DOCMAN_WRITE', $force);
        $dao->setDefaultPermissions($itemId, 'PLUGIN_DOCMAN_MANAGE', $force);
    }

    function setDefaultDocmanPermissions($groupId) {
        $dao = $this->getDao();

        $dao->setDefaultPermissions($groupId, self::PLUGIN_DOCMAN_ADMIN);
    }

    /**
     * Retreive and cache all read permissions for a list of itemIds
     *
     * In order to reduce the perf overhead of permission checking, fetch one for
     * all the permissions set on all given items and store them in cache.
     *
     * @param Array $itemsIds
     * @param PFUser $user
     *
     * @return void
     */
    function retreiveReadPermissionsForItems($itemsIds, $user){
        $dao    = $this->getDao();
        $userId = $user->getId();

        // Collect the item ids we need to check
        $objIds = array();
        foreach($itemsIds as $itemid) {
            if($this->userCanAdmin($user)) {
                // Docman admin has all rights
                $this->_setCanManage($userId, $itemid, true);
            } else {
                // Otherwise, initialize the perm to "nothing". This is possible here
                // as we fetch all the permissions related to an item in the same time
                // if we only get READ for an item, the item will only be readable, later
                // userCanWrite doesn't need to fetch permissions again.
                // This is very specific to this method. With "traditional" userCanRead,
                // without cache, fetching read permission gives no info about WRITE or
                // MANAGE perms.
                $this->_setNoAccess($userId, $itemid);
                $objIds[] = $itemid;
            }
        }

        if(count($objIds) > 0) {
            $perms = array("'PLUGIN_DOCMAN_READ'", "'PLUGIN_DOCMAN_WRITE'", "'PLUGIN_DOCMAN_MANAGE'");
            $dar = $dao->retreivePermissionsForItems($objIds, $perms, $user->getUgroups($this->groupId, array()));
            foreach($dar as $row) {
                switch($row['permission_type']) {
                case 'PLUGIN_DOCMAN_MANAGE':
                    $this->_setCanManage($userId, $row['object_id'], true);
                    break;
                case 'PLUGIN_DOCMAN_WRITE':
                    $this->_setCanWrite($userId, $row['object_id'], true);
                    break;
                case 'PLUGIN_DOCMAN_READ':
                    $this->_setCanRead($userId, $row['object_id'], true);
                    break;
                }
            }

            // Locks
            // Iter on all given item_ids and disable write if current user is not
            // lock owner and not doc manager.
            foreach($this->getLockFactory()->retreiveLocksForItems($objIds) as $row){
                if($row['user_id'] != $userId && !$this->cache_manage[$userId][$row['item_id']]) {
                    $this->cache_write[$userId][$row['item_id']] = false;
                }
            }
        }
    }

    /**
     * Revoke all access to the user if not already set.
     */
    function _setNoAccess($userId, $objectId) {
        $this->_revokeIfNotGranted($this->cache_read, $userId, $objectId);
        $this->_revokeIfNotGranted($this->cache_write, $userId, $objectId);
        $this->_revokeIfNotGranted($this->cache_manage, $userId, $objectId);
    }

    /**
     * Set READ access to the user
     *
     * If userCanRead, cache it. Otherwise, if read is not already granted,
     * block it.
     */
    function _setCanRead($userId, $objectId, $canRead) {
        if($canRead) {
            $this->cache_read[$userId][$objectId] = true;
        } else {
            $this->_revokeIfNotGranted($this->cache_read, $userId, $objectId);
        }
    }

    /**
     * Set WRITE and READ accesses to the user.
     *
     * If userCanWrite, cache it. Otherwise, if write is not already granted,
     * block it.
     */
    function _setCanWrite($userId, $objectId, $canWrite) {
        if($canWrite) {
            $this->cache_read[$userId][$objectId] = true;
            $this->cache_write[$userId][$objectId] = true;
        } else {
            $this->_revokeIfNotGranted($this->cache_write, $userId, $objectId);
        }
    }

    /**
     * Set MANAGE, WRITE and READ accesses to the user.
     *
     * If user cannot manage and manage is not already granted, block it.
     */
    function _setCanManage($userId, $objectId, $canManage) {
        if($canManage) {
            $this->cache_read[$userId][$objectId]   = true;
            $this->cache_write[$userId][$objectId]  = true;
            $this->cache_manage[$userId][$objectId] = true;
        } else {
            $this->_revokeIfNotGranted($this->cache_manage, $userId, $objectId);
        }
    }

    /**
     * Set given permission cache array to false if not already to true.
     *
     * @param Array   $array    A cache array ($this->cache_read, write, manage)
     * @param Integer $userId   User id
     * @param Integer $objectId Item id
     *
     * @return void
     */
    function _revokeIfNotGranted(&$array, $userId, $objectId) {
        if (!isset($array[$userId][$objectId])) {
            $array[$userId][$objectId] = false;
        }
    }

    function oneFolderIsWritable($user) {
        $oneWritable = false;

        $dao = $this->getDao();

        if($this->userCanAdmin($user)) {
            $oneWritable = true;
        }
        else {
            $oneWritable = $dao->oneFolderIsWritable($this->groupId, $user->getUgroups($this->groupId, array()));
        }

        return $oneWritable;
    }

    /**
    * Returns the integer value that corresponds to the permission
    */
    public static function getDefinitionIndexForPermission($p) {
        switch ($p) {
            case 'PLUGIN_DOCMAN_READ':
                return 1;
                break;
            case 'PLUGIN_DOCMAN_WRITE':
                return 2;
                break;
            case 'PLUGIN_DOCMAN_MANAGE':
                return 3;
                break;
            default:
                return 100;
                break;
        }
    }

    /**
     * Return the list of people to be notified as document managers for a given item
     *
     * For dynamics ugroups, we decided to force notification to project admin to avoid
     * notification of a lot of people if "Document Manager" set to "project_members" or
     * "all_users".
     *
     * @param Integer $objectId The id of the object
     * @param Project $project  The related project
     *
     * @return Array
     */
    function getDocmanManagerUsers($objectId, $project) {
        $userArray = array();
        $dao = $this->getDao();
        $dar = $this->_getPermissionManagerInstance()->getUgroupIdByObjectIdAndPermissionType($objectId, 'PLUGIN_DOCMAN_MANAGE');
        if ($dar) {
            foreach ($dar as $row) {
                if ($row['ugroup_id'] > 100) {
                    $darUg = $dao->getUgroupMembers($row['ugroup_id']);
                    foreach ($darUg as $rowUg) {
                        $userArray[$rowUg['email']] = $rowUg['language_id'];
                    }
                }
            }
        }
        return $userArray;
    }

    /**
     * Return the list of people to be notified as docman admins for the given project
     *
     * @param Project $project The related project
     *
     * @return Array
     */
    function getDocmanAdminUsers($project) {
        $userArray = array();
        $dao = $this->getDao();
        $dar = $dao->getDocmanAdminUgroups($project);
        if ($dar) {
            foreach ($dar as $row) {
                if ($row['ugroup_id'] > 100) {
                    $darUg = $dao->getUgroupMembers($row['ugroup_id']);
                    foreach ($darUg as $rowUg) {
                        $userArray[$rowUg['email']] = $rowUg['language_id'];
                    }
                }
            }
        }
        return $userArray;
    }

    /**
     * Return the list of people to be notified as project admins
     *
     * @param Project $project The related project
     *
     * @return Array
     */
    function getProjectAdminUsers($project) {
        $userArray = array();
        $dao = $this->getDao();
        $darDu = $dao->getProjectAdminMembers($project);
        if ($darDu) {
            foreach ($darDu as $rowDu) {
                $userArray[$rowDu['email']] = $rowDu['language_id'];
            }
        }
        return $userArray;
    }

    public function isUgroupUsed($ugroup_id) {
        if ($this->getDao()->isThereAnExplicitPermission($ugroup_id, $this->groupId)) {
            return true;
        }

        if ($this->getDao()->doAllItemsHaveExplicitPermissions($this->groupId)) {
            return false;
        }

        return $this->getDao()->isThereADefaultPermissionThatUsesUgroup($ugroup_id);
    }
}
