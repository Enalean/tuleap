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

namespace Tuleap\Timetracking\Widget\Management;

use PFUser;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\User\RetrieveUserById;

final readonly class ViewableUserRetriever implements GetViewableUser
{
    public function __construct(
        private RetrieveUserById $retrieve_user,
        private VerifyManagerCanSeeTimetrackingOfUser $perms_verifier,
    ) {
    }

    #[\Override]
    public function getViewableUser(PFUser $current_user, int $user_id): Ok|Err
    {
        if ($current_user->isAnonymous()) {
            return Result::err(Fault::fromMessage('Anonymous users cannot retrieve PII'));
        }

        if ((int) $current_user->getId() === $user_id) {
            return Result::ok($current_user);
        }

        $user = $this->retrieve_user->getUserById($user_id);
        if (! $user || ! $user->isAlive()) {
            return Result::err(QueryInvalidUserIdFault::build($user_id));
        }

        if ($current_user->isSuperUser()) {
            return Result::ok($user);
        }

        if ($this->perms_verifier->isManagerAllowedToSeeTimetrackingOfUser($current_user, $user)) {
            return Result::ok($user);
        }

        return Result::err(NotAllowedToSeeTimetrackingOfUserFault::build($current_user, $user));
    }
}
