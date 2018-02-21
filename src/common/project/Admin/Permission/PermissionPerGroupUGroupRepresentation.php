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

namespace Tuleap\Project\Admin\Permission;

use Tuleap\REST\JsonCast;

class PermissionPerGroupUGroupRepresentation
{
    /** @var string */
    public $ugroup_name;

    /** @var bool */
    public $is_project_admin;

    /** @var bool */
    public $is_static;

    /** @var bool */
    public $is_custom;

    public function __construct($name, $is_project_admin, $is_static, $is_custom)
    {
        $this->ugroup_name      = $name;
        $this->is_project_admin = JsonCast::toBoolean($is_project_admin);
        $this->is_static        = JsonCast::toBoolean($is_static);
        $this->is_custom        = JsonCast::toBoolean($is_custom);
    }
}
