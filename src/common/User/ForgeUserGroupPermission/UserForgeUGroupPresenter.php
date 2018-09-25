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

namespace Tuleap\User\ForgeUserGroupPermission;

use User_ForgeUGroup;

class UserForgeUGroupPresenter
{
    public $name;
    public $cannot_remove_label;
    public $description;
    public $id;
    public $can_be_removed;

    public function __construct(User_ForgeUGroup $group, $can_be_removed)
    {
        $this->id                  = $group->getId();
        $this->name                = $group->getName();
        $this->description         = $group->getDescription();
        $this->can_be_removed      = $can_be_removed;
        $this->cannot_remove_label = _('You can\'t remove the last group containing site administrator permissions.');
    }

    public function has_description()
    {
        return $this->description !== '';
    }
}
