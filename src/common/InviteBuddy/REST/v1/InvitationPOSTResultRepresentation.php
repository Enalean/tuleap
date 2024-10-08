<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy\REST\v1;

use Tuleap\InviteBuddy\SentInvitationResult;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
final class InvitationPOSTResultRepresentation
{
    /**
     * @param string[] $failures
     * @param UserRepresentation[] $already_project_members
     * @param UserRepresentation[] $known_users_added_to_project_members
     * @param UserRepresentation[] $known_users_not_alive
     * @param UserRepresentation[] $known_users_are_restricted
     */
    private function __construct(
        public array $failures,
        public array $already_project_members,
        public array $known_users_added_to_project_members,
        public array $known_users_not_alive,
        public array $known_users_are_restricted,
    ) {
    }

    public static function fromResult(SentInvitationResult $result, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        return new self(
            $result->failures,
            array_map(
                static fn(\PFUser $user): UserRepresentation => UserRepresentation::build($user, $provide_user_avatar_url),
                $result->already_project_members,
            ),
            array_map(
                static fn(\PFUser $user): UserRepresentation => UserRepresentation::build($user, $provide_user_avatar_url),
                $result->known_users_added_to_project_members,
            ),
            array_map(
                static fn(\PFUser $user): UserRepresentation => UserRepresentation::build($user, $provide_user_avatar_url),
                $result->known_users_not_alive,
            ),
            array_map(
                static fn(\PFUser $user): UserRepresentation => UserRepresentation::build($user, $provide_user_avatar_url),
                $result->known_users_are_restricted,
            ),
        );
    }
}
