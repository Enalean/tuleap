<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class FromPayloadUserListBuilder
{
    public function __construct(
        private GetActiveUser $check_that_user_is_active,
    ) {
    }

    /**
     * @return Ok<UserList>|Err<Fault>
     */
    public function getUserList(array $users): Ok|Err
    {
        $user_ids = [];
        foreach ($users as $user_representation) {
            $user_ids[] = $user_representation['id'];
        }

        foreach ($user_ids as $user_id) {
            $user = $this->check_that_user_is_active->getActiveUser($user_id);
            if (! $user) {
                return Result::err(QueryInvalidUserIdFault::build($user_id));
            }
        }

        return Result::ok(new UserList($user_ids));
    }
}
