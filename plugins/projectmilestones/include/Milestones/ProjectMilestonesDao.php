<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\ProjectMilestones\Milestones;

use Tuleap\DB\DataAccessObject;

class ProjectMilestonesDao extends DataAccessObject
{
    public function create(int $project_id): string
    {
        $this->getDB()->run('INSERT INTO plugin_projectmilestones_widget(id, group_id) VALUES (null, ?)', $project_id);
        return $this->getDB()->lastInsertId();
    }

    public function updateProjectMilestoneId(int $widget_id, int $project_id): void
    {
        $sql = 'UPDATE plugin_projectmilestones_widget
                SET group_id = ?
                WHERE id = ?';
        $this->getDB()->run($sql, $project_id, $widget_id);
    }

    /**
     * @return int|false
     */
    public function searchProjectIdById(int $id)
    {
        $sql = 'SELECT group_id
                FROM plugin_projectmilestones_widget
                WHERE id = ?';

        return $this->getDB()->single($sql, [$id]);
    }

    public function delete(int $widget_id): void
    {
        $sql = 'DELETE
                FROM plugin_projectmilestones_widget
                WHERE id = ?';
        $this->getDB()->run($sql, $widget_id);
    }
}
