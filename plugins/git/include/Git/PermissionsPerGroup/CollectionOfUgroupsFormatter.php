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

namespace Tuleap\Git\PermissionsPerGroup;

use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use UGroupManager;

class CollectionOfUgroupsFormatter
{
    /** @var PermissionPerGroupUGroupFormatter */
    private $formatter;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(PermissionPerGroupUGroupFormatter $formatter, UGroupManager $ugroup_manager)
    {
        $this->formatter      = $formatter;
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @param array $ugroup_ids
     * @return array
     */
    public function formatCollectionOfUgroupIds(array $ugroup_ids, Project $project)
    {
        return $this->formatter->getFormattedUGroups($project, $ugroup_ids);
    }

    /**
     * @param ProjectUGroup[] $ugroups
     * @return array
     */
    public function formatCollectionOfUgroups(array $ugroups, Project $project)
    {
        $formatted_permissions = [];
        foreach ($ugroups as $ugroup) {
            $user_group = $this->ugroup_manager->getUGroup($project, $ugroup->getId());
            if ($user_group) {
                $formatted_permissions[] =  $this->formatter->formatGroup($user_group);
            }
        }
        return $formatted_permissions;
    }
}
