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

namespace Tuleap\Tracker\Notifications;

use Tracker;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettings;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsDAO;

class NotificationsForceUsageUpdater
{
    /**
     * @var RecipientsManager
     */
    private $recipients_manager;
    /**
     * @var UserNotificationSettings
     */
    private $notification_settings;

    public function __construct(RecipientsManager $recipients_manager, UserNotificationSettingsDAO $notification_settings)
    {
        $this->recipients_manager    = $recipients_manager;
        $this->notification_settings = $notification_settings;
    }

    public function forceUserPreferences(Tracker $tracker, $new_notifications_level)
    {
        foreach ($this->recipients_manager->getAllRecipientsWhoHaveCustomSettingsForATracker($tracker) as $user_id) {
            if ((int) $new_notifications_level === Tracker::NOTIFICATIONS_LEVEL_DEFAULT) {
                $this->notification_settings->enableNoGlobalNotificationMode($user_id, $tracker->getId());
            }

            if ((int) $new_notifications_level === Tracker::NOTIFICATIONS_LEVEL_DISABLED) {
                $this->notification_settings->enableNoNotificationAtAllMode($user_id, $tracker->getId());
            }

            if ((int) $new_notifications_level === Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE) {
                $this->notification_settings->enableNotifyOnStatusChangeMode($user_id, $tracker->getId());
            }
        }
    }
}
