<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\REST\Tests\API;

/**
 * @psalm-immutable
 * @psalm-type RESTUserGroup = array{
 *     id: string,
 *     uri: string,
 *     label: string,
 *     users_uri: string,
 *     key: string,
 *     short_name: string,
 *     additional_information: array{ ldap: ?string }
 * }
 * @template-implements \IteratorAggregate<int, RESTUserGroup>
 */
final readonly class UserGroupsRESTCollection implements \IteratorAggregate
{
    /**
     * @param list<RESTUserGroup> $user_groups
     */
    public function __construct(private array $user_groups)
    {
    }

    /**
     * @return RESTUserGroup
     */
    public function getUserGroupByShortName(string $short_name): array
    {
        foreach ($this->user_groups as $user_group) {
            if ($user_group['short_name'] === $short_name) {
                return $user_group;
            }
        }
        throw new \RuntimeException(sprintf('Could not find user group with short name "%s"', $short_name));
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->user_groups);
    }
}
