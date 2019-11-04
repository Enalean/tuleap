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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use PFUser;
use Tracker_Artifact;
use Tracker_ArtifactDao;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;

class ArtifactNotificationActionButtonPresenterBuilder
{
    /**
     * @var UnsubscribersNotificationDAO
     */
    private $unsubscribers_DAO;
    /**
     * @var Tracker_ArtifactDao
     */
    private $tracker_artifact_dao;

    public function __construct(
        UnsubscribersNotificationDAO $unsubscribers_notification_DAO,
        Tracker_ArtifactDao $tracker_artifact_dao
    ) {
        $this->unsubscribers_DAO    = $unsubscribers_notification_DAO;
        $this->tracker_artifact_dao = $tracker_artifact_dao;
    }

    public function getNotificationButton(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($user->isAnonymous()) {
            return;
        }

        if ($this->unsubscribers_DAO->doesUserIDHaveUnsubscribedFromTrackerNotifications($user->getId(), $artifact->getTrackerId())) {
            return;
        }

        return new ArtifactNotificationsButtonPresenter(
            $this->getUnsubscribeButtonLabel($user, $artifact),
            $this->getUnsubscribeButtonAlternateText($user, $artifact),
            $this->getUnsubscribeButtonIcon($user, $artifact)
        );
    }

    private function getUnsubscribeButtonLabel(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($this->doesUserHaveUnsubscribedFromArtifactNotification($user, $artifact)) {
            return $GLOBALS['Language']->getText('plugin_tracker', 'enable_notifications');
        }

        return $GLOBALS['Language']->getText('plugin_tracker', 'disable_notifications');
    }

    private function doesUserHaveUnsubscribedFromArtifactNotification(PFUser $user, Tracker_Artifact $artifact)
    {
        return $this->tracker_artifact_dao->doesUserHaveUnsubscribedFromArtifactNotifications(
            $artifact->getId(),
            $user->getId()
        );
    }

    private function getUnsubscribeButtonAlternateText(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($this->doesUserHaveUnsubscribedFromArtifactNotification($user, $artifact)) {
            return $GLOBALS['Language']->getText('plugin_tracker', 'enable_notifications_alternate_text');
        }

        return $GLOBALS['Language']->getText('plugin_tracker', 'disable_notifications_alternate_text');
    }

    private function getUnsubscribeButtonIcon(PFUser $user, Tracker_Artifact $artifact)
    {
        if ($this->doesUserHaveUnsubscribedFromArtifactNotification($user, $artifact)) {
            return 'fa-bell-o';
        }

        return 'fa-bell-slash-o';
    }
}
