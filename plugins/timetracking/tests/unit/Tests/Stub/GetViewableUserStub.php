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

namespace Tuleap\Timetracking\Tests\Stub;

use PFUser;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Timetracking\Widget\Management\GetViewableUser;
use Tuleap\Timetracking\Widget\Management\NotAllowedToSeeTimetrackingOfUserFault;
use Tuleap\Timetracking\Widget\Management\QueryInvalidUserIdFault;

final class GetViewableUserStub implements GetViewableUser
{
    /**
     * @var array<int, PFUser>
     */
    private array $not_viewable_users = [];

    /**
     * @param array<int, PFUser> $users
     */
    public function __construct(private readonly array $users)
    {
    }

    #[\Override]
    public function getViewableUser(PFUser $current_user, int $user_id): Ok|Err
    {
        if (isset($this->not_viewable_users[$user_id])) {
            return Result::err(
                NotAllowedToSeeTimetrackingOfUserFault::build(
                    $current_user,
                    $this->not_viewable_users[$user_id],
                ),
            );
        }

        if (! isset($this->users[$user_id])) {
            return Result::err(QueryInvalidUserIdFault::build($user_id));
        }

        return Result::ok($this->users[$user_id]);
    }

    public static function withViewableUsers(PFUser ...$users): self
    {
        $users_by_id = [];
        foreach ($users as $user) {
            $users_by_id[(int) $user->getId()] = $user;
        }

        return new self($users_by_id);
    }

    public function andNotViewableUsers(PFUser ...$users): self
    {
        foreach ($users as $user) {
            $this->not_viewable_users[(int) $user->getId()] = $user;
        }

        return $this;
    }
}
