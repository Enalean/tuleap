<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

/**
 * Remind users that didn't review documents yet
 */
class Docman_ApprovalTableReminder
{

    /**
     * Remind approval table approvers
     *
     * @return Void
     */
    public function remindApprovers()
    {
        $dao = new Docman_ApprovalTableItemDao();
        $dar = $dao->getTablesForReminder();
        $tables = array();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                if ($row['item_id']) {
                    $table = new Docman_ApprovalTableItem();
                } elseif ($row['link_version_id']) {
                    $table = new Docman_ApprovalTableLink();
                } else {
                    $table = new Docman_ApprovalTableFile();
                }
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
    private function sendNotificationToPendingApprovers(Docman_ApprovalTable $table)
    {
        if ($table->isEnabled()) {
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
     * @return bool
     */
    private function notifyAllAtOnce(Docman_ApprovalTable $table)
    {
        $nbNotif = 0;
        $this->populateReviewersList($table);
        $reviewers   = $table->getReviewerArray();
        foreach ($reviewers as $reviewer) {
            if ($reviewer->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET || $reviewer->getState() == PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED) {
                $sent = $this->notifyIndividual($table, $reviewer->getId());
                if ($sent) {
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
     * @return bool
     */
    private function notifyNextReviewer(Docman_ApprovalTable $table)
    {
        $dao = new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
        $dar = $dao->getFirstReviewerByStatus($table->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return false;
        } else {
            $dar = $dao->getFirstReviewerByStatus($table->getId(), array(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED));
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
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
     * @param int $reviewerId Id of the reviewer
     *
     * @return bool
     */
    private function notifyIndividual(Docman_ApprovalTable $table, $reviewerId)
    {
        $hp       = Codendi_HTMLPurifier::instance();
        $um       = UserManager::instance();
        $reviewer = $um->getUserById($reviewerId);

        if ($reviewer === null) {
            return false;
        }

        if (! $reviewer->getEmail()) {
            return false;
        }

        $itemId = '';
        if ($table instanceof Docman_ApprovalTableFile) {
            $versionFactory = new Docman_VersionFactory();
            $version        = $versionFactory->getSpecificVersionById($table->getVersionId(), 'plugin_docman_version');
            if ($version) {
                $itemId = $version->getItemId();
            }
        } elseif ($table && method_exists($table, 'getItemId')) {
            $itemId = $table->getItemId();
        }
        if (! $itemId) {
            return false;
        }
        $itemFactory = new Docman_ItemFactory();
        $docmanItem  = $itemFactory->getItemFromDb($itemId);
        if (! $docmanItem) {
            return false;
        }

        $subject     = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_mail_subject', array($GLOBALS['sys_name'], $docmanItem->getTitle()));

        $mailMgr   = new MailManager();
        $mailPrefs = $mailMgr->getMailPreferencesByUser($reviewer);

        $html_body = '';
        if ($mailPrefs == Codendi_Mail_Interface::FORMAT_HTML) {
                $html_body = $this->getBodyHtml($table, $docmanItem);
        }
        $text_body = $this->getBodyText($table, $docmanItem);

        $mail_notification_builder = new MailNotificationBuilder(
            new MailBuilder(
                TemplateRendererFactory::build(),
                new MailFilter(
                    UserManager::instance(),
                    new ProjectAccessChecker(
                        PermissionsOverrider_PermissionsOverriderManager::instance(),
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    new MailLogger()
                )
            )
        );

        return $mail_notification_builder->buildAndSendEmail(
            $this->getItemProject($docmanItem),
            array($reviewer->getEmail()),
            $subject,
            $html_body,
            $text_body,
            $this->getReviewUrl($docmanItem),
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Retrieve approval table url for a given docman item
     *
     * @param Docman_Item $docmanItem Item to be approved
     *
     * @return String
     */
    private function getReviewUrl(Docman_Item $docmanItem)
    {
        $baseUrl   = HTTPRequest::instance()->getServerUrl().'/plugins/docman/?group_id='.$docmanItem->getGroupId();
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
    private function getItemUrl(Docman_Item $docmanItem)
    {
        $baseUrl   = HTTPRequest::instance()->getServerUrl().'/plugins/docman/?group_id='.$docmanItem->getGroupId();
        $itemUrl   = $baseUrl.'&action=show&id='.$docmanItem->getId();
        return $itemUrl;
    }

    /**
     * Retrieve notification mail type formmatted as a message within the reminder
     *
     * @param Docman_ApprovalTable $table The approval The approval table that its reminder notification will be sent
     *
     * @return PFUser
     */
    private function getNotificationStyle(Docman_ApprovalTable $table)
    {
        $notifStyle = '';
        switch ($table->getNotification()) {
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
     * @param Docman_ApprovalTable $table  The approval table that its reminder notification will be sent
     * @param String               $format Message format
     *
     * @return PFUser
     */
    private function getTableDescriptionAsMessage(Docman_ApprovalTable $table, $format)
    {
        $comment     = '';
        $userComment = $table->getDescription();
        if ($userComment != '') {
            switch ($format) {
                case Codendi_Mail_Interface::FORMAT_HTML:
                    $comment  = '<b>'.$GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_mail_notif_owner_comment').'</b><br>';
                    $comment .= '<hr align="center" width="50%" color="midnightblue" size="3"><br>'.$userComment.'<br><hr align="center" width="50%" color="midnightblue" size="3"><br><br>';
                    $comment .= '<br>';
                    break;
                case Codendi_Mail_Interface::FORMAT_TEXT:
                    $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_notif_mail_notif_owner_comment', array($userComment));
                    $comment .= "\n\n";
                    break;
                default:
                    $comment = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_mail_notif_owner_comment', array($userComment));
                    break;
            }
        }
        return $comment;
    }

    /**
     * Retrieve the owner of a given approval table
     *
     * @param Docman_ApprovalTable $table The approval table we want to get its owner
     *
     * @return PFUser
     */
    private function getApprovalTableOwner(Docman_ApprovalTable $table)
    {
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
    private function getItemProject(Docman_Item $docmanItem)
    {
        $pm    = ProjectManager::instance();
        return $pm->getProject($docmanItem->getGroupId());
    }

    /**
     * Creates the text mail body
     *
     * @param Docman_ApprovalTable $table      Approval table
     * @param Docman_Item          $docmanItem The docman item to be reviewed
     *
     * @return String
     */
    private function getBodyText(Docman_ApprovalTable $table, Docman_Item $docmanItem)
    {
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

        return $body;
    }

    /**
     * Creates the html mail body
     *
     * @param Docman_ApprovalTable $table      Approval table
     * @param Docman_Item          $docmanItem Item to be approved
     *
     * @return String
     */
    private function getBodyHtml(Docman_ApprovalTable $table, Docman_Item $docmanItem)
    {
        $group = $this->getItemProject($docmanItem);
        $owner = $this->getApprovalTableOwner($table);
        $body  = $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_header').'<br><br><b>';
        $body .= $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_title').'</b> '.$docmanItem->getTitle().'<br><b>';
        $body .= $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_group_name').'</b> '.$group->getPublicName().'<br><b>';
        $body .= $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_owner_name').'</b><a href="'.$owner->getEmail().'">'.$owner->getRealName().'</a><br>';
        $body .= '<a href="'.$this->getItemUrl($docmanItem).'">'.$GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_direct_link').' </a><br>';
        $body .= '<br>'.$this->getTableDescriptionAsMessage($table, Codendi_Mail_Interface::FORMAT_HTML).'<br><br>';
        $body .= $GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_notif_type').' '.$this->getNotificationStyle($table).' <br><br>';
        $body .= '<a href="'.$this->getReviewUrl($docmanItem).'"><b>'.$GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_review_url').'<b></a><br>';
        $body .= '<br>--<br><i>'.$GLOBALS['Language']->getText('plugin_docman', 'approval_reminder_html_mail_body_footer').'</i><br>';
        return $body;
    }

    /**
     * Populate reviewers list of an approval table
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Void
     */
    private function populateReviewersList(Docman_ApprovalTable $table)
    {
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
