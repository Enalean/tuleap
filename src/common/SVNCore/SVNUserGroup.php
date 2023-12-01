<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore;

/**
 * @psalm-immutable
 */
final class SVNUserGroup
{
    public const MEMBERS = 'members';

    /**
     * @var SVNUser[]
     */
    public readonly array $users;

    private function __construct(public readonly string $name, SVNUser ...$users)
    {
        $this->users = $users;
    }

    public static function fromUserGroup(\ProjectUGroup $user_group): self
    {
        $group_name = $user_group->getName();
        if ($user_group->getId() === \ProjectUGroup::PROJECT_MEMBERS) {
            $group_name = self::MEMBERS;
        }
        return new self($group_name, ...array_map(static fn (\PFUser $user) => SVNUser::fromUser($user), $user_group->getMembers()));
    }

    public static function fromUserGroupAndMembers(\ProjectUGroup $user_group, SVNUser ...$users): self
    {
        return new self(self::getGroupName($user_group), ...$users);
    }

    private static function getGroupName(\ProjectUGroup $user_group): string
    {
        if ($user_group->getId() === \ProjectUGroup::PROJECT_MEMBERS) {
            return self::MEMBERS;
        }
        return $user_group->getName();
    }
}
