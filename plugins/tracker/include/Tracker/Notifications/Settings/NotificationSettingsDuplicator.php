<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Settings;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Notifications\ConfigNotificationAssignedToDao;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderDao;
use Tuleap\Tracker\Notifications\GlobalNotificationDuplicationDao;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDuplicationDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDuplicationDao;
use Tuleap\Tracker\TrackerDuplicationUserGroupMapping;

final class NotificationSettingsDuplicator
{
    public function __construct(
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly GlobalNotificationDuplicationDao $global_notification_dao,
        private readonly UsersToNotifyDuplicationDao $duplicate_users_dao,
        private readonly UgroupsToNotifyDuplicationDao $ugroups_to_notify_dao,
        private readonly ConfigNotificationAssignedToDao $assigned_to_dao,
        private readonly ConfigNotificationEmailCustomSenderDao $custom_sender_dao,
        private readonly DateReminderDao $date_reminder_dao,
        private readonly CalendarEventConfigDao $calendar_event_config_dao,
    ) {
    }

    public function duplicate(int $template_tracker_id, int $new_tracker_id, TrackerDuplicationUserGroupMapping $duplication_user_group_mapping, array $field_mapping): void
    {
        $this->transaction_executor->execute(fn () => $this->duplicateNotificationsSettings($template_tracker_id, $new_tracker_id, $duplication_user_group_mapping, $field_mapping));
    }

    private function duplicateNotificationsSettings(int $template_tracker_id, int $new_tracker_id, TrackerDuplicationUserGroupMapping $duplication_user_group_mapping, array $field_mapping): void
    {
        $this->duplicateGlobalNotifications($template_tracker_id, $new_tracker_id, $duplication_user_group_mapping);
        $this->duplicateAssignedTo($template_tracker_id, $new_tracker_id);
        $this->duplicateCustomSender($template_tracker_id, $new_tracker_id);
        $this->duplicateReminders($template_tracker_id, $new_tracker_id, $duplication_user_group_mapping, $field_mapping);
        $this->duplicateCalendarEventConfig($template_tracker_id, $new_tracker_id);
    }

    private function duplicateGlobalNotifications(int $template_tracker_id, int $new_tracker_id, TrackerDuplicationUserGroupMapping $duplication_user_group_mapping): void
    {
        foreach ($this->global_notification_dao->getByTrackerId($template_tracker_id) as $template_notification_id) {
            $new_notification_id = $this->global_notification_dao->duplicate($template_notification_id, $new_tracker_id);
            $this->duplicate_users_dao->duplicate($template_notification_id, $new_notification_id);
            $this->ugroups_to_notify_dao->duplicate($template_notification_id, $new_notification_id, $duplication_user_group_mapping);
        }
    }

    private function duplicateAssignedTo(int $template_tracker_id, int $new_tracker_id): void
    {
        $this->assigned_to_dao->duplicate($template_tracker_id, $new_tracker_id);
    }

    private function duplicateCustomSender(int $template_tracker_id, int $new_tracker_id): void
    {
        $this->custom_sender_dao->duplicate($template_tracker_id, $new_tracker_id);
    }

    private function duplicateReminders(int $template_tracker_id, int $new_tracker_id, TrackerDuplicationUserGroupMapping $duplication_user_group_mapping, array $field_mapping): void
    {
        $this->date_reminder_dao->duplicate($template_tracker_id, $new_tracker_id, $duplication_user_group_mapping, $field_mapping);
    }

    private function duplicateCalendarEventConfig(int $template_tracker_id, int $new_tracker_id): void
    {
        $this->calendar_event_config_dao->duplicate($template_tracker_id, $new_tracker_id);
    }
}
