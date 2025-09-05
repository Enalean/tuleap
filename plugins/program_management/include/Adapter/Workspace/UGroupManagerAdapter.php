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

use ProjectUGroup;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUGroups;
use Tuleap\ProgramManagement\Domain\Workspace\UserGroupAttributes;

final class UGroupManagerAdapter implements RetrieveUGroups
{
    public function __construct(private RetrieveFullProject $retrieve_full_project, private \UGroupManager $group_manager)
    {
    }

    /**
     * @return UserGroupAttributes[]
     */
    #[\Override]
    public function getUgroupsFromProgram(ProgramForAdministrationIdentifier $program_identifier): array
    {
        $project = $this->retrieve_full_project->getProject($program_identifier->id);

        $list = [];
        foreach ($this->group_manager->getUGroups($project, ProjectUGroup::SYSTEM_USER_GROUPS) as $ugroup) {
            $list[] = UserGroupProxy::fromProjectUGroup($ugroup);
        }

        return $list;
    }

    #[\Override]
    public function getUGroupByNameInProgram(ProgramForAdministrationIdentifier $program_identifier, string $ugroup_name): ?UserGroupAttributes
    {
        $project = $this->retrieve_full_project->getProject($program_identifier->id);
        $ugroup  = $this->group_manager->getUGroupByName($project, $ugroup_name);

        if (! $ugroup) {
            return null;
        }

        return UserGroupProxy::fromProjectUGroup($ugroup);
    }
}
