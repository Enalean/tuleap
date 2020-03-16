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

use HTTPRequest;
use Tracker;

class NotificationLevelExtractor
{
    public function extractNotificationLevel(HTTPRequest $request)
    {
        if ($request->exist('disable_notifications')) {
            return Tracker::NOTIFICATIONS_LEVEL_DISABLED;
        } elseif ($request->exist('enable_notifications')) {
            return Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        }

        $notification_level = $request->get('notifications_level');
        if ((int) $notification_level !== Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE &&
            (int) $notification_level !== Tracker::NOTIFICATIONS_LEVEL_DISABLED) {
            return Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        }

        return $notification_level;
    }

    public function extractNotificationLevelFromString($notification_level)
    {
        if ($notification_level === Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE_LABEL) {
            return Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
        }

        if ($notification_level === Tracker::NOTIFICATIONS_LEVEL_DISABLED_LABEL) {
            return Tracker::NOTIFICATIONS_LEVEL_DISABLED;
        }

        return Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
    }
}
