<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\UGroups;

use Tuleap\DB\DataAccessObject;

class SynchronizedProjectMembershipDao extends DataAccessObject
{
    public function isEnabled(int $project_id): bool
    {
        $sql = 'SELECT COUNT(*)
            FROM project_ugroup_synchronized_membership
            WHERE project_id = ? AND is_activated = 1';
        return $this->getDB()->exists($sql, $project_id);
    }

    public function enable(\Project $project): void
    {
        $sql = 'REPLACE INTO project_ugroup_synchronized_membership(project_id, is_activated) VALUES (?, 1)';
        $this->getDB()->run($sql, $project->getID());
    }

    public function disable(\Project $project): void
    {
        $sql = 'DELETE FROM project_ugroup_synchronized_membership
                WHERE project_id = ?';
        $this->getDB()->run($sql, $project->getID());
    }

    public function duplicateActivationFromTemplate(int $source_project_id, int $destination_project_id): void
    {
        $sql = 'INSERT INTO project_ugroup_synchronized_membership(project_id, is_activated)
            SELECT ?, source.is_activated
            FROM project_ugroup_synchronized_membership AS source
            WHERE source.project_id = ?';
        $this->getDB()->run($sql, $destination_project_id, $source_project_id);
    }
}
