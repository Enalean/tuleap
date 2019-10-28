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

namespace Tuleap\FRS\PermissionsPerGroup;

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentation;

class PackagePermissionPerGroupReleaseRepresentation
{
    /**
     * @var string
     */
    public $release_url;

    /**
     * @var string
     */
    public $release_name;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $release_permissions;

    public function __construct(
        $release_url,
        $release_name,
        array $release_permissions
    ) {
        $this->release_url         = $release_url;
        $this->release_name        = $release_name;
        $this->release_permissions = $release_permissions;
    }
}
