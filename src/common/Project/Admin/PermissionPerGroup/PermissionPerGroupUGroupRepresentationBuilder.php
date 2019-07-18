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
use Tuleap\Project\UGroups\InvalidUGroupException;
use UGroupManager;

class PermissionPerGroupUGroupRepresentationBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function build(Project $project, $user_group_id)
    {
        $user_group = $this->ugroup_manager->getUGroup($project, $user_group_id);
        if (! $user_group) {
            throw new InvalidUGroupException($user_group_id);
        }
        $is_custom = ! $this->isProjectAdmin($user_group) && ! $user_group->isStatic();

        return new PermissionPerGroupUGroupRepresentation(
            $user_group->getTranslatedName(),
            $this->isProjectAdmin($user_group),
            $user_group->isStatic(),
            $is_custom
        );
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
