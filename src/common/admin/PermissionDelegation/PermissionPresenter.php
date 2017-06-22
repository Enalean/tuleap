<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Admin\PermissionDelegation;

use User_ForgeUserGroupPermission;

class PermissionPresenter
{
    public $permission;
    public $id;
    public $description;
    public $name;
    public $can_be_removed;
    public $cannot_remove_label;

    public function __construct(User_ForgeUserGroupPermission $permission)
    {
        $this->id                  = $permission->getId();
        $this->description         = $permission->getDescription();
        $this->name                = $permission->getName();
        $this->can_be_removed      = $permission->canBeRemoved();
        $this->cannot_remove_label = $permission->getCannotRemoveLabel();
    }
}
