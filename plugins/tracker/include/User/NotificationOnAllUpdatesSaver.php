<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\User;

use PFUser;
use Tuleap\User\StoreUserPreference;

final readonly class NotificationOnAllUpdatesSaver
{
    public const PREFERENCE_NAME = 'user_notifications_all_updates_tracker';
    public const VALUE_NO_NOTIF  = '0';
    public const VALUE_NOTIF     = '1';

    public function __construct(
        private NotificationOnAllUpdatesRetriever $retriever,
        private StoreUserPreference $dao,
    ) {
    }

    /** returns true if the preference changed */
    public function save(NotificationOnAllUpdatesPreference $new_preference, PFUser $user): bool
    {
        $old_preference = $this->retriever->retrieve($user);
        if (! $new_preference->hasChanged($old_preference)) {
            return false;
        }
        $value = $new_preference->enabled ? self::VALUE_NOTIF : self::VALUE_NO_NOTIF;
        $this->dao->set((int) $user->getId(), self::PREFERENCE_NAME, $value);
        return true;
    }
}
