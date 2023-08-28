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
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Tuleap\DB\DataAccessObject;

class ExplicitBacklogDao extends DataAccessObject implements VerifyProjectUsesExplicitBacklog
{
    public function isProjectUsingExplicitBacklog(int $project_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_agiledashboard_planning_explicit_backlog_usage WHERE project_id = ?';
        $rows = $this->getDB()->run($sql, $project_id);

        return count($rows) > 0;
    }

    public function setProjectIsUsingExplicitBacklog(int $project_id): void
    {
        $sql = 'INSERT INTO plugin_agiledashboard_planning_explicit_backlog_usage (project_id)
                 VALUES (?)
                 ON DUPLICATE KEY UPDATE project_id=?';

        $this->getDB()->run($sql, $project_id, $project_id);
    }
}
