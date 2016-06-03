<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use DataAccessObject;

class FineGrainedDao extends DataAccessObject
{
    public function enableRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "REPLACE INTO plugin_git_repository_fine_grained_permissions_enabled (repository_id)
                VALUES ($repository_id)";

        return $this->update($sql);
    }

    public function disableRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "DELETE FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = $repository_id";

        return $this->update($sql);
    }

    public function searchRepositoryUseFineGrainedPermissions($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT repository_id
                FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = $repository_id";

        return $this->retrieveFirstRow($sql);
    }
}
