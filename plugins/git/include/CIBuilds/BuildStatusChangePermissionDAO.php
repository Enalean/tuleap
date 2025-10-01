<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\CIBuilds;

use Tuleap\DB\DataAccessObject;

class BuildStatusChangePermissionDAO extends DataAccessObject
{
    public function updateBuildStatusChangePermissionsForRepository(int $repository_id, string $granted_groups_ids): void
    {
        $sql = '
            INSERT INTO plugin_git_change_build_status_permissions (repository_id, granted_user_groups_ids)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE granted_user_groups_ids = ?
        ';

        $this->getDB()->run($sql, $repository_id, $granted_groups_ids, $granted_groups_ids);
    }

    public function searchBuildStatusChangePermissionsForRepository(int $repository_id): ?string
    {
        $sql = '
            SELECT granted_user_groups_ids
            FROM plugin_git_change_build_status_permissions
            WHERE repository_id = ?
        ';

        return $this->getDB()->cell($sql, $repository_id) ?: null;
    }
}
