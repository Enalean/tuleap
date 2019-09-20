<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

class Docman_ApprovalTableNotificationCycle
{
    var $table;
    var $owner;
    var $item;

    /** @var MailNotificationBuilder */
    private $mail_notification_builder;

    /** @var Docman_NotificationsManager|null */
    private $notificationManager = null;

    public function __construct(MailNotificationBuilder $mail_builder)
    {
        $this->table                     = null;
        $this->owner                     = null;
        $this->item                      = null;
        $this->mail_notification_builder = $mail_builder;
    }

    function reviewUpdated($review)
    {
        // Parameters
        $withComments = false;
        if (trim($review->getComment()) != "") {
            $withComments = true;
        }

        $reviewer = $this->_getUserById($review->getId());

        // States
        if ($review->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED) {
            $this->reviewerReject($reviewer);
        } elseif ($this->getTableState() == PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED) {
            $isLastReviewer = true;
            $this->reviewerApprove($reviewer, $isLastReviewer, $withComments);
        } else {
            $isLastReviewer = false;
            switch ($review->getState()) {
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

    // Actions
    /**
     * Action
     */
    function reviewerApprove($reviewer, $isLastReviewer, $withComments)
    {
        if ($isLastReviewer) {
            $this->sendNotifTableApproved($reviewer, $withComments);
            $this->changeItemStatus($reviewer, PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        } else {
            $this->sendNotifReviewApproved($reviewer, $withComments);
        }

        if (!$isLastReviewer &&
           $this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $this->notifyNextReviewer();
        }
    }

    /**
     * Action
     */
    function reviewerReject($reviewer)
    {
        $this->sendNotifRejected($reviewer);
        $this->changeItemStatus($reviewer, PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);
    }

    /**
     * Action
     */
    function reviewerDecline($reviewer, $isLastReviewer)
    {
        $this->sendNotifReviewDeclined($reviewer);
        if (!$isLastReviewer &&
           $this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $this->notifyNextReviewer();
        }
    }

    /**
     * Action
     */
    function reviewerComment($reviewer)
    {
        $this->sendNotifReviewCommented($reviewer);
        $this->changeItemStatus($reviewer, PLUGIN_DOCMAN_ITEM_STATUS_DRAFT);
    }

    /**
     * Notify everybody in the same time
     *
     * @return bool Will return false only if there is no table or no
 * reviewers to notify. If one notification fail, I don't have the tools to
 * report it to the user.
     */
    function notifyAllAtOnce()
    {
        $nbNotif = 0;

        $rIter = $this->table->getReviewerIterator();
        if ($rIter !== null) {
            $rIter->rewind();
            while ($rIter->valid()) {
                $reviewer = $rIter->current();
                switch ($reviewer->getState()) {
                    case PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET:
                    case PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED:
                        $sent = $this->notifyIndividual($reviewer->getId());
                        if ($sent) {
                            $nbNotif++;
                        }
                }
                $rIter->next();
            }
        } else {
            return false;
        }

        if ($nbNotif > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Action - Sequential notification
     *
     * Send a mail to the first reviewer that didn't already commit herself
     * (review = not yet). If someone reject the document, Codendi doesn't send
     * any emails.
     */
    function notifyNextReviewer()
    {
        $dao = $this->_getReviewerDao();

        $dar = $dao->getFirstReviewerByStatus($this->table->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return false;
        } else {
            $dar = $dao->getFirstReviewerByStatus($this->table->getId(), array(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
                                                                              PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED));
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                return $this->notifyIndividual($row['reviewer_id']);
            }
        }
        return false;
    }

    /**
     * Action
     */
    function notifyIndividual($reviewerId)
    {
        // enable item monitoring
        $this->enableMonitorForReviewer($reviewerId);

        $um = $this->_getUserManager();
        $reviewer = $um->getUserById($reviewerId);

        return $this->sendNotifReviewer($reviewer);
    }

    /**
     * Enable the monitoring of an item for a given reviewer
     */
    private function enableMonitorForReviewer($reviewerId)
    {
        if (($this->notificationManager !== null) && !$this->notificationManager->userExists($reviewerId, $this->item->getId())) {
            $this->notificationManager->add($reviewerId, $this->item->getId());
        }
    }

    /**
     * Update item status according to parameters.
     * Not in use today.
     */
    function changeItemStatus($reviewer, $status)
    {
       // TBD
    }

    function getReviewUrl()
    {
        $baseUrl = HTTPRequest::instance()->getServerUrl().'/plugins/docman/?group_id='.$this->item->getGroupId();
        $reviewUrl = $baseUrl .'&action=details&section=approval&id='.$this->item->getId();
        return $reviewUrl;
    }

    function _getEmailToOwner()
    {
        $mail = $this->_getMail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo($this->owner->getEmail());
        return $mail;
    }

    /**
     * Notify table owner
     */
    function sendNotifRejected($reviewer)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $reviewUrl       = $this->getReviewUrl();

        $subject = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_reject_mail_subject',
            array(
                $GLOBALS['sys_name'],
                $this->item->getTitle()
            )
        );

        $body = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_reject_mail_body',
            array(
                $this->item->getTitle(),
                $reviewUrl,
                $reviewer->getRealName(),
                $reviewer->getEmail()
            )
        );

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            array($reviewer->getEmail()),
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Notify table owner
     */
    function sendNotifReviewApproved($reviewer, $withComments)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());

        $reviewUrl = $this->getReviewUrl();
        $body      = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_approve_user_mail_body',
            array($this->item->getTitle(),
                $reviewUrl,
                $reviewer->getRealName(),
                $reviewer->getEmail()
            )
        );

        $comment = '';
        if ($withComments) {
            $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_approve_user_mail_com');
        }

        $subject = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_approve_user_mail_subject',
            array(
                $GLOBALS['sys_name'],
                $this->item->getTitle(),
                $comment
            )
        );

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            array($this->owner->getEmail()),
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Notify table owner
     */
    function sendNotifTableApproved($reviewer, $withComments)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());

        $reviewUrl = $this->getReviewUrl();
        $baseUrl   = HTTPRequest::instance()->getServerUrl().'/plugins/docman/?group_id='.$this->item->getGroupId();
        $propUrl   = $baseUrl .'&action=edit&id='.$this->item->getId();
        $body      = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_approve_mail_body',
            array(
                $this->item->getTitle(),
                $reviewUrl,
                $reviewer->getRealName(),
                $reviewer->getEmail(),
                $propUrl
            )
        );

        $comment = '';
        if ($withComments) {
            $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_approve_user_mail_com');
        }

        $subject = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_approve_mail_subject',
            array(
                $GLOBALS['sys_name'],
                $this->item->getTitle(),
                $comment
            )
        );

        return $this->mail_notification_builder->buildAndSendEmail(
            $project,
            array($this->owner->getEmail()),
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Notify table owner
     */
    function sendNotifReviewDeclined($reviewer)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $reviewUrl       = $this->getReviewUrl();

        $subject = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_declined_mail_subject',
            array(
                $GLOBALS['sys_name'],
                $this->item->getTitle()
            )
        );

        $body = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_declined_mail_body',
            array(
                $this->item->getTitle(),
                $reviewUrl,
                $reviewer->getRealName(),
                $reviewer->getEmail()
            )
        );

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            array($this->owner->getEmail()),
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Notify table owner
     */
    function sendNotifReviewCommented($reviewer)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $reviewUrl       = $this->getReviewUrl();

        $commentSeq = '';
        if ($this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $commentSeq = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_comment_mail_seq', $reviewer->getRealName());
            $commentSeq .= "\n";
        }

        $subject = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_comment_mail_subject',
            array(
                $GLOBALS['sys_name'],
                $this->item->getTitle()
            )
        );

        $body = $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_comment_mail_body',
            array(
                $this->item->getTitle(),
                $reviewer->getRealName(),
                $reviewer->getEmail(),
                $reviewUrl,
                $commentSeq
            )
        );

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            array($reviewer->getEmail()),
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    function sendNotifReviewer($reviewer)
    {
        // Project
        $pm = ProjectManager::instance();
        $group = $pm->getProject($this->item->getGroupId());

        // Url
        $reviewUrl = $this->getReviewUrl().'&review=1';

        $subject = $this->getNotificationSubject();
        $body    = $this->getNotificationBodyText();

        return $this->mail_notification_builder->buildAndSendEmail(
            $group,
            array($reviewer->getEmail()),
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Return current item approval table state
     */
    function getTableState()
    {
        $nbApproved = 0;
        $nbDeclined = 0;
        $rejected = false;
        $revIterator = $this->table->getReviewerIterator();
        while (!$rejected && $revIterator->valid()) {
            $reviewer = $revIterator->current();
            switch ($reviewer->getState()) {
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
            $revIterator->next();
        }
        if ($rejected) {
            return PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED;
        }
        if (($nbApproved + $nbDeclined) == $revIterator->count()) {
            return PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED;
        }
        return PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET;
    }

    // Getters & setters
    function setTable(&$table)
    {
        $this->table = $table;
    }

    function getTable()
    {
        return $this->table;
    }

    function setOwner(&$owner)
    {
        $this->owner = $owner;
    }

    function setItem($item)
    {
        $this->item = $item;
    }

    // Class accessor
    function _getReviewerDao()
    {
        return new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
    }

    function _getMail()
    {
        return new Codendi_Mail();
    }

    function _getUserManager()
    {
        return UserManager::instance();
    }

    function _getUserById($id)
    {
        return UserManager::instance()->getUserById($id);
    }

    function _getItemFactory()
    {
        return new Docman_ItemFactory($this->item->getGroupId());
    }

    function _getEventManager()
    {
        return EventManager::instance();
    }

    function _getSettingsBo($groupId)
    {
        return Docman_SettingsBo::instance($groupId);
    }

    function setNotificationManager($notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    function getNotificationSubject()
    {
        return $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_subject', array($GLOBALS['sys_name'], $this->item->getTitle()));
    }

    function getNotificationBodyText()
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $baseUrl         = HTTPRequest::instance()->getServerUrl().'/plugins/docman/?group_id='.$this->item->getGroupId();
        $itemUrl         = $baseUrl .'&action=show&id='.$this->item->getId();
        $comment         = '';
        $userComment     = $this->table->getDescription();

        if ($userComment != '') {
            $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_owner_comment', array($userComment));
            $comment .= "\n\n";
        }

        $reviewUrl = $this->getReviewUrl().'&review=1';

        // Notification style
        $notifStyle = '';
        switch ($this->table->getNotification()) {
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
                $notifStyle = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_seq', array($GLOBALS['sys_name']));
                break;
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
                $notifStyle = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_all');
                break;
        }

        return $GLOBALS['Language']->getText(
            'plugin_docman',
            'approval_notif_mail_body',
            array(
                $this->item->getTitle(),
                $project->getPublicName(),
                $this->owner->getRealName(),
                $itemUrl,
                $comment,
                $notifStyle,
                $reviewUrl,
                $this->owner->getEmail()
            )
        );
    }
}
