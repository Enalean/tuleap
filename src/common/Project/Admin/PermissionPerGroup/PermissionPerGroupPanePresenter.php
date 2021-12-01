<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use ProjectUGroup;

class PermissionPerGroupPanePresenter
{
    public $permissions;
    public $has_permissions;
    public $user_group_name;

    public function __construct(
        array $permissions,
        ?ProjectUGroup $selected_ugroup = null,
    ) {
        $this->permissions     = $permissions;
        $this->has_permissions = count($permissions) > 0;
        $this->user_group_name = ($selected_ugroup)
            ? $selected_ugroup->getTranslatedName()
            : '';
    }
}
