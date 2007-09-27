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
 * $Id$
 */

require_once('Docman_ApprovalTable.class.php');
require_once('Docman_ApprovalTableDao.class.php');
require_once('common/mail/Mail.class.php');

class Docman_ApprovalTableFactory {
    var $itemId;
    var $reviewerCache;

    function Docman_ApprovalTableFactory($itemId) {
        $this->itemId = $itemId;
        $this->reviewerCache = null;
    }

    function createTableFromRow($row) {
        $table = new Docman_ApprovalTable();
        $table->initFromRow($row);
        return $table;
    }

    function createTable($userId) {
        $dao =& $this->_getApprovalTableDao();
        return $dao->createTable($this->itemId, $userId, '');
    }

    function tableExist() {
        $dao =& $this->_getApprovalTableDao();
        return $dao->tableExist($this->itemId);
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
        $deleted = $dao->deleteTable($this->itemId);
        if($deleted) {
            $deleted = $dao->truncateTable($this->itemId);
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
        $updated = $dao->updateTable($this->itemId, '', $status, $notification, $description);
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
        
        $dar = $dao->getTableById($this->itemId);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $table = $this->createTableFromRow($row);
        }

        if($withReviewers && $table !== null) {
            $dar = $dao->getReviewerList($this->itemId);
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
     * Notify everybody in the same time
     *
     * @return boolean Will return false only if there is no table or no
     * reviewers to notify. If one notification fail, I don't have the tools to
     * report it to the user.
     */
    function notifyAllAtOnce() {
        $nbNotif = 0;
        $table = $this->getTable();
        if($table !== null) {
            $rIter = $table->getReviewerIterator();
            if($rIter !== null) {
                $rIter->rewind();
                while($rIter->valid()) {
                    $reviewer = $rIter->current();
                    if($reviewer->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET) {
                        $sent = $this->sendNotification($table->getOwner(), $reviewer->getId(), PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE, $table->getDescription());
                        if($sent) {
                            $nbNotif++; 
                        }
                    }
                    $rIter->next();
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        if($nbNotif > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sequential notification
     *
     * Send a mail to the first reviewer that didn't already commit herself
     * (review = not yet). If someone reject the document, CodeX doesn't send
     * any emails.
     */
    function notifySequential() {
        $table = $this->getTable(false);
        
        $dao =& $this->_getApprovalTableDao();

        $dar = $dao->getFirstReviewerByStatus($this->itemId, PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        if($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return false;
        }
        else {
            $dar = $dao->getFirstReviewerByStatus($this->itemId, PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                return $this->sendNotification($table->getOwner(), $row['reviewer_id'], PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL, $table->getDescription());
            }
        }
    }

    /**
     * Call the right notification method regarding the notification type.
     * @access: private
     */
    function _notifyReviewers($notification) {
        $res = false;
        switch($notification) {
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
            $res = $this->notifyAllAtOnce();
            break;
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
            $res = $this->notifySequential();
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

    /**
     * Build and send notification email to given user.
     */
    function sendNotification($ownerId, $reviewerId, $notificationType, $userComment) {
        $mail = $this->getNotificationEmail($ownerId, $reviewerId, $notificationType, $userComment);
        if($mail != null) {
            return $mail->send();
        }
        return false;
    }
    
    function getNotificationEmail($ownerId, $reviewerId, $notificationType, $userComment) {
        $itemFactory = $this->_getItemFactory();
        $item = $itemFactory->getItemFromDb($this->itemId);
        if($item) {
            // Users
            $um =& $this->_getUserManager();
            $owner    =& $um->getUserById($ownerId);
            $reviewer =& $um->getUserById($reviewerId);
            
            // Project
            $group = group_get_object($item->getGroupId());

            // Url
            $baseUrl = get_server_url().'/plugins/docman/?group_id='.$item->getGroupId();
            $itemUrl   = $baseUrl .'&action=show&id='.$item->getId();
            $reviewUrl = $baseUrl .'&action=details&section=approval&id='.$item->getId().'&user_id='.$reviewer->getId();

            // Notification style
            $notifStyle = '';
            switch($notificationType) {
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
                $notifStyle = Docman::txt('approval_notif_mail_notif_seq', array($GLOBALS['sys_name']));
                break;
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
                $notifStyle = Docman::txt('approval_notif_mail_notif_all');
                break;
            }

            // Comment
            $comment = '';
            if($userComment != '') {
                $comment = Docman::txt('approval_notif_mail_notif_owner_comment', array($userComment));
                $comment .= "\n\n";
            }

            $subj = Docman::txt('approval_notif_mail_subject', array($GLOBALS['sys_name'], $item->getTitle()));
            $body = Docman::txt('approval_notif_mail_body', array($item->getTitle(), 
                                                                  $group->getPublicName(),
                                                                  $owner->getRealName(),
                                                                  $itemUrl,
                                                                  $comment,
                                                                  $notifStyle,
                                                                  $reviewUrl,
                                                                  $owner->getEmail()));

            $mail =& $this->_getMail();
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setTo($reviewer->getEmail());
            $mail->setSubject($subj);
            $mail->setBody($body);

            return $mail;
        }
        else {
            return null;
        }
    }

    function getTableState() {
        $nbApproved = 0;
        $rejected = false;
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getReviewerList($this->itemId);
        $dar->rewind();
        while(!$rejected && $dar->valid()) {
            $row = $dar->current();
            switch($row['state']) {
            case PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED:
                $nbApproved++;
                break;
            case PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED:
                $rejected = true;
                break;
            }
            $dar->next();
        }
        if($rejected) {
            return PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED;
        }
        if($nbApproved == $dar->rowCount()) {
            return PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED;
        }
        return PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET;
    }

    /**
     * Notify table owner.
     *
     * If type == Rejected we notify table owner that table was rejected by
     *            $reviewerId
     * If type == Approved and reviewerId != null: notifies owner that
     *            $reviewerId approved the document
     * If type == Approved and reviewerId == null: notifies owner that all
     *            reviewers approved the document
     */
    function notifyOwner($type, $reviewerId=null) {
        $mailSent = false;
        $table = $this->getTable(false);
        if($table !== null) {
            $itemFactory = $this->_getItemFactory();
            $item = $itemFactory->getItemFromDb($this->itemId);

            $um =& $this->_getUserManager();
            $owner =& $um->getUserById($table->getOwner());

            $reviewer = null;
            if($reviewerId !== null) {
                $reviewer =& $um->getUserById($reviewerId);
            }

            $baseUrl = get_server_url().'/plugins/docman/?group_id='.$item->getGroupId();
            $reviewUrl = $baseUrl .'&action=details&section=approval&id='.$item->getId();

            switch($type) {
            case PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED:
                $mail =& $this->_getMail();
                $mail->setFrom($GLOBALS['sys_noreply']);
                $mail->setTo($owner->getEmail());
                $mail->setSubject(Docman::txt('approval_notif_reject_mail_subject', array($GLOBALS['sys_name'], $item->getTitle())));
                $mail->setBody(Docman::txt('approval_notif_reject_mail_body', array($item->getTitle(), $reviewUrl, $reviewer->getRealName(), $reviewer->getEmail())));
                $mailSent = $mail->send();
                break;
            case PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED:
                $itemPropertiesUrl = $baseUrl .'&action=edit&id='.$item->getId();

                $mail =& $this->_getMail();
                $mail->setFrom($GLOBALS['sys_noreply']);
                $mail->setTo($owner->getEmail());

                if($reviewer !== null) {
                    $mail->setSubject(Docman::txt('approval_notif_approve_user_mail_subject', array($GLOBALS['sys_name'], $item->getTitle())));
                    $mail->setBody(Docman::txt('approval_notif_approve_user_mail_body', array($item->getTitle(), $reviewUrl, $reviewer->getRealName(), $reviewer->getEmail())));
                } else {
                    $mail->setSubject(Docman::txt('approval_notif_approve_mail_subject', array($GLOBALS['sys_name'], $item->getTitle())));
                    $mail->setBody(Docman::txt('approval_notif_approve_mail_body', array($item->getTitle(), $reviewUrl, $itemPropertiesUrl)));
                }
                $mailSent = $mail->send();

                break;
            }
            return $mailSent;
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
     * Return the list of ugroups selectable to fill the notification table.
     *
     * It contains: all dynamic ugroups plus project members and admins.
     */
    function getUgroupsAllowedForTable($groupId) {
        $res = ugroup_db_get_existing_ugroups($groupId, array($GLOBALS['UGROUP_PROJECT_MEMBERS'],
                                                              $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $ugroups = array();
        while($row = db_fetch_array($res)) {
            $ugroups['vals'][] = $row['ugroup_id'];
            $ugroups['txts'][] = util_translate_name_ugroup($row['name']);
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
        $dar = $dao->getReviewerById($this->itemId, $userId);
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
            $dar = $dao->getReviewerList($this->itemId);
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
     * @todo: test is user can read the document.
     *
     * @access: private
     */
    function _addUser($userId) {
        $dao =& $this->_getApprovalTableDao();
        if(!$this->isReviewer($userId)) {
            $added = $dao->addUser($this->itemId, $userId);
            if($added) {
                $this->reviewerCache[$userId] = true;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
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
     * @return true if at least one user was added to the list.
     */
    function addUsers($userArray) {
        $nbUserAdded = 0;
        foreach($userArray as $user) {
            $userName = util_user_finder($user, true);
            if($userName != '') {
                $res = user_get_result_set_from_unix($userName);
                if($res) {
                    $added = $this->_addUser(db_result($res, 0, 'user_id'));
                    if($added) {
                        $nbUserAdded++;
                    }
                }
            }
        }
        if($nbUserAdded > 0) {
            return true;
        }
        return false;
    }

    /**
     * Add members of the given ugroup to the reviewer list.
     *
     * @return true if at least one user was added to the list.
     */
    function addUgroup($ugroupId) {
        $nbUserAdded = 0;

        $groupId = -1;
        if($ugroupId <= 100) {
            $itemFactory = $this->_getItemFactory();
            $item = $itemFactory->getItemFromDb($this->itemId);
            $groupId = $item->getGroupId();
        }
 
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getUgroupMembers($ugroupId, $groupId);
        if($dar && !$dar->isError()) {
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                $added = $this->_addUser($row['user_id']);
                if($added) {
                    $nbUserAdded++;
                }
                $dar->next();
            }
        }
        if($nbUserAdded > 0) {
            return true;
        }
        return false;
    }

    /**
     * Update user rank in the reviewer list.
     */
    function updateUser($userId, $rank) {
        $dao =& $this->_getApprovalTableDao();
        return $dao->updateUser($this->itemId, $userId, $rank);
    }

    /**
     * Delete user from reviewer list.
     */
    function delUser($userId) {
        $dao =& $this->_getApprovalTableDao();
        $deleted = $dao->delUser($this->itemId, $userId);
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
        $updated = $dao->updateReview($this->itemId,
                                  $review->getId(),
                                  $review->getReviewDate(),
                                  $review->getState(),
                                  $review->getComment(),
                                  $review->getVersion());
        if($updated) {
            if($review->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED) {
                $this->notifyOwner(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED, $review->getId());
            }
            elseif($this->getTableState() == PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED) {
                $this->notifyOwner(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
            }
            else {
                if($review->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED) {
                    // Notify table owner
                    $this->notifyOwner(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED, $review->getId());

                    $table = $this->getTable(false);
                    if($table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
                        $this->notifySequential();
                    }
                }
            }
            return true;
        }
        return false;
    }

    function getReviewStateName($state) {
        return Docman::txt('approval_review_state_'.$state);
    }

    function getAllPendingReviewsForUser($userId) {
        $reviewsArray = array();
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getAllReviewsForUserByState($userId, PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
        while($dar->valid()) {
            $row = $dar->current();
            
            $baseUrl = get_server_url().'/plugins/docman/?group_id='.$row['group_id'];
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

    function _getUserManager() {
        $um =& UserManager::instance();
        return $um;
    }
}

?>
