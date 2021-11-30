<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

namespace Tuleap\Tracker\PermissionsPerGroup;

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentation;

class TrackerPermissionPerGroupPermissionRepresentation
{
    /**
     * @var String
     */
    public $permission_name;

    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $granted_groups;

    public function __construct(
        $permission_name,
        array $granted_groups,
    ) {
        $this->permission_name = $permission_name;
        $this->granted_groups  = $granted_groups;
    }
}
