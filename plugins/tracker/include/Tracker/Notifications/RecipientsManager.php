<?php
/**
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
 *
 */

namespace Tuleap\Tracker\Notifications;

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Notifications\RemoveRecipient\ArtifactStatusChangeDetector;
use Tuleap\Tracker\Notifications\RemoveRecipient\ArtifactStatusChangeDetectorImpl;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatAreTechnicalUsers;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatCannotReadAnything;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatDoesntWantMailForTheirOwnActions;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatHaveUnsubscribedFromNotification;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientWhenTheyAreInStatusUpdateOnlyMode;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientWhenTheyAreInCreationOnlyMode;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use UserManager;

class RecipientsManager
{
    /**
     * @var RecipientRemovalStrategy[]
     */
    private array $recipient_removal_strategies;

    private ArtifactStatusChangeDetector $status_change_detector;

    public function __construct(
        private Tracker_FormElementFactory $form_element_factory,
        private UserManager $user_manager,
        private UnsubscribersNotificationDAO $unsubscribers_notification_dao,
        private UserNotificationSettingsRetriever $notification_settings_retriever,
        private UserNotificationOnlyStatusChangeDAO $user_status_change_only_dao,
    ) {
        $this->status_change_detector       = new ArtifactStatusChangeDetectorImpl();
        $this->recipient_removal_strategies = [
            new RemoveRecipientThatAreTechnicalUsers(),
            new RemoveRecipientThatDoesntWantMailForTheirOwnActions(),
            new RemoveRecipientThatCannotReadAnything($this->form_element_factory),
            new RemoveRecipientThatHaveUnsubscribedFromNotification($this->unsubscribers_notification_dao),
            new RemoveRecipientWhenTheyAreInStatusUpdateOnlyMode($this->user_status_change_only_dao, $this->status_change_detector),
            new RemoveRecipientWhenTheyAreInCreationOnlyMode($this->notification_settings_retriever),
        ];
    }

    /**
     * Get the recipients for notification
     *
     * @psalm-return array<string, bool> Structure is [$recipient => $checkPermissions] where $recipient is a username or an email and $checkPermissions is bool.
     */
    public function getRecipients(Tracker_Artifact_Changeset $changeset, bool $is_update, LoggerInterface $logger): array
    {
        // 1 Get from the fields
        $recipients = [];
        $changeset->forceFetchAllValues();
        foreach ($changeset->getValues() as $field_id => $current_changeset_value) {
            if ($field = $this->form_element_factory->getFieldById($field_id)) {
                if ($field->isNotificationsSupported() && $field->hasNotifications() && ($r = $field->getRecipients($current_changeset_value))) {
                    $recipients = array_merge($recipients, $r);
                }
            }
        }
        // 2 Get from the commentators
        $recipients = array_merge($recipients, $changeset->getArtifact()->getCommentators());
        $recipients = array_values(array_unique($recipients));

        //now force check perms for all this people
        $tablo = [];
        foreach ($recipients as $r) {
            $user = $this->getUserFromRecipientName($r);
            if (! $user) {
                continue;
            }
            $tablo[$r] = Recipient::fromUser($user);
        }

        $this->removeRecipientsWhenTrackerIsInOnlyStatusUpdateMode($changeset, $tablo);

        // 3 Get from the global notif
        foreach ($changeset->getTracker()->getRecipients() as $r) {
            if ($r['on_updates'] == 1 || ! $is_update) {
                foreach ($r['recipients'] as $recipient) {
                    if (isset($tablo[$recipient])) {
                        $tablo[$recipient] = Recipient::fromUserWithPermissions($tablo[$recipient]->user, (bool) $r['check_permissions']);
                    } else {
                        $user = $this->getUserFromRecipientName($recipient);
                        if (! $user) {
                            continue;
                        }
                        $tablo[$recipient] = Recipient::fromUserWithPermissions($user, (bool) $r['check_permissions']);
                    }
                }
            }
        }

        foreach ($this->recipient_removal_strategies as $strategy) {
            if (empty($tablo)) {
                $logger->debug('Recepient list is empty, skip other removal strategies');
                break;
            }
            $tablo = $strategy->removeRecipient($logger, $changeset, $tablo, $is_update);
        }

        return array_map(static fn (Recipient $recipient) => $recipient->check_permissions, $tablo);
    }

    public function getUserFromRecipientName(string $recipient_name): ?PFUser
    {
        $user = null;
        if (strpos($recipient_name, '@') !== false) {
            //check for registered
            $user = $this->user_manager->getUserByEmail($recipient_name);

            //user does not exist (not registered/mailing list) then it is considered as an anonymous
            if (! $user) {
                // don't call $um->getUserAnonymous() as it will always return the same instance
                // we don't want to override previous emails
                // So create new anonymous instance by hand
                $user = $this->user_manager->getUserInstanceFromRow(
                    [
                        'user_id' => 0,
                        'email'   => $recipient_name,
                    ]
                );
            }
        } else {
            //is a login
            $user = $this->user_manager->getUserByUserName($recipient_name);
        }

        return $user;
    }

    private function removeRecipientsWhenTrackerIsInOnlyStatusUpdateMode(
        Tracker_Artifact_Changeset $changeset,
        array &$recipients,
    ): void {
        if (! $this->isTrackerInStatusUpdateOnlyNotificationsMode($changeset)) {
            return;
        }

        if ($this->status_change_detector->hasChanged($changeset)) {
            return;
        }

        $this->removeUsersWhoAreNotInAllNotificationsOrInvolvedMode($changeset, $recipients);
    }

    /**
     *
     * @return bool
     */
    private function isTrackerInStatusUpdateOnlyNotificationsMode(Tracker_Artifact_Changeset $changeset)
    {
        return (int) $changeset->getTracker()->getNotificationsLevel() === \Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
    }

    /**
     * @param array<string, Recipient>                      $recipients
     */
    private function removeUsersWhoAreNotInAllNotificationsOrInvolvedMode(Tracker_Artifact_Changeset $changeset, array &$recipients): void
    {
        $tracker = $changeset->getTracker();

        foreach ($recipients as $key => $recipient) {
            $user_notification_settings = $this->notification_settings_retriever->getUserNotificationSettings(
                $recipient->user,
                $tracker
            );

            if (
                ! $user_notification_settings->isInNotifyOnEveryChangeMode() &&
                ! $user_notification_settings->isInNoGlobalNotificationMode()
            ) {
                unset($recipients[$key]);
            }
        }
    }

    public function getAllRecipientsWhoHaveCustomSettingsForATracker(\Tracker $tracker)
    {
        $user_ids_of_tracker_recipients         = $this->extractUserIdFromGlobalNotificationsRecipientList($tracker->getRecipients());
        $user_ids_of_tracker_unsubscribers      = $this->unsubscribers_notification_dao->searchUserIDHavingUnsubcribedFromNotificationByTrackerID($tracker->getId());
        $user_ids_if_tracker_status_change_only = $this->user_status_change_only_dao->searchUserIdsHavingSubscribedForTrackerStatusChangedOnly($tracker->getId());

        return array_merge($user_ids_of_tracker_recipients, $user_ids_of_tracker_unsubscribers, $user_ids_if_tracker_status_change_only);
    }

    private function extractUserIdFromGlobalNotificationsRecipientList(array $recipients)
    {
        $user_ids = [];
        foreach ($recipients as $recipient_list) {
            foreach ($recipient_list['recipients'] as $recipient) {
                $user = $this->getUserFromRecipientName($recipient);
                if ($user) {
                    $user_ids[] = (int) $user->getId();
                }
            }
        }

        return $user_ids;
    }
}
