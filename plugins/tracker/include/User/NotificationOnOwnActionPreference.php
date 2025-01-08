<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\User;

final class NotificationOnOwnActionPreference
{
    public const PREFERENCE_NAME = 'user_notifications_own_actions_tracker';
    public const VALUE_NO_NOTIF  = '0';
    public const VALUE_NOTIF     = '1';

    public static function userWantsNotification(\PFUser $user): bool
    {
        return $user->getPreference(self::PREFERENCE_NAME) !== self::VALUE_NO_NOTIF;
    }

    public static function updatePreference(\HTTPRequest $request, \PFUser $user): bool
    {
        $notification_on_own_action = $request->get(self::PREFERENCE_NAME) === self::VALUE_NOTIF;
        if (self::userWantsNotification($user)) {
            if (! $notification_on_own_action) {
                self::updateUserDoesNotWantNotification($user);
                return true;
            }
        } elseif ($notification_on_own_action) {
            self::updateUserWantsNotifications($user);
            return true;
        }
        return false;
    }

    public static function updateUserWantsNotifications(\PFUser $user): void
    {
        $user->setPreference(self::PREFERENCE_NAME, self::VALUE_NOTIF);
    }

    public static function updateUserDoesNotWantNotification(\PFUser $user): void
    {
        $user->setPreference(self::PREFERENCE_NAME, self::VALUE_NO_NOTIF);
    }
}
