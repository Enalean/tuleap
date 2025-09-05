<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ForgeUserGroupPermission_ProjectApproval extends User_ForgeUserGroupPermission
{
    public const ID = 1;

    #[\Override]
    public function getId()
    {
        if (self::ID) {
            return self::ID;
        }
    }

    #[\Override]
    public function getName()
    {
        return $GLOBALS['Language']->getText('usergroup_forge_permission', 'project_approval_name');
    }

    #[\Override]
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('usergroup_forge_permission', 'project_approval_description');
    }
}
