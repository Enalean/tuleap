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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use ParagonIE\EasyDB\EasyDB;
use Project;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;

class RoleAssignmentRepositoryAdapter implements RoleAssignmentRepository
{
    /** @var EasyDB */
    private $db;

    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @return RoleAssignment[]
     */
    public function findByProjectAndRole(Project $project, string $role): array
    {
        $rows = $this->db->safeQuery(
            "SELECT user_group_id, role, project_id
                    FROM plugin_baseline_role_assignment
                    WHERE project_id = ?
                    AND role = ?",
            [$project->getID(), $role]
        );

        $assignments = [];
        foreach ($rows as $row) {
            $assignments[] = new RoleAssignment(
                $project,
                $row['user_group_id'],
                $row['role']
            );
        }

        return $assignments;
    }
}
