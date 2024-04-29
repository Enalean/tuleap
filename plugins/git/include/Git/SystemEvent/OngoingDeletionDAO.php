<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Git\SystemEvent;

use SystemEvent;
use SystemEvent_GIT_REPO_DELETE;
use Tuleap\DB\DataAccessObject;

class OngoingDeletionDAO extends DataAccessObject
{
    public function isADeletionForPathOngoingInProject(int $project_id, string $repository_path): bool
    {
        $sql = "SELECT NULL
                FROM system_event
                    INNER JOIN plugin_git ON (SUBSTRING_INDEX(system_event.parameters, '::', -1) = plugin_git.repository_id)
                WHERE system_event.type = ?
                    AND system_event.status != ?
                    AND plugin_git.project_id = ?
                    AND plugin_git.repository_path = ?";

        $rows = $this->getDB()->run(
            $sql,
            SystemEvent_GIT_REPO_DELETE::NAME,
            SystemEvent::STATUS_DONE,
            $project_id,
            $repository_path
        );

        return count($rows) > 0;
    }
}
