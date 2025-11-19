<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Project\UGroups;

use ProjectUGroup;
use User_ForgeUGroup;

final class UserGroupsPresenterBuilder
{
    /**
     * @param User_ForgeUGroup[] $project_ugroups
     * @param array<int, true> $selected
     * @return list<array{id: int, name: string, selected: bool}>
     */
    public function getUgroups(array $project_ugroups, array $selected): array
    {
        $options = [];
        foreach ($project_ugroups as $project_ugroup) {
            if ($project_ugroup->getId() > 100 || in_array($project_ugroup->getId(), [ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN], true)) {
                $options[] = [
                    'id' => $project_ugroup->getId(),
                    'name' => $project_ugroup->getName(),
                    'selected' => isset($selected[$project_ugroup->getId()]),
                ];
            }
        }

        return $options;
    }
}
