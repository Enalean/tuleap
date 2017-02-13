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

namespace Tuleap\Git;

use RestrictedResourceDao;

class RestrictedGerritServerDao extends RestrictedResourceDao
{
    public function getResourceAllowedProjectsTableName()
    {
        return 'plugin_git_restricted_gerrit_servers_allowed_projects';
    }

    public function getResourceFieldName()
    {
        return 'gerrit_server_id';
    }

    public function unsetResourceRestricted($gerrit_server_id)
    {
        $gerrit_server_id = $this->da->escapeInt($gerrit_server_id);

        $sql = "DELETE FROM plugin_git_restricted_gerrit_servers WHERE gerrit_server_id = $gerrit_server_id";

        return $this->update($sql);
    }

    public function setResourceRestricted($gerrit_server_id)
    {
        $gerrit_server_id = $this->da->escapeInt($gerrit_server_id);

        $this->startTransaction();

        $sql = "REPLACE INTO plugin_git_restricted_gerrit_servers VALUES ($gerrit_server_id)";

        if ($this->update($sql)) {
            $sql = "REPLACE INTO plugin_git_restricted_gerrit_servers_allowed_projects
                    SELECT DISTINCT $gerrit_server_id, project_id
                    FROM plugin_git
                    WHERE remote_server_id = $gerrit_server_id";

            $this->update($sql);
            return $this->commit();
        }

        $this->rollBack();
        return false;
    }

    public function searchAllowedProjectsOnResource($gerrit_server_id)
    {
        $gerrit_server_id = $this->da->escapeInt($gerrit_server_id);

        $sql = "SELECT *
                FROM groups g
                INNER JOIN plugin_git_restricted_gerrit_servers_allowed_projects rgs ON g.group_id = rgs.project_id
                WHERE rgs.gerrit_server_id = $gerrit_server_id";

        return $this->retrieve($sql);
    }

    public function isResourceRestricted($gerrit_server_id)
    {
        $gerrit_server_id = $this->da->escapeInt($gerrit_server_id);

        $sql = "SELECT * FROM plugin_git_restricted_gerrit_servers WHERE gerrit_server_id = $gerrit_server_id";

        if ($this->retrieveFirstRow($sql)) {
            return true;
        }

        return false;
    }
}
