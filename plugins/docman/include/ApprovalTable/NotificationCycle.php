<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Config\ConfigurationVariables;
use Tuleap\ServerHostname;

class Docman_ApprovalTableNotificationCycle // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public $table;
    public $owner;
    public $item;

    /** @var Docman_NotificationsManager|null */
    private $notificationManager = null;

    public function __construct(
        private readonly MailNotificationBuilder $mail_notification_builder,
        private readonly Codendi_HTMLPurifier $purifier,
    ) {
        $this->table = null;
        $this->owner = null;
        $this->item  = null;
    }

    public function reviewUpdated($review)
    {
        // Parameters
        $withComments = false;
        if (trim($review->getComment()) != '') {
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
    public function reviewerApprove($reviewer, $isLastReviewer, $withComments)
    {
        if ($isLastReviewer) {
            $this->sendNotifTableApproved($reviewer, $withComments);
            $this->changeItemStatus($reviewer, PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        } else {
            $this->sendNotifReviewApproved($reviewer, $withComments);
        }

        if (
            ! $isLastReviewer &&
            $this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL
        ) {
            $this->notifyNextReviewer();
        }
    }

    /**
     * Action
     */
    public function reviewerReject($reviewer)
    {
        $this->sendNotifRejected($reviewer);
        $this->changeItemStatus($reviewer, PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);
    }

    /**
     * Action
     */
    public function reviewerDecline($reviewer, $isLastReviewer)
    {
        $this->sendNotifReviewDeclined($reviewer);
        if (
            ! $isLastReviewer &&
            $this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL
        ) {
            $this->notifyNextReviewer();
        }
    }

    /**
     * Action
     */
    public function reviewerComment($reviewer)
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
    public function notifyAllAtOnce()
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
    public function notifyNextReviewer()
    {
        $dao = $this->_getReviewerDao();

        $dar = $dao->getFirstReviewerByStatus($this->table->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return false;
        } else {
            $dar = $dao->getFirstReviewerByStatus($this->table->getId(), [PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
                PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED,
            ]);
            if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                return $this->notifyIndividual($row['reviewer_id']);
            }
        }
        return false;
    }

    /**
     * Action
     */
    public function notifyIndividual($reviewerId)
    {
        // enable item monitoring
        $this->enableMonitorForReviewer($reviewerId);

        $um       = $this->_getUserManager();
        $reviewer = $um->getUserById($reviewerId);

        return $this->sendNotifReviewer($reviewer);
    }

    /**
     * Enable the monitoring of an item for a given reviewer
     */
    private function enableMonitorForReviewer($reviewerId)
    {
        if (($this->notificationManager !== null) && ! $this->notificationManager->userExists($reviewerId, $this->item->getId())) {
            $this->notificationManager->add($reviewerId, $this->item->getId());
        }
    }

    /**
     * Update item status according to parameters.
     * Not in use today.
     */
    public function changeItemStatus($reviewer, $status)
    {
        // TBD
    }

    public function getReviewUrl()
    {
        $baseUrl   = ServerHostname::HTTPSUrl() . '/plugins/docman/?group_id=' . $this->item->getGroupId();
        $reviewUrl = $baseUrl . '&action=details&section=approval&id=' . $this->item->getId();
        return $reviewUrl;
    }

    /**
     * Notify table owner
     */
    public function sendNotifRejected($reviewer)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $reviewUrl       = $this->getReviewUrl();

        $subject = sprintf(dgettext('tuleap-docman', '[%1$s] \'%2$s\' was rejected by a reviewer'), ForgeConfig::get(ConfigurationVariables::NAME), $this->item->getTitle());

        $body = sprintf(dgettext('tuleap-docman', 'Your document \'%1$s\' was  rejected by %3$s <%4$s>.
Direct access to the approval table:
<%2$s>

--
This is an automatic email sent by a robot. Please do not reply to this email.'), $this->item->getTitle(), $reviewUrl, $reviewer->getRealName(), $reviewer->getEmail());

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            [$reviewer->getEmail()],
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
    public function sendNotifReviewApproved($reviewer, $withComments)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());

        $reviewUrl = $this->getReviewUrl();
        $body      = sprintf(dgettext('tuleap-docman', 'Your document \'%1$s\' was approved by %3$s <%4$s>.
You can access to the table with the following link:
<%2$s>

--
This is an automatic email sent by a robot. Please do not reply to this email.'), $this->item->getTitle(), $reviewUrl, $reviewer->getRealName(), $reviewer->getEmail());

        $comment = '';
        if ($withComments) {
            $comment = dgettext('tuleap-docman', 'with comments');
        }

        $subject = sprintf(dgettext('tuleap-docman', '[%1$s] \'%2$s\' was approved by a reviewer %3$s'), ForgeConfig::get(ConfigurationVariables::NAME), $this->item->getTitle(), $comment);

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            [$this->owner->getEmail()],
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
    public function sendNotifTableApproved($reviewer, $withComments)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $service         = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
        assert($service instanceof \Tuleap\Docman\ServiceDocman);

        $reviewUrl = $this->getReviewUrl();
        $baseUrl   = ServerHostname::HTTPSUrl() . $service->getUrl();
        $propUrl   = $baseUrl;
        if ($this->item->getParentId()) {
            $propUrl .= 'preview/' . urlencode((string) $this->item->getId());
        }
        $body = sprintf(dgettext('tuleap-docman', 'Your document \'%1$s\' was approved by last reviewer: %3$s <%4$s>.
You can access to the table with the following link:
<%2$s>

Please note that the document status was not automaticaly changed. You can
change the document properties:
<%5$s>

--
This is an automatic email sent by a robot. Please do not reply to this email.'), $this->item->getTitle(), $reviewUrl, $reviewer->getRealName(), $reviewer->getEmail(), $propUrl);

        $comment = '';
        if ($withComments) {
            $comment = dgettext('tuleap-docman', 'with comments');
        }

        $subject = sprintf(dgettext('tuleap-docman', '[%1$s] \'%2$s\' was approved by last reviewer %3$s'), ForgeConfig::get(ConfigurationVariables::NAME), $this->item->getTitle(), $comment);

        return $this->mail_notification_builder->buildAndSendEmail(
            $project,
            [$this->owner->getEmail()],
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
    public function sendNotifReviewDeclined($reviewer)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $reviewUrl       = $this->getReviewUrl();

        $subject = sprintf(dgettext('tuleap-docman', '[%1$s] a reviewer will not review \'%2$s\''), ForgeConfig::get(ConfigurationVariables::NAME), $this->item->getTitle());

        $body = sprintf(dgettext('tuleap-docman', 'Your document \'%1$s\' will not be reviewed by %3$s <%4$s>.

You can access to the table with the following link:
<%2$s>

--
This is an automatic email sent by a robot. Please do not reply to this email.'), $this->item->getTitle(), $reviewUrl, $reviewer->getRealName(), $reviewer->getEmail());

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            [$this->owner->getEmail()],
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
    public function sendNotifReviewCommented($reviewer)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $reviewUrl       = $this->getReviewUrl();

        $commentSeq = '';
        if ($this->table->getNotification() == PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL) {
            $commentSeq  = sprintf(dgettext('tuleap-docman', 'Important note: this approval table is configured in \'Sequential\' mode.
The notification sequence is on hold until %1$s approves or rejects the document.'), $reviewer->getRealName());
            $commentSeq .= "\n";
        }

        $subject = sprintf(dgettext('tuleap-docman', '[%1$s] a reviewer commented \'%2$s\''), ForgeConfig::get(ConfigurationVariables::NAME), $this->item->getTitle());

        $body = sprintf(dgettext('tuleap-docman', 'Your document \'%1$s\' was commented (but neither approved nor rejected) by \'%2$s\' <%3$s>.
%5$s
You can access to the table with the following link:
<%4$s>

--
This is an automatic email sent by a robot. Please do not reply to this email.'), $this->item->getTitle(), $reviewer->getRealName(), $reviewer->getEmail(), $reviewUrl, $commentSeq);

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            [$reviewer->getEmail()],
            $subject,
            '',
            $body,
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    public function sendNotifReviewer($reviewer)
    {
        // Project
        $pm    = ProjectManager::instance();
        $group = $pm->getProject($this->item->getGroupId());

        // Url
        $reviewUrl = $this->getReviewUrl() . '&review=1';

        $subject = $this->getNotificationSubject();
        $body    = $this->getNotificationBodyHTML();

        return $this->mail_notification_builder->buildAndSendEmail(
            $group,
            [$reviewer->getEmail()],
            $subject,
            $body,
            '',
            $reviewUrl,
            DocmanPlugin::TRUNCATED_SERVICE_NAME,
            new MailEnhancer()
        );
    }

    /**
     * Return current item approval table state
     */
    public function getTableState()
    {
        $nbApproved  = 0;
        $nbDeclined  = 0;
        $rejected    = false;
        $revIterator = $this->table->getReviewerIterator();
        while (! $rejected && $revIterator->valid()) {
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
    public function setTable(&$table)
    {
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setOwner(&$owner)
    {
        $this->owner = $owner;
    }

    public function setItem($item)
    {
        $this->item = $item;
    }

    // Class accessor
    public function _getReviewerDao() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
    }

    public function _getUserManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return UserManager::instance();
    }

    public function _getUserById($id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return UserManager::instance()->getUserById($id);
    }

    public function _getItemFactory() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return new Docman_ItemFactory($this->item->getGroupId());
    }

    public function _getEventManager() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return EventManager::instance();
    }

    public function setNotificationManager($notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    public function getNotificationSubject()
    {
        return sprintf(dgettext('tuleap-docman', '[%1$s] Please review \'%2$s\''), ForgeConfig::get(ConfigurationVariables::NAME), $this->item->getTitle());
    }

    public function getNotificationBodyHTML(): string
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($this->item->getGroupId());
        $document_id     = $this->item->getId();
        if ($this->item instanceof Docman_Folder) {
            $document_link = ServerHostname::HTTPSUrl() . '/plugins/document/' . $project->getUnixNameLowerCase() . '/folder/' . $document_id;
        } else {
            $folder_id     = $this->item->getParentId();
            $document_link = ServerHostname::HTTPSUrl() . '/plugins/document/' . $project->getUnixNameLowerCase() . '/folder/' . $folder_id . '/' . $document_id;
        }
        $comment           = $this->table->getDescription();
        $notification_type = match ((int) $this->table->getNotification()) {
            PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL => sprintf(
                dgettext('tuleap-docman', 'Sequence. %s notifies reviewers one after another. People <em>will not be notified</em> to review the document <em>until you approved it</em>.'),
                ForgeConfig::get(ConfigurationVariables::NAME),
            ),
            PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE  => dgettext('tuleap-docman', 'All at once'),
        };
        $review_url = ServerHostname::HTTPSUrl() . '/plugins/document/' . $project->getUnixNameLowerCase() . '/approval-table/' . $document_id;

        $template_renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/templates');
        return $template_renderer->renderToString('notification-mail', [
            'project-name'      => $project->getPublicName(),
            'document-title'    => $this->item->getTitle(),
            'document-link'     => $document_link,
            'owner-name'        => $this->owner->getRealName(),
            'owner-mail'        => $this->owner->getEmail(),
            'comment'           => $comment !== '' ? $comment : false,
            'notification-type' => $this->purifier->purify($notification_type, Codendi_HTMLPurifier::CONFIG_LIGHT),
            'review-url'        => $review_url,
        ]);
    }
}
