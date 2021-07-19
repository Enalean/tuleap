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
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUGroups;

final class UGroupManagerAdapter implements RetrieveUGroups
{
    private \ProjectManager $project_manager;
    private \UGroupManager $group_manager;

    public function __construct(\ProjectManager $project_manager, \UGroupManager $group_manager)
    {
        $this->project_manager = $project_manager;
        $this->group_manager   = $group_manager;
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getUgroupsFromProjectId(int $project_id): array
    {
        $project = $this->project_manager->getProject($project_id);
        return $this->group_manager->getUGroups($project, ProjectUGroup::SYSTEM_USER_GROUPS);
    }
}
