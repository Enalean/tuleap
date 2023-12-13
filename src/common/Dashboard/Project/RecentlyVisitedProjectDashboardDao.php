<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use Tuleap\DB\DataAccessObject;

final class RecentlyVisitedProjectDashboardDao extends DataAccessObject implements DeleteVisitByUserId, DeleteVisitByDashboardId
{
    public function save(int $user_id, int $dashboard_id, int $created_on): void
    {
        $this->getDB()->run(
            'INSERT INTO project_dashboards_recently_visited(user_id, dashboard_id, created_on)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE created_on = ?',
            $user_id,
            $dashboard_id,
            $created_on,
            $created_on
        );
    }

    public function searchVisitByUserId(int $user_id, int $maximum_visits): array
    {
        $sql = 'SELECT dashboard_id, created_on
                FROM project_dashboards_recently_visited
                WHERE user_id = ?
                ORDER BY created_on DESC
                LIMIT ?';

        return $this->getDB()->run($sql, $user_id, $maximum_visits);
    }

    public function deleteVisitByUserId(int $user_id): void
    {
        $this->getDB()->delete('project_dashboards_recently_visited', ['user_id' => $user_id]);
    }

    public function deleteVisitByDashboardId(int $dashboard_id): void
    {
        $this->getDB()->delete('project_dashboards_recently_visited', ['dashboard_id' => $dashboard_id]);
    }

    public function deleteOldVisits(): void
    {
        $sql = 'DELETE FROM project_dashboards_recently_visited
                WHERE (user_id, dashboard_id) IN (
                SELECT * FROM (
                    SELECT RV1.user_id, RV1.dashboard_id
                    FROM project_dashboards_recently_visited AS RV1
                    WHERE (
                        SELECT COUNT(*)
                        FROM project_dashboards_recently_visited AS RV2
                        WHERE RV1.user_id = RV2.user_id AND RV1.created_on <= RV2.created_on
                    ) > 30 -- Number of items to keep
                ) AS TMP)';
        $this->getDB()->run($sql);
    }
}
