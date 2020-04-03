<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use Project;
use ProjectUGroup;
use UGroupManager;

class UGroupRetrieverWithLegacy
{
    private const LEGACY_SYSTEM_USER_GROUPS = [
        'UGROUP_NONE'               => ProjectUGroup::NONE,
        'UGROUP_ANONYMOUS'          => ProjectUGroup::ANONYMOUS,
        'UGROUP_REGISTERED'         => ProjectUGroup::REGISTERED,
        'UGROUP_AUTHENTICATED'      => ProjectUGroup::AUTHENTICATED,
        'UGROUP_PROJECT_MEMBERS'    => ProjectUGroup::PROJECT_MEMBERS,
        'UGROUP_PROJECT_ADMIN'      => ProjectUGroup::PROJECT_ADMIN,
        'UGROUP_FILE_MANAGER_ADMIN' => ProjectUGroup::FILE_MANAGER_ADMIN,
        'UGROUP_WIKI_ADMIN'         => ProjectUGroup::WIKI_ADMIN,
        'UGROUP_TRACKER_ADMIN'      => ProjectUGroup::TRACKER_ADMIN,
    ];

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getUGroupId(Project $project, string $ugroup_name): ?int
    {
        if (isset(self::LEGACY_SYSTEM_USER_GROUPS[$ugroup_name])) {
            $ugroup_id = self::LEGACY_SYSTEM_USER_GROUPS[$ugroup_name];
        } else {
            $ugroup = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);
            if (is_null($ugroup)) {
                $ugroup_id = null;
            } else {
                $ugroup_id = $ugroup->getId();
            }
        }

        return $ugroup_id;
    }

    /**
     * @return int[]
     */
    public function getProjectUgroupIds(Project $project): array
    {
        $ugroups        = self::LEGACY_SYSTEM_USER_GROUPS;
        $static_groups  = $this->ugroup_manager->getStaticUGroups($project);

        foreach ($static_groups as $ugroup) {
            $ugroups[$ugroup->getName()] = (int) $ugroup->getId();
        }

        return $ugroups;
    }
}
