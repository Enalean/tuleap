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

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentation;

class RepositoryFineGrainedRepresentation
{
    /**
     * @var bool
     */
    public $has_fined_grained_permissions;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $url;
    /**
     * @var FineGrainedPermissionRepresentation[]
     */
    public $fine_grained_permission;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $readers;

    public function __construct(
        array $readers,
        $name,
        $url,
        array $fine_grained_permission
    ) {
        $this->has_fined_grained_permissions = true;
        $this->name                          = $name;
        $this->url                           = $url;
        $this->fine_grained_permission       = $fine_grained_permission;
        $this->readers                       = $readers;
    }
}
