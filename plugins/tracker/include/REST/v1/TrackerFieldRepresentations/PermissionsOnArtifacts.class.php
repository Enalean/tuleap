<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\REST\v1\TrackerFieldsRepresentations;

use Tuleap\Project\REST\MinimalUserGroupRepresentation;

class PermissionsOnArtifacts
{

    public $is_used_by_default;

    /**
     * @var MinimalUserGroupRepresentation[]
     */
    public $ugroup_representations;

    public function build($project_id, $is_used_by_default, array $ugroups)
    {
        $ugroup_representations = [];

        foreach ($ugroups as $user_group) {
            $ugroup_representation    = new MinimalUserGroupRepresentation((int) $project_id, $user_group);
            $ugroup_representations[] = $ugroup_representation;
        }

        $this->is_used_by_default     = $is_used_by_default;
        $this->ugroup_representations = $ugroup_representations;
    }
}
