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

namespace Tuleap\Git\PerGroup;

use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;

class CollectionOfUgroupsFormatter
{
    /** @var PermissionPerGroupUGroupFormatter */
    private $formatter;

    /**
     * @param PermissionPerGroupUGroupFormatter $formatter
     */
    public function __construct(PermissionPerGroupUGroupFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param array $ugroup_ids
     * @param Project $project
     * @return array
     */
    public function formatCollectionOfUgroupIds(array $ugroup_ids, Project $project)
    {
        $formatted_permissions = [];
        foreach ($ugroup_ids as $ugroup_id) {
            $formatted_permissions[] = $this->formatter->formatGroup($project, $ugroup_id);
        }
        return $formatted_permissions;
    }

    /**
     * @param ProjectUGroup[] $ugroups
     * @param Project $project
     * @return array
     */
    public function formatCollectionOfUgroups(array $ugroups, Project $project)
    {
        $formatted_permissions = [];
        foreach ($ugroups as $ugroup) {
            $formatted_permissions[] = $this->formatter->formatGroup($project, $ugroup->getId());
        }
        return $formatted_permissions;
    }
}
