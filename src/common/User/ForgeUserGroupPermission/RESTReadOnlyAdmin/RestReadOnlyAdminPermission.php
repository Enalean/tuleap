<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin;

use User_ForgeUserGroupPermission;

class RestReadOnlyAdminPermission extends User_ForgeUserGroupPermission
{
    public const ID = 9;

    public function getId()
    {
        return self::ID;
    }

    public function getName()
    {
        return _('REST Read only administrator');
    }

    public function getDescription()
    {
        return _('This permission grants the user the right to see all the platform content through the REST API (GET/OPTIONS)');
    }
}
