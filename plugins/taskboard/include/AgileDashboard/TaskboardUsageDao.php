<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\AgileDashboard;

use Tuleap\DB\DataAccessObject;

class TaskboardUsageDao extends DataAccessObject
{
    /**
     * @return string | false
     */
    public function searchBoardTypeByProjectId(int $project_id)
    {
        $statement = 'SELECT board_type FROM plugin_taskboard_usage WHERE project_id = ?';

        return $this->getDB()->cell($statement, $project_id);
    }

    public function updateBoardTypeByProjectId(int $project_id, string $board_type): void
    {
        $statement = '
            UPDATE plugin_taskboard_usage
            SET board_type = ?
            WHERE project_id = ?
        ';

        $this->getDB()->run($statement, $board_type, $project_id);
    }

    public function create(int $project_id, string $board_type): void
    {
        $statement = '
            INSERT INTO plugin_taskboard_usage(project_id, board_type)
            VALUE(?, ?)
        ';

        $this->getDB()->run($statement, $project_id, $board_type);
    }

    public function deleteBoardTypeByProjectId(int $project_id): void
    {
        $statement = 'DELETE FROM plugin_taskboard_usage WHERE project_id = ?';

        $this->getDB()->run($statement, $project_id);
    }

    public function duplicate(int $project_id, int $template_id): void
    {
        $statement = '
            INSERT INTO plugin_taskboard_usage(project_id, board_type)
            SELECT ?, board_type
            FROM plugin_taskboard_usage
            WHERE project_id = ?
        ';

        $this->getDB()->run($statement, $project_id, $template_id);
    }
}
