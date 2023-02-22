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

namespace Tuleap\InviteBuddy\Admin;

/**
 * @psalm-immutable
 */
final class InvitedByPresenter
{
    public readonly bool $has_been_invited;
    public readonly bool $has_been_invited_by_only_one;

    /**
     * @param InvitedByUserPresenter[] $invited_by_users
     */
    public function __construct(
        public readonly array $invited_by_users,
        public readonly bool $has_used_an_invitation_to_register,
    ) {
        $this->has_been_invited             = count($invited_by_users) > 0;
        $this->has_been_invited_by_only_one = count($invited_by_users) === 1;
    }
}
