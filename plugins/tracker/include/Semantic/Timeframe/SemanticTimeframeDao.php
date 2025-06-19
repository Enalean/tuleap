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

namespace Tuleap\Tracker\Semantic\Timeframe;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class SemanticTimeframeDao extends DataAccessObject
{
    private const SQL_TRUE_VALUE = 1;

    /**
     * @psalm-return array{start_date_field_id: ?int, duration_field_id: ?int, end_date_field_id: ?int, implied_from_tracker_id: ?int}|null
     */
    public function searchByTrackerId(int $tracker_id): ?array
    {
        $sql = 'SELECT start_date_field_id, duration_field_id, end_date_field_id, implied_from_tracker_id
            FROM tracker_semantic_timeframe
                WHERE tracker_id = ?';

        return $this->getDB()->row($sql, $tracker_id);
    }

    public function save(int $tracker_id, ?int $start_date_field_id, ?int $duration_field_id, ?int $end_date_field_id, ?int $implied_from_tracker_id): bool
    {
        $sql    = '
            REPLACE INTO tracker_semantic_timeframe(
                tracker_id,
                start_date_field_id,
                duration_field_id,
                end_date_field_id,
                implied_from_tracker_id
            ) VALUES (?, ?, ?, ?, ?)
        ';
        $result = $this->getDB()->run($sql, $tracker_id, $start_date_field_id, $duration_field_id, $end_date_field_id, $implied_from_tracker_id);
        return $result !== null;
    }

    public function deleteTimeframeSemantic(int $tracker_id): void
    {
        $sql = 'DELETE FROM tracker_semantic_timeframe WHERE tracker_id = ?';

        $this->getDB()->run($sql, $tracker_id);
    }

    public function getNbOfTrackersWithoutTimeFrameSemanticDefined(array $tracker_ids): int
    {
        $tracker_ids_statement = EasyStatement::open()->in('(?*)', $tracker_ids);
        $sql                   = "SELECT count(*) AS nb
FROM tracker
         LEFT JOIN tracker_semantic_timeframe AS timeframe
ON (tracker.id = timeframe.tracker_id)
WHERE tracker.id IN $tracker_ids_statement
      AND timeframe.tracker_id IS NULL";

        return $this->getDB()->single($sql, $tracker_ids_statement->values());
    }

    public function areTimeFrameSemanticsUsingSameTypeOfField(array $tracker_ids): bool
    {
        $tracker_ids_statement = EasyStatement::open()->in('(?*)', $tracker_ids);
        $sql                   = "SELECT CASE 0 WHEN (count(*) - count(duration_field_id)) THEN true
        WHEN (count(*) - count(end_date_field_id)) THEN true
        ELSE false END AS same_field_type
FROM tracker_semantic_timeframe AS timeframe
WHERE tracker_id IN $tracker_ids_statement";

        $result = $this->getDB()->single($sql, $tracker_ids_statement->values());
        return $result === self::SQL_TRUE_VALUE;
    }

    public function areTimeFrameSemanticsUsingSameDatetimeDisplayingForStartDate(array $tracker_ids): bool
    {
        $tracker_ids_statement = EasyStatement::open()->in('(?*)', $tracker_ids);
        $sql                   = "SELECT DISTINCT display_time
FROM tracker_semantic_timeframe
INNER JOIN tracker_field_date ON (tracker_semantic_timeframe.start_date_field_id = tracker_field_date.field_id)
WHERE tracker_id IN $tracker_ids_statement";

        $rows = $this->getDB()->run($sql, ...$tracker_ids_statement->values());
        return count($rows) === 1;
    }

    public function areTimeFrameSemanticsUsingSameDatetimeDisplayingForEndDate(array $tracker_ids): bool
    {
        $tracker_ids_statement = EasyStatement::open()->in('(?*)', $tracker_ids);
        $sql                   = "SELECT DISTINCT display_time
FROM tracker_semantic_timeframe
INNER JOIN tracker_field_date ON (tracker_semantic_timeframe.end_date_field_id = tracker_field_date.field_id)
WHERE tracker_id IN $tracker_ids_statement";

        $rows = $this->getDB()->run($sql, ...$tracker_ids_statement->values());
        return count($rows) <= 1;
    }

    /**
     * @psalm-return array<int, array{tracker_id: int, implied_from_tracker_id: int}>|null
     */
    public function getSemanticsImpliedFromGivenTracker(int $tracker_id): ?array
    {
        $sql = '
            SELECT tracker_id, implied_from_tracker_id
            FROM tracker_semantic_timeframe
            WHERE implied_from_tracker_id = ?
        ';
        return $this->getDB()->run($sql, $tracker_id);
    }
}
