<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

use PFUser;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\User\RetrieveUserByUserName;

final class ProvideAndRetrieveUserStub implements ProvideCurrentUser, RetrieveUserByUserName
{
    /**
     * @var PFUser[]
     */
    private array $users = [];

    private function __construct(
        private PFUser $current_user,
    ) {
    }

    public static function build(PFUser $current_user): self
    {
        return new self($current_user);
    }

    /**
     * @param PFUser[] $users
     */
    public function withUsers(array $users): self
    {
        $this->users = $users;

        return $this;
    }

    public function getCurrentUser(): PFUser
    {
        return $this->current_user;
    }

    public function getUserByUserName(string $user_name): PFUser|null
    {
        foreach ($this->users as $user) {
            if ($user->getUserName() === $user_name) {
                return $user;
            }
        }

        return null;
    }
}
