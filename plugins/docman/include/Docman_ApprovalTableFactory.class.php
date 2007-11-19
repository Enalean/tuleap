<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

require_once('Docman_ApprovalTable.class.php');
require_once('Docman_ApprovalTableNotificationCycle.class.php');

class Docman_ApprovalTableFactory {
    var $item;
    var $reviewerCache;
    var $err;
    var $warn;

    function Docman_ApprovalTableFactory($item) {
        $this->item = $item;
        $this->reviewerCache = null;

        // Cache of error messages
        $this->err = array();
        $this->err['db'] = array();
        $this->err['perm'] = array();
        $this->err['notreg'] = array();
        $this->warn = array();
        $this->warn['double'] = array();
    }

    function createTableFromRow($row) {
        $table = new Docman_ApprovalTable();
        $table->initFromRow($row);
        return $table;
    }

    function createTable($userId) {
        $dao =& $this->_getApprovalTableDao();
        return $dao->createTable($this->item->getId(), $userId, '');
    }

    function tableExist() {
        $dao =& $this->_getApprovalTableDao();
        return $dao->tableExist($this->item->getId());
    }

    function createTableIfNotExist($userId) {
        if($this->tableExist()) {
            return true;
        }
        else {
            return $this->createTable($userId);
        }
    }

    function deleteTable() {
        $dao =& $this->_getApprovalTableDao();
        $deleted = $dao->deleteTable($this->item->getId());
        if($deleted) {
            $deleted = $dao->truncateTable($this->item->getId());
        }
        return $deleted;
    }

    /**
     * Update table settings:
     * - status
     * - notification
     * This action triggers reviewer notification if the update succeed.
     */
    function updateTable($status, $notification, $description) {
        $dao =& $this->_getApprovalTableDao();
        $updated = $dao->updateTable($this->item->getId(), '', $status, $notification, $description);
        if($updated) {
            // It will be great to have a generic feedback mecanism to report
            // several messages (Here: report that the table was successfully
            // updated and report things about review).
            $this->notifyReviewers();
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Return an ApprovalTable object. If the parameter is 'true' (default),
     * it appends the list of reviewers to the table.
     */
    function getTable($withReviewers = true) {
        $table = null;

        $dao =& $this->_getApprovalTableDao();
        
        $dar = $dao->getTableById($this->item->getId());
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $table = $this->createTableFromRow($row);
        }

        if($withReviewers && $table !== null) {
            $dar = $dao->getReviewerList($this->item->getId());
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                $reviewer = $this->createReviewerFromRow($row);
                $table->addReviewer($reviewer);
                unset($reviewer);
                $dar->next();
            }
        }
        
        return $table;
    }

    /**
     * Call the right notification method regarding the notification type.
     * @access: private
     */
    function _notifyReviewers($notification) {
        $res = false;
        $atsm =& $this->_getApprovalTableNotificationCycle(true);
        switch($notification) {
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
            $res = $atsm->notifyAllAtOnce();
            break;
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
            $res = $atsm->notifyNextReviewer();
            break;
        default:
        }
        return $res;
    }

    /**
     * Handle table notification.
     *
     * Trigger notification if all the parameters allow it:
     * - table exist.
     * - table enabled.
     * - notification not disabled.
     */
    function notifyReviewers() {
        $table = $this->getTable(false);
        if($table !== null
           && $table->isEnabled() 
           && $table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
            return $this->_notifyReviewers($table->getNotification());
        }
        else {
            return false;
        }
    }

    //
    // User Management
    //

    function createReviewerFromRow($row) {
        $reviewer = new Docman_ApprovalReviewer();
        $reviewer->initFromRow($row);
        return $reviewer;
    }

    /**
     * Return the list of ugroup selectable to fill the notification table.
     *
     * It contains: all dynamic ugroups plus project members and admins.
     */
    function getUgroupsAllowedForTable($groupId) {
        $res = ugroup_db_get_existing_ugroups($groupId, array($GLOBALS['UGROUP_PROJECT_MEMBERS'],
                                                              $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $ugroups = array();
        while($row = db_fetch_array($res)) {
            $r = array();
            $r['value'] = $row['ugroup_id'];
            $r['text'] = util_translate_name_ugroup($row['name']);
            $ugroups[] = $r;
        }

        return $ugroups;
    }

    /**
     * Create reviewer from database.
     * This method update 'isReviewer' cache (see corresponding method)
     * 
     * @return: Docman_ApprovalReviewer object.
     */
    function getReviewer($userId) {
        $reviewer = null;
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getReviewerById($this->item->getId(), $userId);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $reviewer = $this->createReviewerFromRow($row);
            $this->reviewerCache[$row['reviewer_id']] = true;
        }
        return $reviewer;
    }

    /**
     * Return true if given userid is member of the current table or not.
     * There is a cache for this information (the membership of users).
     */
    function isReviewer($userId) {
        if($this->reviewerCache === null) {
            $dao =& $this->_getApprovalTableDao();
            $dar = $dao->getReviewerList($this->item->getId());
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                $this->reviewerCache[$row['reviewer_id']] = true;
                $dar->next();
            }
        }
        if(isset($this->reviewerCache[$userId])) {
            return true;
        }
        return false;
    }

    /**
     * Add given user into the reviewer list if she's not already member.
     *
     * @access: private
     */
    function _addUser($userId) {
        $dPm =& Docman_PermissionsManager::instance($this->item->getGroupId());
        $um =& $this->_getUserManager();
        $user =& $um->getUserById($userId);
        if($dPm->userCanRead($user, $this->item->getId())) {
            if(!$this->isReviewer($user->getId())) {
                $dao =& $this->_getApprovalTableDao();
                $added = $dao->addUser($this->item->getId(), $user->getId());
                if($added) {
                    $this->reviewerCache[$user->getId()] = true;
                    return true;
                } else {
                    $this->err['db'][] = $user->getRealName();
                }
            } else {
                $this->warn['double'][] = $user->getRealName();
            }
        } else {
            $this->err['perm'][] = $user->getRealName();
        }
        return false;
    }

    /**
     * Add a list of user in the reviewer list.
     *
     * Note: we don't test if the user can read the document she has to review.
     * @todo: Report users not found in the CodeX user list.
     *
     * @param Array of string $userArray this method try to find a matching
     *   user in the given list. A matching user is a Active or Restricted
     *   CodeX user.
     * @return int number of users added.
     */
    function addUsers($userArray) {
        $nbUserAdded = 0;
        foreach($userArray as $user) {
            $added = false;
            $userName = util_user_finder($user, true);
            if($userName != '') {
                $res = user_get_result_set_from_unix($userName);
                if($res) {
                    $added = $this->_addUser(db_result($res, 0, 'user_id'));
                    if($added) {
                        $nbUserAdded++;
                    }
                }
            } else {
                $this->err['notreg'][] = $user;
            }
        }
        return $nbUserAdded;
    }

    /**
     * Add members of the given ugroup to the reviewer list.
     *
     * @return true if at least one user was added to the list.
     */
    function addUgroup($ugroupId) {
        $nbUserAdded = 0;
        $nbMembers = 0;

        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getUgroupMembers($ugroupId, $this->item->getGroupId());
        if($dar && !$dar->isError()) {
            $dar->rewind();
            while($dar->valid()) {
                $nbMembers++;
                $row = $dar->current();
                $added = $this->_addUser($row['user_id']);
                if($added) {
                    $nbUserAdded++;
                }
                $dar->next();
            }
        }
        if($nbUserAdded == $nbMembers) {
            return true;
        }
        return false;
    }

    /**
     * Update user rank in the reviewer list.
     */
    function updateUser($userId, $rank) {
        $dao =& $this->_getApprovalTableDao();
        return $dao->updateUser($this->item->getId(), $userId, $rank);
    }

    /**
     * Delete user from reviewer list.
     */
    function delUser($userId) {
        $dao =& $this->_getApprovalTableDao();
        $deleted = $dao->delUser($this->item->getId(), $userId);
        if($deleted) {
            if(isset($this->reviewerCache[$userId])) {
                unset($this->reviewerCache[$userId]);
            }
            return true;
        }
        return false;
    }
    
    /**
     * Update user review.
     */
    function updateReview($review) {
        $dao =& $this->_getApprovalTableDao();
        $updated = $dao->updateReview($this->item->getId(),
                                  $review->getId(),
                                  $review->getReviewDate(),
                                  $review->getState(),
                                  $review->getComment(),
                                  $review->getVersion());
        if($updated) {
            $atsm =& $this->_getApprovalTableNotificationCycle();
            $atsm->reviewUpdated($review);
            return true;
        }
        return false;
    }

    function getReviewStateName($state) {
        return Docman::txt('approval_review_state_'.$state);
    }

    function getNotificationTypeName($type) {
        return Docman::txt('details_approval_notif_'.$type);
    }

    /**
     * Return all the review where the user doesn't commit himself yet.
     */
    function getAllPendingReviewsForUser($userId) {
        $reviewsArray = array();
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getAllReviewsForUserByState($userId, PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
        $docmanUrl = get_server_url().'/plugins/docman';
        while($dar->valid()) {
            $row = $dar->current();
            $baseUrl = $docmanUrl.'/?group_id='.$row['group_id'];
            $url = $baseUrl.'&action=details&section=approval&id='.$row['item_id'].'&user_id='.$userId;
            $reviewsArray[] = array('group' => $row['group_name'],
                                    'group_id' => $row['group_id'],
                                    'title' => $row['title'],
                                    'date'  => $row['date'],
                                    'url'   => $url);
            $dar->next();
        }
        return $reviewsArray;
    }

    /**
     * Return all the approval table not deleted and not closed where the user
     * is the table owner.
     */
    function getAllApprovalTableForUser($userId) {
        $reviewsArray = array();
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getAllApprovalTableForUser($userId);
        $docmanUrl = get_server_url().'/plugins/docman';
        while($dar->valid()) {
            $row = $dar->current();
            $baseUrl = $docmanUrl.'/?group_id='.$row['group_id'];
            $url = $baseUrl.'&action=details&section=approval&id='.$row['item_id'];
            $reviewsArray[] = array('group' => $row['group_name'],
                                    'group_id' => $row['group_id'],
                                    'title' => $row['title'],
                                    'date'  => $row['date'],
                                    'url'   => $url);
            $dar->next();
        }
        return $reviewsArray;
    }

    //
    // Class accessor
    //

    function &_getApprovalTableDao() {
        $dao = new Docman_ApprovalTableDao(CodexDataAccess::instance());
        return $dao;
    }

    function &_getMail() {
        //require_once('common/mail/TestMail.class.php');
        //$mail = new TestMail();
        //$mail->_testDir = '/local/vm16/codev/servers/docman-2.0/var/spool/mail';
        $mail = new Mail();
        return $mail;
    }

    function &_getItemFactory() {
        $i = new Docman_ItemFactory();
        return $i;
    }

    function &_getUserManager() {
        $um =& UserManager::instance();
        return $um;
    }

    function &_getApprovalTableNotificationCycle($withReviewers=false) {
        $atsm = new Docman_ApprovalTableNotificationCycle();

        $table = $this->getTable($withReviewers);
        $atsm->setTable($table);

        $itemFactory = $this->_getItemFactory();
        $item = $itemFactory->getItemFromDb($this->item->getId());
        $atsm->setItem($item);

        $um =& $this->_getUserManager();
        $owner =& $um->getUserById($table->getOwner());
        $atsm->setOwner($owner);
        return $atsm;
    }
}

?>
