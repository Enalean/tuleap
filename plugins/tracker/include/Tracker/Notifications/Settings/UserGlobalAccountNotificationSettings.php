<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Settings;

use Tuleap\Tracker\User\NotificationOnAllUpdatesPreference;
use Tuleap\Tracker\User\NotificationOnAllUpdatesRetriever;
use Tuleap\Tracker\User\NotificationOnOwnActionPreference;
use Tuleap\Tracker\User\NotificationOnOwnActionRetriever;
use Tuleap\User\ProvideCurrentUser;

final readonly class UserGlobalAccountNotificationSettings
{
    private function __construct(
        public NotificationOnAllUpdatesPreference $notification_on_all_update,
        public NotificationOnOwnActionPreference $notification_on_my_own_action,
    ) {
    }

    public static function build(
        ProvideCurrentUser $user_manager,
        NotificationOnAllUpdatesRetriever $notification_on_all_update,
        NotificationOnOwnActionRetriever $notification_on_own_action,
    ): self {
        $current_user                  = $user_manager->getCurrentUser();
        $notification_on_all_update    = $notification_on_all_update->retrieve($current_user);
        $notification_on_my_own_action = $notification_on_own_action->retrieve($current_user);

        return new self($notification_on_all_update, $notification_on_my_own_action);
    }
}
