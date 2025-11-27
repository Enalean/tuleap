<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use PFUser;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use UserHelper;

final class UserInvolvedInTrackerNotificationPresenter
{
    private function __construct(
        public readonly int $user_id,
        public readonly string $display_name,
        public readonly string $avatar_url,
        public readonly string $user_url,
    ) {
    }

    public static function fromPFUser(PFUser $user, UserHelper $helper, ProvideUserAvatarUrl $avatar_url_provider): self
    {
        return new self(
            (int) $user->getId(),
            $helper->getDisplayName($user->getUserName(), $user->getRealName()),
            $avatar_url_provider->getAvatarUrl($user),
            $helper->getUserUrl($user),
        );
    }
}
