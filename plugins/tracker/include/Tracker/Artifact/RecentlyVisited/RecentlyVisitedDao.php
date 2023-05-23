<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class RecentlyVisitedDao extends DataAccessObject
{
    public function save(int $user_id, int $artifact_id, int $created_on): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($user_id, $artifact_id, $created_on): void {
            $sql_update = 'INSERT INTO plugin_tracker_recently_visited(user_id, artifact_id, created_on)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE created_on=?';
            $db->run($sql_update, $user_id, $artifact_id, $created_on, $created_on);

            $sql_clean_history = 'DELETE FROM plugin_tracker_recently_visited WHERE user_id = ? AND created_on <= (
                                    SELECT created_on FROM (
                                      SELECT created_on FROM plugin_tracker_recently_visited WHERE user_id = ? ORDER BY created_on DESC LIMIT 1 OFFSET 30
                                    ) oldest_entry_to_keep
                                  )';
            $db->run($sql_clean_history, $user_id, $user_id);
        });
    }

    /**
     * @psalm-return array{artifact_id: int, created_on: int}[]
     */
    public function searchVisitByUserId(int $user_id, int $maximum_visits): array
    {
        $sql = 'SELECT artifact_id, created_on
                FROM plugin_tracker_recently_visited
                WHERE user_id = ?
                ORDER BY created_on DESC
                LIMIT ?';

        return $this->getDB()->run($sql, $user_id, $maximum_visits);
    }

    public function deleteVisitByUserId(int $user_id): void
    {
        $sql = 'DELETE FROM plugin_tracker_recently_visited WHERE user_id = ?';

        $this->getDB()->run($sql, $user_id);
    }

    public function deleteVisitByArtifactId(int $artifact_id): void
    {
        $sql = 'DELETE FROM plugin_tracker_recently_visited
                WHERE artifact_id = ?';

        $this->getDB()->run($sql, $artifact_id);
    }
}
