<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UserList;

use PFUser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;
use Tuleap\User\ProvideCurrentUser;
use UserHelper;

final readonly class UserOrderByBuilder
{
    public function __construct(
        private ProvideCurrentUser $current_user_provider,
    ) {
    }

    public function getOrderByForUsers(string $user_alias, OrderByDirection $direction): string
    {
        $preference = (int) $this->current_user_provider->getCurrentUser()->getPreference(PFUser::PREFERENCE_NAME_DISPLAY_USERS) ?: 0;

        $concat_value = match ($preference) {
            UserHelper::PREFERENCES_LOGIN          => "$user_alias.user_name",
            UserHelper::PREFERENCES_REAL_NAME      => "$user_alias.realname",
            UserHelper::PREFERENCES_LOGIN_AND_NAME => "$user_alias.user_name, ' (', $user_alias.realname, ')'",
            UserHelper::PREFERENCES_NAME_AND_LOGIN => "$user_alias.realname, ' (', $user_alias.user_name, ')'",
        };

        return "CONCAT($concat_value) $direction->value";
    }
}
