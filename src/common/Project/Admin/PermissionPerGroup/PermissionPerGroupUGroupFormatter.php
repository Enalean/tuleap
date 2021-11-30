<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use Project;
use ProjectUGroup;

class PermissionPerGroupUGroupFormatter
{
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;

    public function __construct(\UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getFormattedUGroups(Project $project, array $ugroups_ids)
    {
        $formatted_ugroups = [];
        foreach ($ugroups_ids as $ugroup_id) {
            $user_group = $this->ugroup_manager->getUGroup($project, $ugroup_id);
            if ($user_group) {
                $formatted_ugroups[] = $this->formatGroup($user_group);
            }
        }

        return $formatted_ugroups;
    }

    public function formatGroup(ProjectUGroup $user_group)
    {
        return [
            'is_project_admin' => $this->isProjectAdmin($user_group),
            'is_static'        => $user_group->isStatic(),
            'is_custom'        => ! $this->isProjectAdmin($user_group) && ! $user_group->isStatic(),
            'name'             => $user_group->getTranslatedName(),
        ];
    }

    /**
     * @param $user_group
     *
     * @return bool
     */
    private function isProjectAdmin(ProjectUGroup $user_group)
    {
        return $user_group->getId() === ProjectUGroup::PROJECT_ADMIN;
    }
}
