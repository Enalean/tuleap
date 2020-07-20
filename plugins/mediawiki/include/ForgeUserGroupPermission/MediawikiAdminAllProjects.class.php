<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

namespace Tuleap\Mediawiki\ForgeUserGroupPermission;

use User_ForgeUserGroupPermission;

class MediawikiAdminAllProjects extends User_ForgeUserGroupPermission
{
    public const ID = 3;

    public function getId()
    {
        return self::ID;
    }

    public function getName()
    {
        return dgettext('tuleap-mediawiki', 'Global Mediawiki Administrator');
    }

    public function getDescription()
    {
        return dgettext('tuleap-mediawiki', 'This permission grants mediawiki administration rights for each mediawiki of every project');
    }
}
