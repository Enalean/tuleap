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

namespace Tuleap\Git\BigObjectAuthorization;

use ParagonIE\EasyDB\EasyStatement;

class BigObjectAuthorizationDao extends \Tuleap\DB\DataAccessObject
{

    public function authorizeProject($project_id)
    {
        $this->getDB()->run(
            "INSERT INTO plugin_git_big_object_authorized_project
            VALUES (?)
            ON DUPLICATE KEY UPDATE
            project_id=project_id",
            $project_id
        );
    }

    public function revokeProjectAuthorization(array $project_ids)
    {
        if (empty($project_ids)) {
            return;
        }

        $conditions = EasyStatement::open();

        foreach ($project_ids as $project_id) {
            $conditions->orWith('project_id = ?', $project_id);
        }

        $this->getDB()->safeQuery(
            "DELETE FROM plugin_git_big_object_authorized_project
            WHERE $conditions",
            $conditions->values()
        );
    }

    /**
     * @return array
     */
    public function getAuthorizedProjects()
    {
        return $this->getDB()->column(
            "SELECT project_id
            FROM plugin_git_big_object_authorized_project"
        );
    }
}
