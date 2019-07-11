<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Binding;

use ProjectUGroup;

class RecursiveBoundUGroupsRefresher
{
    /** @var BoundUGroupRefresher */
    private $ugroup_refresher;
    /** @var \UGroupManager */
    private $ugroup_manager;

    public function __construct(BoundUGroupRefresher $ugroup_refresher, \UGroupManager $ugroup_manager)
    {
        $this->ugroup_refresher = $ugroup_refresher;
        $this->ugroup_manager   = $ugroup_manager;
    }

    /**
     * @throws \Exception
     */
    public function refreshUGroupAndBoundUGroups(ProjectUGroup $source, ProjectUGroup $destination): void
    {
        $this->ugroup_refresher->refresh($source, $destination);
        $destination_id = $destination->getId();
        $bound_ugroups  = $this->ugroup_manager->searchUGroupByBindingSource($destination_id);
        foreach ($bound_ugroups as $row) {
            $bound_ugroup = $this->ugroup_manager->getById($row['ugroup_id']);
            $this->refreshUGroupAndBoundUGroups($destination, $bound_ugroup);
        }
    }
}
