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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\REST\v1;

class ServiceRepresentation
{
    public const ROUTE = 'frs_service';

    /**
     * @var ServicePermissionsForGroupsRepresentation {@required false} {@type \Tuleap\FRS\REST\v1\ServicePermissionsForGroupsRepresentation}
     * @psalm-var ServicePermissionsForGroupsRepresentation|null
     */
    public $permissions_for_groups;

    public function build(ServicePermissionsForGroupsRepresentation $permission_for_groups): self
    {
        $this->permissions_for_groups = $permission_for_groups;
        return $this;
    }
}
