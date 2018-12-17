<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Statistics;

use Tuleap\DB\DataAccessObject;

class LFSStatisticsDAO extends DataAccessObject
{
    /**
     * @return int
     */
    public function getOccupiedSizeByProjectIDAndExpiration($project_id, $current_time)
    {
        $sql = 'SELECT COALESCE(SUM(size), 0) AS size
                FROM (
                    SELECT plugin_gitlfs_object.object_size AS size
                    FROM plugin_gitlfs_object
                    JOIN plugin_gitlfs_object_repository
                    ON (plugin_gitlfs_object_repository.object_id = plugin_gitlfs_object.id)
                    JOIN plugin_git ON (plugin_git.repository_id = plugin_gitlfs_object_repository.repository_id)
                    WHERE plugin_git.project_id = ?
                    GROUP BY plugin_gitlfs_object.id
                UNION ALL
                    SELECT MAX(plugin_gitlfs_authorization_action.object_size) AS size
                    FROM plugin_gitlfs_authorization_action
                    JOIN plugin_git ON (plugin_git.repository_id = plugin_gitlfs_authorization_action.repository_id)
                    WHERE plugin_git.project_id = ? AND expiration_date >= ? AND plugin_gitlfs_authorization_action.object_oid NOT IN (
                        SELECT plugin_gitlfs_object.object_oid
                        FROM plugin_gitlfs_object
                        JOIN plugin_gitlfs_object_repository ON (plugin_gitlfs_object_repository.object_id = plugin_gitlfs_object.id)
                        JOIN plugin_git ON (plugin_git.repository_id = plugin_gitlfs_object_repository.repository_id)
                        WHERE plugin_git.project_id = ?
                    ) GROUP BY plugin_gitlfs_authorization_action.object_oid
                ) AS all_object_size';

        return (int) $this->getDB()->single($sql, [$project_id, $project_id, $current_time, $project_id]);
    }
}
