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
 */

declare(strict_types=1);

namespace Tuleap\User;

/**
 * @psalm-immutable
 */
final class CurrentUserWithLoggedInInformation
{
    private function __construct(public \PFUser $user, public bool $is_logged_in)
    {
    }

    public static function fromLoggedInUser(\PFUser $user): self
    {
        if ($user->isAnonymous()) {
            throw new \LogicException('An anonymous user cannot be logged in');
        }
        return new self($user, true);
    }

    public static function fromAnonymous(ProvideAnonymousUser $anonymous_user_provider): self
    {
        return new self($anonymous_user_provider->getUserAnonymous(), false);
    }
}
