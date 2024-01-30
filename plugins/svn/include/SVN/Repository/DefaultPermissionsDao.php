<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\Repository;

use Tuleap\DB\DataAccessObject;
use Tuleap\SVNCore\Repository;

final class DefaultPermissionsDao extends DataAccessObject implements DefaultPermissions
{
    public function enableUseDefaultPermissions(Repository $repository): void
    {
        $this->getDB()->update('plugin_svn_repositories', ['has_default_permissions' => 1], ['id' => $repository->getId()]);
    }

    public function disableUseDefaultPermissions(Repository $repository): void
    {
        $this->getDB()->update('plugin_svn_repositories', ['has_default_permissions' => 0], ['id' => $repository->getId()]);
    }
}
