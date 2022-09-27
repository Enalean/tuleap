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

namespace Tuleap\Gitlab\Permission;

use Tuleap\Git\Permissions\VerifyUserIsGitAdministrator;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class GitAdministratorChecker
{
    public function __construct(private VerifyUserIsGitAdministrator $admin_verifier)
    {
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    public function checkUserIsGitAdministrator(\Project $project, \PFUser $user): Ok|Err
    {
        if (! $this->admin_verifier->userIsGitAdmin($user, $project)) {
            return Result::err(UserIsNotGitAdministratorFault::build());
        }
        return Result::ok(null);
    }
}
