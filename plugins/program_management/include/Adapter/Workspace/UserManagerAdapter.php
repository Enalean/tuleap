<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyIsUser;

final class UserManagerAdapter implements RetrieveUser, VerifyIsUser
{
    private \UserManager $user_manager;

    public function __construct(\UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    #[\Override]
    public function getUserWithId(UserIdentifier $user_identifier): \PFUser
    {
        $user_id = $user_identifier->getId();
        $user    = $this->user_manager->getUserById($user_id);

        if (! $user) {
            throw new UserNotFoundException($user_id);
        }

        return $user;
    }

    #[\Override]
    public function isUser(int $user_id): bool
    {
        $user = $this->user_manager->getUserById($user_id);
        return $user !== null;
    }
}
