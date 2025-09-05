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

namespace Tuleap\User\ForgeUserGroupPermission;

use User_ForgeUserGroupPermission;

class RestProjectManagementPermission extends User_ForgeUserGroupPermission
{
    public const ID = 8;

    #[\Override]
    public function getId()
    {
        return self::ID;
    }

    #[\Override]
    public function getName()
    {
        return _('REST projects management');
    }

    #[\Override]
    public function getDescription()
    {
        return _('This permission grants the right to manage projects through the REST api');
    }
}
