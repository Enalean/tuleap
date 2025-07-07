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
use Tuleap\Timetracking\Widget\Management\GetViewableUser;

final readonly class FromPayloadUserListBuilder
{
    public function __construct(
        private GetViewableUser $check_that_user_is_active,
    ) {
    }

    /**
     * @param QueryUserRepresentation[] $users
     *
     * @return Ok<UserList>|Err<Fault>
     */
    public function getUserList(\PFUser $current_user, array $users): Ok|Err
    {
        $user_ids = $this->extractUserIds($users);

        foreach ($user_ids as $user_id) {
            if (! $this->isUserIdValid($current_user, $user_id)) {
                return Result::err(QueryInvalidUserIdFault::build($user_id));
            }
        }

        return Result::ok(new UserList($user_ids));
    }

    /**
     * @param QueryUserRepresentation[] $users
     */
    private function extractUserIds(array $users): array
    {
        $user_ids = [];
        foreach ($users as $user_representation) {
            $user_ids[$user_representation->id] = true;
        }

        return array_keys($user_ids);
    }

    private function isUserIdValid(\PFUser $current_user, int $user_id): bool
    {
        $user = $this->check_that_user_is_active->getViewableUser($current_user, $user_id);

        return $user !== null;
    }
}
