<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\FRS\PerGroup;

use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRepresentation;

class PackagePermissionPerGroupRepresentation
{
    /**
     * @var string
     */
    public $package_url;

    /**
     * @var string
     */
    public $package_name;

    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $permissions;

    /**
     * @var PackagePermissionPerGroupReleaseRepresentation[]
     */
    public $releases;

    public function __construct(
        $package_url,
        $package_name,
        array $permissions,
        array $releases
    ) {
        $this->package_url  = $package_url;
        $this->package_name = $package_name;
        $this->permissions  = $permissions;
        $this->releases     = $releases;
    }
}
