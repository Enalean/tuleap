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

declare(strict_types=1);

namespace Tuleap\Timetracking\Widget\Management;

use DateTimeImmutable;
use Tuleap\DB\DataAccessObject;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\GetQueryUsers;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\GetWidgetInformation;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\PredefinedTimePeriod;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SaveQueryWithDates;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SaveQueryWithPredefinedTimePeriod;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SearchQueryByWidgetId;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SearchUsersByWidgetId;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\UserList;

final class ManagementDao extends DataAccessObject implements SaveQueryWithDates, SaveQueryWithPredefinedTimePeriod, GetQueryUsers, GetWidgetInformation, SearchQueryByWidgetId, SearchUsersByWidgetId
{
    public function create(PredefinedTimePeriod $predefined_time_period): int
    {
        $this->getDB()->run('INSERT INTO plugin_timetracking_management_query (predefined_time_period)
                                        VALUES (?)', $predefined_time_period->value);
        return (int) $this->getDB()->lastInsertId();
    }

    public function delete(int $query_id): void
    {
        $this->getDB()->run(
            <<<EOS
            DELETE plugin_timetracking_management_query, plugin_timetracking_management_query_users
            FROM plugin_timetracking_management_query
                INNER JOIN plugin_timetracking_management_query_users ON (id = query_id)
            WHERE id = ?
            EOS,
            $query_id,
        );
    }

    public function saveQueryWithDates(
        int $query_id,
        DateTimeImmutable $start_date,
        DateTimeImmutable $end_date,
        UserList $users,
    ): void {
        $this->getDB()->tryFlatTransaction(function () use ($query_id, $start_date, $end_date, $users) {
            $sql = 'UPDATE plugin_timetracking_management_query
                    SET start_date = ?, end_date = ?, predefined_time_period = ?
                    WHERE id = ?';

            $this->getDB()->run($sql, $start_date->getTimestamp(), $end_date->getTimestamp(), null, $query_id);
            $this->deleteUsers($query_id);
            $this->insertUsers($query_id, $users);
        });
    }

    public function saveQueryWithPredefinedTimePeriod(
        int $query_id,
        PredefinedTimePeriod $predefined_time_period,
        UserList $users,
    ): void {
        $this->getDB()->tryFlatTransaction(function () use ($query_id, $predefined_time_period, $users) {
            $sql = 'UPDATE plugin_timetracking_management_query
                    SET start_date = ?, end_date = ?, predefined_time_period = ?
                    WHERE id = ?';

            $this->getDB()->run($sql, null, null, $predefined_time_period->value, $query_id);
            $this->deleteUsers($query_id);
            $this->insertUsers($query_id, $users);
        });
    }

    private function deleteUsers(int $query_id): void
    {
        $sql = 'DELETE FROM plugin_timetracking_management_query_users WHERE query_id = ? ';

        $this->getDB()->RUN($sql, $query_id);
    }

    private function insertUsers(int $query_id, UserList $users): void
    {
        $users_to_insert = [];
        foreach ($users->viewable_users as $user) {
            $users_to_insert[] = ['query_id' => $query_id, 'user_id' => $user->getId()];
        }

        if ($users_to_insert !== []) {
            $this->getDB()->insertMany('plugin_timetracking_management_query_users', $users_to_insert);
        }
    }

    public function getUsersByQueryId(int $id): array
    {
        $sql = 'SELECT user_id
                FROM plugin_timetracking_management_query_users
                WHERE query_id = ?';
        return $this->getDB()->column($sql, [$id]);
    }

    public function getWidgetInformationFromQuery(int $query_id): ?array
    {
        $sql = 'SELECT dashboard_id, user_id
                FROM plugin_timetracking_management_query
                         INNER JOIN dashboards_lines_columns_widgets AS widget
                                    ON plugin_timetracking_management_query.id = widget.content_id
                         INNER JOIN dashboards_lines_columns
                                    ON widget.column_id = dashboards_lines_columns.id
                         INNER JOIN dashboards_lines
                                    ON dashboards_lines_columns.line_id = dashboards_lines.id
                         LEFT JOIN user_dashboards
                                   ON user_dashboards.id = dashboards_lines.dashboard_id
                         LEFT JOIN project_dashboards
                                   ON project_dashboards.id = dashboards_lines.dashboard_id
                WHERE plugin_timetracking_management_query.id = ?
                  AND widget.name = "timetracking-management-widget"
                  AND dashboard_type = "user"';

        return $this->getDB()->row($sql, $query_id);
    }

    /**
     * @return null|array{id: int, start_date: string|null, end_date: string|null, predefined_time_period: string|null}
     */
    public function searchQueryById(int $id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_timetracking_management_query
                WHERE id = ?';

        return $this->getDB()->row($sql, $id);
    }

    /**
     * @return int[]
     */
    public function searchUsersByQueryId(int $id): array
    {
        $sql = 'SELECT user_id
                FROM plugin_timetracking_management_query_users
                WHERE query_id = ?';
        return $this->getDB()->column($sql, [$id]);
    }
}
