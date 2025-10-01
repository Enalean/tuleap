<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

namespace Tuleap\MediawikiStandalone\Permissions\ForgeUserGroupPermission;

use User_ForgeUserGroupPermission;

/**
 * @psalm-immutable
 */
final class MediawikiAdminAllProjects extends User_ForgeUserGroupPermission
{
    public const int ID = 3;

    #[\Override]
    public function getId()
    {
        return self::ID;
    }

    #[\Override]
    public function getName()
    {
        return dgettext('tuleap-mediawiki_standalone', 'Global MediaWiki Administrator');
    }

    #[\Override]
    public function getDescription()
    {
        return dgettext('tuleap-mediawiki_standalone', 'This permission grants MediaWiki administration rights for each MediaWiki of every project');
    }
}
