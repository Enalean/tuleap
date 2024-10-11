<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\User\Admin;

use PFUser;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

/**
 * @psalm-immutable
 */
final class UserPresenter
{
    private function __construct(public string $display_name, public string $avatar_url, public bool $has_avatar)
    {
    }

    public static function fromUser(PFUser $user, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        return new self(
            \UserHelper::instance()->getDisplayNameFromUser($user),
            $provide_user_avatar_url->getAvatarUrl($user),
            $user->hasAvatar(),
        );
    }
}
