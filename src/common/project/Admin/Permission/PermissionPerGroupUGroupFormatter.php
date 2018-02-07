<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\PerGroup;

use Project;
use ProjectUGroup;
use UGroupManager;

class PermissionPerGroupUGroupFormatter
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function formatGroup(Project $project, $group)
    {
        $user_group = $this->ugroup_manager->getUGroup($project, $group);

        $formatted_group = array(
            'is_project_admin' => $this->isProjectAdmin($user_group),
            'is_static'        => $user_group->isStatic(),
            'is_custom'        => ! $this->isProjectAdmin($user_group) && ! $user_group->isStatic(),
            'name'             => $user_group->getTranslatedName()
        );

        return $formatted_group;
    }

    /**
     * @param $user_group
     *
     * @return bool
     */
    private function isProjectAdmin(ProjectUGroup $user_group)
    {
        return (int) $user_group->getId() === ProjectUGroup::PROJECT_ADMIN;
    }
}
