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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use Tuleap\DB\DataAccessObject;

class ProjectOwnerDAO extends DataAccessObject
{
    public function save($project_id, $user_id): void
    {
        $this->getDB()->run(
            'INSERT INTO plugin_project_ownership_project_owner (project_id, user_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE user_id = ?',
            $project_id,
            $user_id,
            $user_id
        );
    }

    public function searchByProjectID($project_id): ?array
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_project_ownership_project_owner WHERE project_id = ?',
            $project_id
        );
    }

    public function delete(int $project_id, int $user_id): void
    {
        $this->getDB()->delete(
            'plugin_project_ownership_project_owner',
            [
                'project_id' => $project_id,
                'user_id'    => $user_id,
            ],
        );
    }
}
