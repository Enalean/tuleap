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

use Tuleap\Date\DateHelper;
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
        $dao    = new Docman_ApprovalTableItemDao();
        $dar    = $dao->getTablesForReminder();
        $tables = [];
        if ($dar && ! $dar->isError()) {
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
        $reviewers = $table->getReviewerArray();
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
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return false;
        } else {
            $dar = $dao->getFirstReviewerByStatus($table->getId(), [PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED]);
            if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
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

        $subject = sprintf(dgettext('tuleap-docman', '[%1$s] [Reminder] Please review \'%2$s\''), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $docmanItem->getTitle());

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
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    new MailLogger()
                )
            )
        );

        return $mail_notification_builder->buildAndSendEmail(
            $this->getItemProject($docmanItem),
            [$reviewer->getEmail()],
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
        $baseUrl   = \Tuleap\ServerHostname::HTTPSUrl() . '/plugins/docman/?group_id=' . $docmanItem->getGroupId();
        $reviewUrl = $baseUrl . '&action=details&section=approval&id=' . $docmanItem->getId() . '&review=1';
        return $reviewUrl;
    }

    /**
     * Retrieve url to access a given docman item
     *
     * @param Docman_Item $docmanItem The approval table that its reminder notification will be sent
     *
     * @return String
     */
    private function getItemUrl(Docman_Item $docmanItem)
    {
        $baseUrl = \Tuleap\ServerHostname::HTTPSUrl() . '/plugins/docman/?group_id=' . $docmanItem->getGroupId();
        $itemUrl = $baseUrl . '&action=show&id=' . $docmanItem->getId();
        return $itemUrl;
    }

    private function getNotificationStyle(Docman_ApprovalTable $table): string
    {
        $notifStyle = '';
        switch ($table->getNotification()) {
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
                $notifStyle = sprintf(dgettext('tuleap-docman', 'Sequence.
%1$s notifies reviewers one after another.
People *will not be notified* to review the document *until you approved it*.'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
                break;
            case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
                $notifStyle = dgettext('tuleap-docman', 'All at once');
                break;
        }
        return $notifStyle;
    }

    private function getTableDescriptionAsMessage(Docman_ApprovalTable $table, string $format): string
    {
        $comment     = '';
        $userComment = $table->getDescription();
        if ($userComment != '') {
            switch ($format) {
                case Codendi_Mail_Interface::FORMAT_HTML:
                    $comment  = '<b>' . dgettext('tuleap-docman', 'Message:') . '</b><br>';
                    $comment .= '<hr align="center" width="50%" color="midnightblue" size="3"><br>' . $userComment . '<br><hr align="center" width="50%" color="midnightblue" size="3"><br><br>';
                    $comment .= '<br>';
                    break;
                case Codendi_Mail_Interface::FORMAT_TEXT:
                    $comment  = sprintf(dgettext('tuleap-docman', 'Message:
------------
%1$s
------------'), $userComment);
                    $comment .= "\n\n";
                    break;
                default:
                    $comment = dgettext('tuleap-docman', 'Message:');
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
        $um = UserManager::instance();
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
        $pm = ProjectManager::instance();
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

        $body = sprintf(dgettext('tuleap-docman', 'You are requested to review the following document:

Project: %2$s
Title: %1$s
Document: <%4$s>

Requester: %3$s <%8$s>
Your review: <%7$s>

%5$sNotification type: %6$s

Click on the following link to approve or reject the document:
<%7$s>

--
This is an automatic message. Please do not reply to this email.'), $docmanItem->getTitle(), $group->getPublicName(), $owner->getRealName(), $this->getItemUrl($docmanItem), $this->getTableDescriptionAsMessage($table, Codendi_Mail_Interface::FORMAT_TEXT), $this->getNotificationStyle($table), $this->getReviewUrl($docmanItem), $owner->getEmail());

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
        $purifier = Codendi_HTMLPurifier::instance();
        $group    = $this->getItemProject($docmanItem);
        $owner    = $this->getApprovalTableOwner($table);
        $body     = dgettext('tuleap-docman', 'You are requested to review the following document:') . '<br><br><b>';
        $body    .= dgettext('tuleap-docman', 'Title:') . '</b> ' . $docmanItem->getTitle() . '<br><b>';
        $body    .= dgettext('tuleap-docman', 'Project:') . '</b> ' . $purifier->purify($group->getPublicName()) . '<br><b>';
        $body    .= dgettext('tuleap-docman', 'Requester:') . '</b><a href="' . $owner->getEmail() . '">' . $owner->getRealName() . '</a><br>';
        $body    .= '<a href="' . $this->getItemUrl($docmanItem) . '">' . dgettext('tuleap-docman', 'Direct link to the document') . ' </a><br>';
        $body    .= '<br>' . $this->getTableDescriptionAsMessage($table, Codendi_Mail_Interface::FORMAT_HTML) . '<br><br>';
        $body    .= dgettext('tuleap-docman', 'Notification type:') . ' ' . $this->getNotificationStyle($table) . ' <br><br>';
        $body    .= '<a href="' . $this->getReviewUrl($docmanItem) . '"><b>' . dgettext('tuleap-docman', 'Click on the following link to approve or reject the document') . '<b></a><br>';
        $body    .= '<br>--<br><i>' . dgettext('tuleap-docman', 'This is an automatic message. Please do not reply to this email') . '</i><br>';
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
        if ($dar && ! $dar->isError()) {
            foreach ($dar as $row) {
                $reviewer = new Docman_ApprovalReviewer();
                $reviewer->initFromRow($row);
                $table->addReviewer($reviewer);
                unset($reviewer);
            }
        }
    }
}
