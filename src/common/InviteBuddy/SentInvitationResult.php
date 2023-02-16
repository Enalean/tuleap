<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

/**
 * @psalm-immutable
 */
final class SentInvitationResult
{
    /**
     * @param string[]  $failures
     * @param \PFUser[] $already_project_members
     * @param \PFUser[] $known_users_added_to_project_members
     * @param \PFUser[] $known_users_not_alive
     * @param \PFUser[] $known_users_are_restricted
     */
    public function __construct(
        public readonly array $failures,
        public readonly array $already_project_members,
        public readonly array $known_users_added_to_project_members,
        public readonly array $known_users_not_alive,
        public readonly array $known_users_are_restricted,
    ) {
    }
}
