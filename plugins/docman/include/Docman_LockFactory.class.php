<?php
/**
 * Copyright (c) Enalean, 2019-present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Manage all the locking stuff of items
 *
 * When an item is locked, it's modifiable (content and metadata) only by the
 * locker.
 * Once locked, only the locker or a Document Manager can release the lock.
 * While an item is locked, it's still accessible by Document Reader.
 */
class Docman_LockFactory
{
    protected $_cachedItem;
    /**
     * @var Docman_LockDao
     */
    private $dao;
    /**
     * @var Docman_Log
     */
    private $docman_log;

    public function __construct(Docman_LockDao $dao, Docman_Log $docman_log)
    {
        $this->dao           = $dao;
        $this->docman_log    = $docman_log;
    }

   /**
    * Retrieve lock infos on all locked documents in a project
    *
    * @param int $groupId project id.
    *
    * @return DataAccessResult|false of lockinfos or false if there isn't any document locked inside the project.
    */
    public function getProjectLockInfos($groupId)
    {
        $dar = $this->dao->searchLocksForProjectByGroupId($groupId);
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
    public function getLockInfoForItem($item)
    {
        $dar = $this->dao->searchLockForItem($item->getId());
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
     * @return bool
     */
    public function userIsLocker($item, $user)
    {
        return $this->userIsLockerByItemId($item->getId(), $user);
    }

    /**
     * Retrun true if given user locked given item
     *
     * @param int $itemId Item to test
     * @param PFUser   $user   User to test
     *
     * @return bool
     */
    public function userIsLockerByItemId($itemId, $user)
    {
        if (
            $this->itemIsLockedByItemId($itemId) &&
            $this->_cachedItem[$itemId]['user_id'] == $user->getId()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Return true if item is locked
     *
     * @param Docman_Item $item Item to test
     *
     * @return bool
     */
    public function itemIsLocked($item)
    {
        return $this->itemIsLockedByItemId($item->getId());
    }

    /**
     * Return true if item is locked
     *
     * @return bool
     */
    public function itemIsLockedByItemId($itemId)
    {
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
     * @param int $itemId An item_id in the project
     *
     * @return void
     */
    public function _cacheLocksForProject($itemId)
    {
        if ($this->_cachedItem === null) {
            $this->_cachedItem = array();
            $dar               = $this->dao->searchLocksForProjectByItemId($itemId);
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
     * @return DataAccessResult|false
     */
    public function retreiveLocksForItems(array $itemIds)
    {
        $dar = $this->dao->searchLocksForItemIds($itemIds);
        if ($dar === false) {
            return false;
        }
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
     * @return void
     */
    public function lock($item, $user)
    {
        if (! $this->itemIsLocked($item)) {
            $this->dao->addLock($item->getId(), $user->getId(), $_SERVER['REQUEST_TIME']);
            $this->logLock($item, $user);
        }
    }

    /**
     * Release locked item
     *
     * @param Docman_Item $item Item to lock
     * @param PFUser      $user
     *
     * @return void
     */
    public function unlock($item, $user)
    {
        if ($this->itemIsLocked($item)) {
            $this->dao->delLock($item->getId());
            $this->logUnlock($item, $user);
        }
    }

    /**
     * Raise "Lock add" event
     *
     * @param Docman_Item $item Locked item
     * @param PFUser      $user Who locked the item
     *
     * @return void
     */
    private function logLock(Docman_Item $item, PFUser $user)
    {
        $p             = [
            'group_id' => $item->getGroupId(),
            'item'     => $item,
            'user'     => $user
        ];
        $this->docman_log->log('plugin_docman_event_lock_add', $p);
    }

    /**
     * Raise "Lock deletion" event
     *
     * @param Docman_Item $item Unlocked item
     * @param PFUser      $user Who unlocked the item
     *
     * @return void
     */
    private function logUnlock(Docman_Item $item, PFUser $user)
    {
        $p             = [
            'group_id' => $item->getGroupId(),
            'item'     => $item,
            'user'     => $user
        ];
        $this->docman_log->log('plugin_docman_event_lock_del', $p);
    }
}
