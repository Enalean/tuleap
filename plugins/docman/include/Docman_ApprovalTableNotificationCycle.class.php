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
 */

require_once('common/mail/Mail.class.php');
require_once('Docman_ApprovalTableDao.class.php');

class Docman_ApprovalTableNotificationCycle {
    var $table;
    var $owner;
    var $item;

    function Docman_ApprovalTableNotificationCycle() {
        $this->table = null;
        $this->owner = null;
        $this->item = null;
    }

    function reviewUpdated($review) {
        // Parameters
        $withComments = false;
        if(trim($review->getComment()) != "") {
            $withComments = true;
        }

        $reviewer =& $this->_getUserById($review->getId());

        // States
        if($review->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED) {
            $this->reviewerReject($reviewer);
        }
        elseif($this->getTableState() == PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED) {
            $isLastReviewer = true;
            $this->reviewerApprove($reviewer, $isLastReviewer, $withComments);
        }
        else {
            $isLastReviewer = false;
            switch($review->getState()) {
            case PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED:
                $this->reviewerApprove($reviewer, $isLastReviewer, $withComments);

                break;
                case PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED:
                $this->reviewerDecline($reviewer, $isLastReviewer);

                break;
            case PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED:
                $this->reviewerComment($reviewer);
                break;
            }
        }
    }

    //
    // Actions
    //

    /**
     * Action
     */
    function reviewerApprove($reviewer, $isLastReviewer, $withComments) {
        if($isLastReviewer) {
            $mail = $this->getNotifTableApproved($reviewer, $withComments);
        } else {
            $mail = $this->getNotifReviewApproved($reviewer, $withComments);
        }
        $mail->send();

        if(!$isLastReviewer &&
           $this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $this->notifyNextReviewer();
        }
    }

    /**
     * Action
     */
    function reviewerReject($reviewer) {
        $mail = $this->getNotifRejected($reviewer);
        $mail->send();
    }

    /**
     * Action
     */
    function reviewerDecline($reviewer, $isLastReviewer) {
        $mail = $this->getNotifReviewDeclined($reviewer);
        $mail->send();
        if(!$isLastReviewer &&
           $this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $this->notifyNextReviewer();
        }
    }

    /**
     * Action
     */
    function reviewerComment($reviewer) {
        $mail = $this->getNotifReviewCommented($reviewer);
        $mail->send();
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

        $rIter = $this->table->getReviewerIterator();
        if($rIter !== null) {
            $rIter->rewind();
            while($rIter->valid()) {
                $reviewer = $rIter->current();
                switch($reviewer->getState()) {
                case PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET:
                case PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED:
                    $sent = $this->notifyIndividual($reviewer->getId());
                    if($sent) {
                        $nbNotif++;
                    }
                }
                $rIter->next();
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
     * Action - Sequential notification
     *
     * Send a mail to the first reviewer that didn't already commit herself
     * (review = not yet). If someone reject the document, CodeX doesn't send
     * any emails.
     */
    function notifyNextReviewer() {
        $dao =& $this->_getApprovalTableDao();

        $dar = $dao->getFirstReviewerByStatus($this->item->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        if($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return false;
        } else {
            $dar = $dao->getFirstReviewerByStatus($this->item->getId(), array(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
                                                                              PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED));
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                return $this->notifyIndividual($row['reviewer_id']);
            }
        }
        return false;
    }

    /**
     * Action
     */
    function notifyIndividual($reviewerId) {
        $um =& $this->_getUserManager();
        $reviewer =& $um->getUserById($reviewerId);

        $mail = $this->getNotifReviewer($reviewer);
        return $mail->send();
    }

    //
    //
    //

    function getReviewUrl() {
        $baseUrl = get_server_url().'/plugins/docman/?group_id='.$this->item->getGroupId();
        $reviewUrl = $baseUrl .'&action=details&section=approval&id='.$this->item->getId();
        return $reviewUrl;
    }

    function &_getEmailToOwner() {
        $mail =& $this->_getMail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo($this->owner->getEmail());
        return $mail;
    }

    /**
     * Notify table owner
     */
    function getNotifRejected($reviewer) {
        $reviewUrl = $this->getReviewUrl();

        $mail =& $this->_getEmailToOwner();
        $mail->setSubject(Docman::txt('approval_notif_reject_mail_subject', array($GLOBALS['sys_name'],
                                                                                  $this->item->getTitle())));
        $mail->setBody(Docman::txt('approval_notif_reject_mail_body', array($this->item->getTitle(),
                                                                            $reviewUrl,
                                                                            $reviewer->getRealName(),
                                                                            $reviewer->getEmail())));
        return $mail;
    }

    /**
     * Notify table owner
     */
    function getNotifReviewApproved($reviewer, $withComments) {
        $reviewUrl = $this->getReviewUrl();

        $comment = '';
        if($withComments) {
            $comment = Docman::txt('approval_notif_approve_user_mail_com');
        }

        $mail =& $this->_getEmailToOwner();
        $mail->setSubject(Docman::txt('approval_notif_approve_user_mail_subject', array($GLOBALS['sys_name'],
                                                                                        $this->item->getTitle(),
                                                                                        $comment)));
        $mail->setBody(Docman::txt('approval_notif_approve_user_mail_body', array($this->item->getTitle(),
                                                                                  $reviewUrl,
                                                                                  $reviewer->getRealName(),
                                                                                  $reviewer->getEmail())));
        return $mail;
    }

    /**
     * Notify table owner
     */
    function getNotifTableApproved($reviewer, $withComments) {
        $reviewUrl = $this->getReviewUrl();
        $baseUrl = get_server_url().'/plugins/docman/?group_id='.$this->item->getGroupId();
        $propUrl = $baseUrl .'&action=edit&id='.$this->item->getId();

        $comment = '';
        if($withComments) {
            $comment = Docman::txt('approval_notif_approve_user_mail_com');
        }

        $mail =& $this->_getEmailToOwner();
        $mail->setSubject(Docman::txt('approval_notif_approve_mail_subject', array($GLOBALS['sys_name'],
                                                                                   $this->item->getTitle(),
                                                                                   $comment)));
        $mail->setBody(Docman::txt('approval_notif_approve_mail_body', array($this->item->getTitle(),
                                                                             $reviewUrl,
                                                                             $reviewer->getRealName(),
                                                                             $reviewer->getEmail(),
                                                                             $propUrl)));
        return $mail;
    }

    /**
     * Notify table owner
     */
    function getNotifReviewDeclined($reviewer) {
        $reviewUrl = $this->getReviewUrl();

        $mail =& $this->_getEmailToOwner();
        $mail->setSubject(Docman::txt('approval_notif_declined_mail_subject', array($GLOBALS['sys_name'],
                                                                                    $this->item->getTitle())));
        $mail->setBody(Docman::txt('approval_notif_declined_mail_body', array($this->item->getTitle(),
                                                                              $reviewUrl,
                                                                              $reviewer->getRealName(),
                                                                              $reviewer->getEmail())));
        return $mail;
    }

    /**
     * Notify table owner
     */
    function getNotifReviewCommented($reviewer) {
        $reviewUrl = $this->getReviewUrl();

        $commentSeq = '';
        if($this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $commentSeq = Docman::txt('approval_notif_comment_mail_seq', $reviewer->getRealName());
            $commentSeq .= "\n";
        }

        $mail =& $this->_getEmailToOwner();
        $mail->setSubject(Docman::txt('approval_notif_comment_mail_subject', array($GLOBALS['sys_name'],
                                                                                   $this->item->getTitle())));
        $mail->setBody(Docman::txt('approval_notif_comment_mail_body', array($this->item->getTitle(),
                                                                             $reviewer->getRealName(),
                                                                             $reviewer->getEmail(),
                                                                             $reviewUrl,
                                                                             $commentSeq)));
        return $mail;
    }

    function getNotifReviewer($reviewer) {
        // Project
        $group = group_get_object($this->item->getGroupId());

        // Url
        $baseUrl = get_server_url().'/plugins/docman/?group_id='.$this->item->getGroupId();
        $itemUrl   = $baseUrl .'&action=show&id='.$this->item->getId();
        $reviewUrl = $this->getReviewUrl().'&user_id='.$reviewer->getId();

        // Notification style
        $notifStyle = '';
        switch($this->table->getNotification()) {
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
            $notifStyle = Docman::txt('approval_notif_mail_notif_seq', array($GLOBALS['sys_name']));
            break;
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
            $notifStyle = Docman::txt('approval_notif_mail_notif_all');
            break;
        }

        // Comment
        $comment = '';
        $userComment = $this->table->getDescription();
        if($userComment != '') {
            $comment = Docman::txt('approval_notif_mail_notif_owner_comment', array($userComment));
            $comment .= "\n\n";
        }

        $subj = Docman::txt('approval_notif_mail_subject', array($GLOBALS['sys_name'], $this->item->getTitle()));
        $body = Docman::txt('approval_notif_mail_body', array($this->item->getTitle(), 
                                                              $group->getPublicName(),
                                                              $this->owner->getRealName(),
                                                              $itemUrl,
                                                              $comment,
                                                              $notifStyle,
                                                              $reviewUrl,
                                                              $this->owner->getEmail()));

        $mail =& $this->_getMail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo($reviewer->getEmail());
        $mail->setSubject($subj);
        $mail->setBody($body);

        return $mail;
    }

    //
    //
    //

    /**
     * Return current item approval table state
     */
    function getTableState() {
        $nbApproved = 0;
        $nbDeclined = 0;
        $rejected = false;
        $dao =& $this->_getApprovalTableDao();
        $dar = $dao->getReviewerList($this->item->getId());
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
            case PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED:
                $nbDeclined++;
                break;
            }
            $dar->next();
        }
        if($rejected) {
            return PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED;
        }
        if(($nbApproved + $nbDeclined) == $dar->rowCount()) {
            return PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED;
        }
        return PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET;
    }

    //
    // Getters & setters
    //

    function setTable($table) {
        $this->table =& $table;
    }

    function getTable() {
        return $this->table;
    }

    function setOwner($owner) {
        $this->owner =& $owner;
    }

    function setItem($item){
        $this->item =& $item;
    }

    //
    // Class accessor
    //

    function &_getApprovalTableDao() {
        $dao = new Docman_ApprovalTableDao(CodexDataAccess::instance());
        return $dao;
    }

    function &_getMail() {
        $mail = new Mail();
        //require_once('common/mail/TestMail.class.php');
        //$mail = new TestMail();
        //$mail->_testDir = '/local/vm16/codev/crx1348-sttrunk/var/spool/mail';
        return $mail;
    }

    function &_getUserManager() {
        $um =& UserManager::instance();
        return $um;
    }

    function &_getUserById($id) {
        $um =& UserManager::instance();
        $user =& $um->getUserById($id);
        return $user;
    }

}
?>
