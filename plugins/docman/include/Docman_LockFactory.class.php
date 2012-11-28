<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'Docman_LockDao.class.php';

/**
 * Manage all the locking stuff of items
 * 
 * When an item is locked, it's modifiable (content and metadata) only by the
 * locker.
 * Once locked, only the locker or a Document Manager can release the lock.
 * While an item is locked, it's still accessible by Document Reader.
 */
class Docman_LockFactory {
    protected $_cachedItem = null;

    function __construct() {
    }

   /**
    * Retrieve lock infos on all locked documents in a project
    *
    * @param Integer $groupId project id.
    *
    * @return DataAccessResult of lockinfos or false if there isn't any document locked inside the project.
    */
    function getProjectLockInfos($groupId) {
        $items = array();
        $dao = $this->getDao();
        $dar = $dao->searchLocksForProjectByGroupId($groupId);
        if ($dar && !$dar->isError()) {
            return $dar;
        } else {
            return false;
        }
    }

    /**
     * Get lock details for one item
     * 
     * @param Docman_Item $item Item
     * 
     * @return Array
     */
    function getLockInfoForItem($item) {
        $dao = $this->getDao();
        $dar = $dao->searchLockForItem($item->getId());
        if ($dar && !$dar->isError() && $dar->rowCount() === 1) {
            return $dar->current();
        }
        return false;
    }

    /**
     * Retrun true if given user locked given item
     * 
     * @param Docman_Item $item Item to test
     * @param PFUser        $user User to test
     * 
     * @return Boolean
     */
    function userIsLocker($item, $user) {
        return $this->userIsLockerByItemId($item->getId(), $user);
    }

    /**
     * Retrun true if given user locked given item
     *
     * @param Intger $itemId Item to test
     * @param PFUser   $user   User to test
     *
     * @return Boolean
     */
    function userIsLockerByItemId($itemId, $user) {
        if ($this->itemIsLockedByItemId($itemId) &&
            $this->_cachedItem[$itemId]['user_id'] == $user->getId()) {
            return true;
        }
        return false;
    }

    /**
     * Return true if item is locked
     * 
     * @param Docman_Item $item Item to test
     * 
     * @return Boolean
     */
    function itemIsLocked($item) {
        return $this->itemIsLockedByItemId($item->getId());
    }

    /**
     * Return true if item is locked
     * 
     * @param Docman_Item $item Item to test
     * 
     * @return Boolean
     */
    function itemIsLockedByItemId($itemId) {
        $this->_cacheLocksForProject($itemId);
        if (isset($this->_cachedItem[$itemId])) {
            return true;
        }
        return false;
    }

    /**
     * Cache all locked items in a project
     * 
     * We use item_id instead of group_id because of Docman_Permissions that only
     * deals with item_id. To avoid a lot of parameters to pass on, we use SQL
     * to look for all items that belongs to the same project than given item and
     * to retrieve only those that are locked.
     * 
     * @param Integer $itemId An item_id in the project
     * 
     * @return void
     */
    function _cacheLocksForProject($itemId) {
        if ($this->_cachedItem === null) {
            $this->_cachedItem = array();
            $dao = $this->getDao();
            $dar = $dao->searchLocksForProjectByItemId($itemId);
            foreach ($dar as $row) {
                $this->_cachedItem[$row['item_id']] = $row;
            }
        }
    }

    /**
     * Retreive lock info for several items.
     * 
     * @param Array $itemIds
     * 
     * @return DataAccessResult
     */
    function retreiveLocksForItems(array $itemIds) {
        $dao = $this->getDao();
        $dar = $dao->searchLocksForItemIds($itemIds);
        foreach ($dar as $row) {
            $this->_cachedItem[$row['item_id']] = $row;
        }
        return $dar;
    }
    
    /**
     * Lock an item for a user
     *
     * @param Docman_Item $item Item to lock
     * @param PFUser        $user User who lock
     *
     * @return Boolean
     */
    function lock($item, $user) {
        $dao = $this->getDao();
        return $dao->addLock($item->getId(), $user->getId(), $_SERVER['REQUEST_TIME']);
    }

    /**
     * Release locked item
     *
     * @param Docman_Item $item Item to lock
     *
     * @return Boolean
     */
    function unlock($item) {
        $dao = $this->getDao();
        return $dao->delLock($item->getId());
    }

    /**
     * Wrapper for Docman_LockDao
     *
     * @return Docman_LockDao
     */
    function getDao() {
        return new Docman_LockDao(CodendiDataAccess::instance());
    }
}

?>