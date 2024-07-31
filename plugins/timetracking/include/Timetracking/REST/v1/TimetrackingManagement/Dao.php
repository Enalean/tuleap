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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use DateTimeImmutable;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

final class Dao extends DataAccessObject implements SaveQueryWithDates, SaveQueryWithPredefinedTimePeriod, GetQueryUsers
{
    public function create(PredefinedTimePeriod $predefined_time_period): int
    {
        $this->getDB()->run('INSERT INTO plugin_timetracking_management_query (predefined_time_period)
                                        VALUES (?)', $predefined_time_period->value);
        return (int) $this->getDB()->lastInsertId();
    }

    public function delete(int $widget_id): void
    {
        $sql = 'DELETE FROM plugin_timetracking_management_query WHERE id = ?';
        $this->getDB()->run($sql, $widget_id);
    }

    public function saveQueryWithDates(int $widget_id, DateTimeImmutable $start_date, DateTimeImmutable $end_date, array $user_ids_to_insert, array $user_ids_to_remove): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($widget_id, $start_date, $end_date, $user_ids_to_insert, $user_ids_to_remove) {
            $sql = 'UPDATE plugin_timetracking_management_query
                    SET start_date = ?, end_date = ?, predefined_time_period = ?
                    WHERE id = ?';

            $this->getDB()->run($sql, $start_date->getTimestamp(), $end_date->getTimestamp(), null, $widget_id);
            $this->insertUsers($widget_id, $user_ids_to_insert);
            $this->deleteUsers($widget_id, $user_ids_to_remove);
        });
    }

    public function saveQueryWithPredefinedTimePeriod(int $widget_id, PredefinedTimePeriod $predefined_time_period, array $user_ids_to_insert, array $user_ids_to_remove): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($widget_id, $predefined_time_period, $user_ids_to_insert, $user_ids_to_remove) {
            $sql = 'UPDATE plugin_timetracking_management_query
                    SET start_date = ?, end_date = ?, predefined_time_period = ?
                    WHERE id = ?';

            $this->getDB()->run($sql, null, null, $predefined_time_period->value, $widget_id);
            $this->insertUsers($widget_id, $user_ids_to_insert);
            $this->deleteUsers($widget_id, $user_ids_to_remove);
        });
    }

    private function deleteUsers(int $widget_id, array $user_ids_to_remove): void
    {
        if (! empty($user_ids_to_remove)) {
            $user_ids_statement = EasyStatement::open()->in('user_id IN (?*)', $user_ids_to_remove);
            $sql                = "DELETE FROM plugin_timetracking_management_query_users WHERE $user_ids_statement AND widget_id = ? ";

            $delete_params   = $user_ids_statement->values();
            $delete_params[] = $widget_id;
            $this->getDB()->safeQuery($sql, $delete_params);
        }
    }

    private function insertUsers(int $widget_id, array $user_ids_to_insert): void
    {
        $users_to_insert = [];
        foreach ($user_ids_to_insert as $user_id) {
            $users_to_insert[] = ['widget_id' => $widget_id, 'user_id' => $user_id];
        }

        if (! empty($user_ids_to_insert)) {
            $this->getDB()->insertMany('plugin_timetracking_management_query_users', $users_to_insert);
        }
    }

    public function getQueryUsers(int $widget_id): array
    {
        $sql = 'SELECT user_id
                FROM plugin_timetracking_management_query_users
                WHERE widget_id = ?';
        return $this->getDB()->column($sql, [$widget_id]);
    }
}
