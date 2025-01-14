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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement;

use Tuleap\DB\DataAccessObject;

class BurnupCacheDao extends DataAccessObject
{
    public function saveCachedFieldValueAtTimestamp(
        int $artifact_id,
        int $timestamp,
        ?float $total_effort,
        ?float $team_effort,
    ): void {
        $sql = <<<SQL
        REPLACE INTO plugin_agiledashboard_tracker_field_burnup_cache
            (artifact_id, timestamp, total_effort, team_effort)
            VALUES (?, ?, ?, ?)
        SQL;
        $this->getDB()->safeQuery($sql, [
            $artifact_id,
            $timestamp,
            $total_effort,
            $team_effort,
        ]);
    }

    public function deleteArtifactCacheValue(int $artifact_id): void
    {
        $this->getDB()->delete('plugin_agiledashboard_tracker_field_burnup_cache', ['artifact_id' => $artifact_id]);
    }

    /**
     * @return list<int>
     */
    public function getCachedDaysTimestamps(int $artifact_id): array
    {
        $sql = <<<SQL
        SELECT timestamp FROM plugin_agiledashboard_tracker_field_burnup_cache
        WHERE artifact_id = ?
        SQL;

        $results = $this->getDB()->safeQuery($sql, [$artifact_id]);
        if (! is_array($results)) {
            return [];
        }

        return array_values(array_map(
            static fn(array $row): int => $row['timestamp'],
            $results,
        ));
    }

    /**
     * @return list<array{
     *     timestamp: int,
     *     team_effort: ?float,
     *     total_effort: ?float,
     * }>
     */
    public function searchCachedDaysValuesByArtifactId(int $artifact_id, int $start_timestamp): array
    {
        $sql = <<<SQL
        SELECT timestamp, team_effort, total_effort
        FROM plugin_agiledashboard_tracker_field_burnup_cache
        WHERE artifact_id = ?
        AND timestamp >= ?
        SQL;
        return $this->getDB()->run($sql, $artifact_id, $start_timestamp);
    }
}
