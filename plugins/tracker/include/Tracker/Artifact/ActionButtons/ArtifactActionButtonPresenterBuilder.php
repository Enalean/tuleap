<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ActionButtons;

use Codendi_HTMLPurifier;
use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_Changeset_IncomingMailGoldenRetriever;
use Tracker_ArtifactDao;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;

class ArtifactActionButtonPresenterBuilder
{
    /**
     * @var Tracker_Artifact_Changeset_IncomingMailGoldenRetriever
     */
    private $mail_golden_retriever;
    /**
     * @var UnsubscribersNotificationDAO
     */
    private $unsubscribers_notification_DAO;
    /**
     * @var Tracker_ArtifactDao
     */
    private $tracker_artifact_dao;

    public function __construct(
        Tracker_Artifact_Changeset_IncomingMailGoldenRetriever $mail_golden_retriever,
        UnsubscribersNotificationDAO $unsubscribers_notification_DAO,
        Tracker_ArtifactDao $tracker_artifact_dao
    ) {
        $this->mail_golden_retriever          = $mail_golden_retriever;
        $this->unsubscribers_notification_DAO = $unsubscribers_notification_DAO;
        $this->tracker_artifact_dao           = $tracker_artifact_dao;
    }

    public function build(PFUser $user, Tracker_Artifact $artifact)
    {
        $action_buttons = [];

        $original_email = $this->getIncomingEmailButton($user, $artifact);
        $copy_artifact  = $this->getCopyArtifactButton($user, $artifact);
        $notification   = $this->getNotificationButton($user, $artifact);

        if ($original_email) {
            $action_buttons[]['section'] = $original_email;
        }
        if ($copy_artifact) {
            $action_buttons[]['section'] = $copy_artifact;
        }

        if (($original_email || $copy_artifact) && $notification) {
            $action_buttons[]['divider'] = true;
        }

        if ($notification) {
            $action_buttons[]['section'] = $notification;
        }

        return new GlobalButtonsActionPresenter($action_buttons);
    }

    private function getIncomingEmailButton(PFUser $user, Tracker_Artifact $artifact)
    {
        if (! $user->isSuperUser()) {
            return;
        }

        $raw_mail = $this->mail_golden_retriever->getRawMailThatCreatedArtifact($artifact);
        if (! $raw_mail) {
            return;
        }

        $raw_email_button_title = $GLOBALS['Language']->getText('plugin_tracker', 'raw_email_button_title');
        $raw_mail               = Codendi_HTMLPurifier::instance()->purify($raw_mail);

        return new ActionButtonPresenter(
            $raw_email_button_title,
            "",
            "",
            "icon-envelope-alt",
            'data-raw-email="' . $raw_mail . '"',
            "artifact-incoming-mail-button"
        );
    }

    private function getNotificationButton(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($this->unsubscribers_notification_DAO->doesUserIDHaveUnsubscribedFromTrackerNotifications(
            $user->getId(),
            $artifact->getTrackerId()
        )) {
            return;
        }

        return new ActionButtonPresenter(
            $this->getUnsubscribeButtonLabel($user, $artifact),
            $this->getUnsubscribeButtonAlternateText($user, $artifact),
            "",
            "icon-bell-alt",
            "",
            "tracker-artifact-notification"
        );
    }

    private function getUnsubscribeButtonAlternateText(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($this->doesUserHaveUnsubscribedFromArtifactNotification($user, $artifact)) {
            return $GLOBALS['Language']->getText('plugin_tracker', 'enable_notifications_alternate_text');
        }

        return $GLOBALS['Language']->getText('plugin_tracker', 'disable_notifications_alternate_text');
    }

    private function doesUserHaveUnsubscribedFromArtifactNotification(PFUser $user, Tracker_Artifact $artifact)
    {
        return $this->tracker_artifact_dao->doesUserHaveUnsubscribedFromArtifactNotifications(
            $artifact->getId(),
            $user->getId()
        );
    }

    private function getUnsubscribeButtonLabel(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($this->doesUserHaveUnsubscribedFromArtifactNotification($user, $artifact)) {
            return $GLOBALS['Language']->getText('plugin_tracker', 'enable_notifications');
        }

        return $GLOBALS['Language']->getText('plugin_tracker', 'disable_notifications');
    }

    private function getCopyArtifactButton(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($user->isLoggedIn() && ! $this->isAlreadyCopyingArtifact()) {
            return new ActionButtonPresenter(
                $GLOBALS['Language']->getText('plugin_tracker', 'copy_this_artifact'),
                $GLOBALS['Language']->getText('plugin_tracker', 'copy_this_artifact'),
                TRACKER_BASE_URL . '/?func=copy-artifact&aid=' . $artifact->getId(),
                "icon-copy",
                "",
                ""
            );
        }
    }

    private function isAlreadyCopyingArtifact()
    {
        return strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL . '/?func=copy-artifact') === 0;
    }
}
