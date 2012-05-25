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

/**
 * Remind users that didn't review documents yet
 */
class Docman_ApprovalTableReminder {

    /**
     * Remind approval table approvers
     *
     * @return Void
     */
    function remindApprovers() {
        $dao = new Docman_ApprovalTableDao();
        $dar = $dao->getTablesForReminder();
        $tables = array();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $table = new Docman_ApprovalTable();
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
     function sendNotificationToPendingApprovers($table) {
        if($table->isEnabled() && $table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
            switch ($table->getNotification()) {
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
                $this->notifyAllAtOnce($table);
                break;
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
                $this->notifyNextReviewer($table);
                break;
            default:
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
    function notifyAllAtOnce($table) {
        $nbNotif = 0;
        $this->populateReviewersList($table);
        $rIter   = $table->getReviewerIterator();
        if($rIter !== null) {
            $rIter->rewind();
            while($rIter->valid()) {
                $reviewer = $rIter->current();
                switch($reviewer->getState()) {
                case PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET:
                case PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED:
                    $sent = $this->notifyIndividual($table, $reviewer->getId());
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
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Boolean
     */
    function notifyNextReviewer($table) {
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
     * @param Docman_ApprovalTable $table Approval table
     * @param Integer              $reviewerId     Id of the reviewer
     *
     * @return Boolean
     */
    function notifyIndividual($table, $reviewerId) {
        $um       = UserManager::instance();
        $reviewer = $um->getUserById($reviewerId);
        $mail     = $this->prepareMailReminder($table, $reviewer);
        //return $mail->send();
    }

    /**
     * Prepare the mail reminder
     *
     * @param Docman_ApprovalTable $table    Approval table
     * @param User                 $reviewer User to remind
     *
     * @return Mail
     */
     function prepareMailReminder($table, $reviewer) {
         // @TODO: Prepare the mail taking into consideration user preferences text/HTML and the language of her choice
     }

    /**
     * Populate reviewers list of an approval table
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Void
     */
     function populateReviewersList($table) {
        $dao = new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
        $dar = $dao->getReviewerList($table->getId());
        foreach ($dar as $row) {
            $reviewer = new Docman_ApprovalReviewer();
            $reviewer->initFromRow($row);
            $table->addReviewer($reviewer);
            unset($reviewer);
        }
     }

}

?>