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

namespace Tuleap\ProgramManagement\Domain\Workspace;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

/**
 * @psalm-immutable
 */
final class UserGroup
{
    public int $id;
    public bool $is_created_by_user;
    public string $translated_name;

    private function __construct(int $id, bool $is_created_by_user, string $translated_name)
    {
        $this->id                 = $id;
        $this->is_created_by_user = $is_created_by_user;
        $this->translated_name    = $translated_name;
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromProgram(RetrieveUGroups $user_group_retriever, ProgramForAdministrationIdentifier $program): array
    {
        $user_groups = $user_group_retriever->getUgroupsFromProgram($program);
        return array_map(
            static fn(UserGroupAttributes $ugroup): self => new self($ugroup->getId(), $ugroup->isCreatedByUser(), $ugroup->getTranslatedName()),
            $user_groups
        );
    }

    public static function fromName(RetrieveUGroups $user_group_retriever, ProgramForAdministrationIdentifier $program, string $name): ?self
    {
        $ugroup = $user_group_retriever->getUGroupByNameInProgram($program, $name);
        if (! $ugroup) {
            return null;
        }
        return new self($ugroup->getId(), $ugroup->isCreatedByUser(), $ugroup->getTranslatedName());
    }
}
