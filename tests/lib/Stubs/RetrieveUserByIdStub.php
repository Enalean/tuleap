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

namespace Tuleap\Test\Stubs;

final class RetrieveUserByIdStub implements \Tuleap\User\RetrieveUserById
{
    /**
     * @param array<int, \PFUser> $users
     */
    private function __construct(private array $users)
    {
    }

    public static function withUser(\PFUser $user): self
    {
        return self::withUsers($user);
    }

    public static function withUsers(\PFUser $first_user, \PFUser ...$other_users): self
    {
        return new self(array_reduce(
            [$first_user, ...$other_users],
            /**
             * @return array<int, \PFUser>
             */
            static function (array $accumulator, \PFUser $user): array {
                $accumulator[(int) $user->getId()] = $user;

                return $accumulator;
            },
            []
        ));
    }

    public static function withNoUser(): self
    {
        return new self([]);
    }

    public function getUserById($user_id): ?\PFUser
    {
        return $this->users[$user_id] ?? null;
    }
}
