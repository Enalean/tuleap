<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions;

final class ISearchByProjectStub implements ISearchByProject
{
    private function __construct(
        private array $permissions,
    ) {
    }

    public static function buildWithPermissions(
        array $readers_ugroup_ids,
        array $writers_ugroup_ids,
        array $admins_ugroup_ids,
    ): self {
        return new self(
            array_merge(
                array_map(static fn($id): array => ['ugroup_id' => $id, 'permission' => PermissionRead::NAME], $readers_ugroup_ids),
                array_map(static fn($id): array => ['ugroup_id' => $id, 'permission' => PermissionWrite::NAME], $writers_ugroup_ids),
                array_map(static fn($id): array => ['ugroup_id' => $id, 'permission' => PermissionAdmin::NAME], $admins_ugroup_ids),
            )
        );
    }

    public static function buildWithoutSpecificPermissions(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchByProject(\Project $project): array
    {
        return $this->permissions;
    }
}
