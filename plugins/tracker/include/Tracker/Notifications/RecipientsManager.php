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
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\Tracker\Notifications\RemoveRecipient\ArtifactStatusChangeDetector;
use Tuleap\Tracker\Notifications\RemoveRecipient\ArtifactStatusChangeDetectorImpl;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatAreTechnicalUsers;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatCannotReadAnything;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatDoesntWantMailForTheirOwnActions;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatHaveUnsubscribedFromNotification;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientWhenTheyAreInCreationOnlyMode;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientWhenTheyAreInStatusUpdateOnlyMode;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\User\NotificationOnAllUpdatesRetriever;
use Tuleap\Tracker\User\NotificationOnOwnActionRetriever;
use UserManager;

class RecipientsManager
{
    /** @var list<RecipientRemovalStrategy> */
    private readonly array $global_recipient_removal_strategies;
    /** @var list<RecipientRemovalStrategy> */
    private readonly array $mentioned_recipient_removal_strategies;
    private readonly ArtifactStatusChangeDetector $status_change_detector;

    public function __construct(
        private readonly Tracker_FormElementFactory $form_element_factory,
        private readonly UserManager $user_manager,
        private readonly UnsubscribersNotificationDAO $unsubscribers_notification_dao,
        private readonly UserNotificationSettingsRetriever $notification_settings_retriever,
        private readonly UserNotificationOnlyStatusChangeDAO $user_status_change_only_dao,
        private readonly NotificationOnAllUpdatesRetriever $notification_on_all_updates_retriever,
        NotificationOnOwnActionRetriever $notification_on_own_action_retriever,
        private readonly MentionedUserInTextRetriever $user_in_text_retriever,
    ) {
        $this->status_change_detector                 = new ArtifactStatusChangeDetectorImpl();
        $this->mentioned_recipient_removal_strategies = [
            new RemoveRecipientThatAreTechnicalUsers(),
            new RemoveRecipientThatDoesntWantMailForTheirOwnActions($notification_on_own_action_retriever),
            new RemoveRecipientThatCannotReadAnything(),
        ];
        $this->global_recipient_removal_strategies    = [
            ...$this->mentioned_recipient_removal_strategies,
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
    public function getRecipients(Tracker_Artifact_Changeset $changeset, bool $is_update, bool $send_notifications_to_subscribed_users, LoggerInterface $logger): array
    {
        $recipients_from_subscriptions = [];
        if ($send_notifications_to_subscribed_users) {
            $recipients_from_subscriptions = $this->getRecipientsFromSubscriptions($changeset, $is_update);
        }

        return array_map(
            static fn (Recipient $recipient) => $recipient->check_permissions,
            $this->removeRecipientsUsingStrategies($this->global_recipient_removal_strategies, $logger, $changeset, $recipients_from_subscriptions, $is_update)
            + $this->getRecipientsFromComment($changeset, $is_update, $logger)
        );
    }

    /**
     * @psalm-return array<string, Recipient>
     */
    private function getRecipientsFromSubscriptions(Tracker_Artifact_Changeset $changeset, bool $is_update): array
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
        $recipients = array_merge($recipients, array_filter(
            $changeset->getArtifact()->getCommentators(),
            function (string $commentator) {
                $user = $this->getUserFromRecipientName($commentator);
                if ($user === null) {
                    return false;
                }
                return $this->notification_on_all_updates_retriever->retrieve($user)->enabled;
            },
        ));
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

        return $tablo;
    }

    /**
     * @psalm-return array<string, Recipient>
     */
    private function getRecipientsFromComment(Tracker_Artifact_Changeset $changeset, bool $is_update, LoggerInterface $logger): array
    {
        $recipients      = [];
        $mentioned_users = $this->user_in_text_retriever->getMentionedUsers($changeset->getComment()?->body ?? '');
        foreach ($mentioned_users->users as $user) {
            $recipients[$user->getUserName()] = Recipient::fromUser($user);
        }
        return $this->removeRecipientsUsingStrategies($this->mentioned_recipient_removal_strategies, $logger, $changeset, $recipients, $is_update);
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
        return (int) $changeset->getTracker()->getNotificationsLevel() === \Tuleap\Tracker\Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
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

    public function getAllRecipientsWhoHaveCustomSettingsForATracker(\Tuleap\Tracker\Tracker $tracker)
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

    /**
     * @param list<RecipientRemovalStrategy> $removal_strategies
     * @param array<string, Recipient> $recipients
     *
     * @psalm-return array<string, Recipient>
     */
    private function removeRecipientsUsingStrategies(
        array $removal_strategies,
        LoggerInterface $logger,
        Tracker_Artifact_Changeset $changeset,
        array $recipients,
        bool $is_update,
    ): array {
        foreach ($removal_strategies as $strategy) {
            if ($recipients === []) {
                $logger->debug('Recipient list is empty, skip other removal strategies');
                break;
            }
            $recipients = $strategy->removeRecipient($logger, $changeset, $recipients, $is_update);
        }
        return $recipients;
    }
}
