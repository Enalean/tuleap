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
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;

class CollectionOfUGroupRepresentationBuilder
{
    /**
     * @var PermissionPerGroupUGroupRepresentationBuilder
     */
    private $ugroup_representation_builder;

    public function __construct(
        PermissionPerGroupUGroupRepresentationBuilder $ugroup_representation_builder
    ) {
        $this->ugroup_representation_builder = $ugroup_representation_builder;
    }

    public function build(Project $project, array $ugroup_ids)
    {
        $ugroups_representation = [];
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroups_representation[] = $this->ugroup_representation_builder->build($project, $ugroup_id);
        }

        return $ugroups_representation;
    }
}
