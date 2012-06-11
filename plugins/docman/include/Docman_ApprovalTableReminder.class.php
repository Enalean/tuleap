<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once('common/date/DateHelper.class.php');
require_once('Docman_ApprovalTableDao.class.php');
require_once('Docman_ApprovalTableReviewerDao.class.php');
require_once('Docman_ApprovalTable.class.php');
require_once('Docman_ApprovalTableReviewer.class.php');
require_once('common/mail/MailManager.class.php');
require_once('Docman_ItemFactory.class.php');

/**
 * Remind users that didn't review documents yet
 */
class Docman_ApprovalTableReminder {

    /**
     * Remind approval table approvers
     *
     * @return Void
     */
    public function remindApprovers() {
        $dao = new Docman_ApprovalTableDao();
        $dar = $dao->getTablesForReminder();
        $tables = array();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $table = new Docman_ApprovalTableItem();
                $table->initFromRow($row);
                $distance = DateHelper::dateDiffInDays($table->getDate(), $_SERVER['REQUEST_TIME']);
                if ($distance > 0 && DateHelper::isPeriodicallyDistant($distance, $table->getNotificationOccurence())) {
                    $this->sendNotificationToPendingApprovers($table);
                }
            }
        }
    }

    /**
     * Send notification to pending approvers of the given table
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Void
     */
    private function sendNotificationToPendingApprovers(Docman_ApprovalTable $table) {
        if($table->isEnabled()) {
            switch ($table->getNotification()) {
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
                $this->notifyAllAtOnce($table);
                break;
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
                $this->notifyNextReviewer($table);
                break;
            default:
                break;
            }
        }
    }

    /**
     * Notify everybody in the same time
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Boolean
     */
    private function notifyAllAtOnce(Docman_ApprovalTable $table) {
        $nbNotif = 0;
        $this->populateReviewersList($table);
        $reviewers   = $table->getReviewerArray();
        foreach ($reviewers as $reviewer) {
            if ($reviewer->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET || $reviewer->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED) {
                $sent = $this->notifyIndividual($table, $reviewer->getId());
                if($sent) {
                    $nbNotif++;
                }
            }
        }

        return ($nbNotif > 0);
    }

    /**
     * Action - Sequential notification
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Boolean
     */
    private function notifyNextReviewer(Docman_ApprovalTable $table) {
        $dao = new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
        $dar = $dao->getFirstReviewerByStatus($table->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        if($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return false;
        } else {
            $dar = $dao->getFirstReviewerByStatus($table->getId(), array(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED));
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                return $this->notifyIndividual($table, $row['reviewer_id']);
            }
        }
        return false;
    }

    /**
     * Remind a user about the document he is supposed to review
     *
     * @param Docman_ApprovalTable $table      Approval table
     * @param Integer              $reviewerId Id of the reviewer
     *
     * @return Boolean
     */
    private function notifyIndividual(Docman_ApprovalTable $table, int $reviewerId) {
        $um       = UserManager::instance();
        $reviewer = $um->getUserById($reviewerId);
        $mail     = $this->prepareMailReminder($table, $reviewer);
        return $this->sendMailReminder($mail, array($reviewer->getEmail()));
    }

    /**
     * Prepare the mail reminder
     *
     * @param Docman_ApprovalTable $table    Approval table
     * @param User                 $reviewer User to remind
     *
     * @return Mail
     */
    private function prepareMailReminder(Docman_ApprovalTable $table, User $reviewer) {
        $itemFactory = new Docman_ItemFactory();
        $docmanItem  = $itemFactory->getItemFromDb($table->itemId);
        $subject     = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_mail_subject', array($GLOBALS['sys_name'], $docmanItem->getTitle()));

        $mailMgr   = new MailManager();
        $mailPrefs = $mailMgr->getMailPreferencesByUser($reviewer);
        switch ($mailPrefs) {
            case Codendi_Mail_Interface::FORMAT_HTML :
                $mail = $this->createHTMLMailForReviewer($table, $docmanItem, $subject);
                break;
            default :
                $mail = $this->createMailForReviewer($table, $docmanItem, $subject);
                break;
        }
        return $mail;
    }

    /**
     * Retrieve approval table url for a given docmna item
     *
     * @param Docman_Item          $docmanItem Item to be approved
     *
     * @return String
     */
    private function createMailForReviewer(Docman_Item $docmanItem) {
        $baseUrl   = get_server_url().'/plugins/docman/?group_id='.$docmanItem->getGroupId();
        $reviewUrl = $baseUrl.'&action=details&section=approval&id='.$docmanItem->getId().'&review=1';
        return $reviewUrl;
    }

    /**
     * Retrieve url to access a given docman item
     *
     * @param Docman_Item $table The approval table that its reminder notification will be sent
     *
     * @return String
     */
    private function getItemUrl($docmanItem) {
        $baseUrl   = get_server_url().'/plugins/docman/?group_id='.$docmanItem->getGroupId();
        $itemUrl   = $baseUrl.'&action=show&id='.$docmanItem->getId();
        return $itemUrl;
    }

    /**
     * Retrieve notification mail type formmatted as a message within the reminder
     *
     * @param ApprovalTable $table The approval The approval table that its reminder notification will be sent
     *
     * @return User
     */
    private function getNotificationStyle($table) {
        $notifStyle = '';
        switch($table->getNotification()) {
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
            $notifStyle = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_seq', array($GLOBALS['sys_name']));
            break;
        case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
            $notifStyle = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_all');
            break;
        }
        return $notifStyle;
    }

    /**
     * Retrieve approval table descritpion formatted as a message within the reminder
     *
     * @param ApprovalTable $table  The approval table that its reminder notification will be sent
     * @param String        $format Message format
     *
     * @return User
     */
    private function getTableDescriptionAsMessage($table, $format) {
        $comment     = '';
        $userComment = $table->getDescription();
        if($userComment != '') {
            switch ($format) {
                case Codendi_Mail_Interface::FORMAT_HTML :
                    $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_mail_notif_owner_comment', array($userComment));
                    break;
                case Codendi_Mail_Interface::FORMAT_TEXT :
                    $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_owner_comment', array($userComment));
                    $comment .= "\n\n";
                    break;
                default :
                    $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_mail_notif_owner_comment', array($userComment));
                    break;
            }
        }
        return $comment;
    }

    /**
     * Retrieve the owner of a given approval table
     *
     * @param ApprovalTable $table The approval table we want to get its owner
     *
     * @return User
     */
    private function getApprovalTableOwner($table) {
        $um    = UserManager::instance();
        return $um->getUserById($table->owner);
    }

    /**
     * Retrieve project for a given docman item
     *
     * @param Docman_Item $docmanItem The docman item we want to get its project
     *
     * @return Project
     */
    private function getItemProject($docmanItem) {
        $pm    = ProjectManager::instance();
        return $pm->getProject($docmanItem->getGroupId());
    }

    /**
     * Creates the text mail body
     *
     * @param User                 $reviewer User to remind
     * @param Docman_ApprovalTable $table    Approval table
     * @param String               $subject
     *
     * @return Mail
     */
    private function createMailForReviewer($table, $docmanItem, $subject) {
        $group = $this->getItemProject($docmanItem);
        $owner = $this->getApprovalTableOwner($table);

        $body = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_body', array($docmanItem->getTitle(), 
                                                              $group->getPublicName(),
                                                              $owner->getRealName(),
                                                              $this->getItemUrl($docmanItem),
                                                              $this->getTableDescriptionAsMessage($table, Codendi_Mail_Interface::FORMAT_TEXT),
                                                              $this->getNotificationStyle($table),
                                                              $this->getReviewUrl($docmanItem),
                                                              $owner->getEmail()));

        $mail = new Mail();
        $mail->setSubject($subject);
        $mail->setBody($body);
        return $mail;
    }

    /**
     * Creates the html mail body
     *
     * @param Docman_ApprovalTable $table      Approval table
     * @param Docman_Item          $docmanItem Item to be approved
     * @param String               $subject    Dubject of the mail
     *
     * @return Codendi_Mail
     */
    private function createHTMLMailForReviewer(Docman_ApprovalTable $table, Docman_Item $docmanItem, String $subject) {
        $group = $this->getItemProject($docmanItem);
        $owner = $this->getApprovalTableOwner($table);

        $body = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body', array($docmanItem->getTitle(), 
                                                              $group->getPublicName(),
                                                              $owner->getRealName(),
                                                              $this->getItemUrl($docmanItem),
                                                              $this->getTableDescriptionAsMessage($table, Codendi_Mail_Interface::FORMAT_HTML),
                                                              $this->getNotificationStyle($table),
                                                              $this->getReviewUrl($docmanItem),
                                                              $owner->getEmail()));

        $mail = new Codendi_Mail();
        $mail->setSubject($subject);
        $mail->setBodyHtml($body);
        return $mail;
    }

    /**
     * Send mail reminder to reviewers
     *
     * @param Codendi_Mail_Interface $mail
     * @param Array                  $to
     */
    private function sendMailReminder(Codendi_Mail_Interface $mail, Array $to) {
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo(join(',', $to));
        return $mail->send();
    }

    /**
     * Populate reviewers list of an approval table
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Void
     */
    private function populateReviewersList(Docman_ApprovalTable $table) {
        $dao = new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
        $dar = $dao->getReviewerList($table->getId());
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $reviewer = new Docman_ApprovalReviewer();
                $reviewer->initFromRow($row);
                $table->addReviewer($reviewer);
                unset($reviewer);
            }
        }
     }

}

?>