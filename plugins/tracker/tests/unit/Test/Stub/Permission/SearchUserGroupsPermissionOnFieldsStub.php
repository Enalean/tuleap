<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Permission;

use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\SearchUserGroupsPermissionOnFields;

final readonly class SearchUserGroupsPermissionOnFieldsStub implements SearchUserGroupsPermissionOnFields
{
    /**
     * @param list<int> $result
     */
    private function __construct(private array $result)
    {
    }

    public static function buildEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param list<int> $results
     */
    public static function buildWithResults(array $results): self
    {
        return new self($results);
    }

    #[\Override]
    public function searchUserGroupsPermissionOnFields(array $user_groups, array $fields_id, FieldPermissionType $permission): array
    {
        return $this->result;
    }
}
