<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\DB\DataAccessObject;

class SemanticProgressDao extends DataAccessObject
{
    /**
     * @psalm-return array{total_effort_field_id: ?int, remaining_effort_field_id: ?int, artifact_link_type: ?string}|null
     */
    public function searchByTrackerId(int $tracker_id): ?array
    {
        $sql = '
            SELECT total_effort_field_id, remaining_effort_field_id, artifact_link_type
            FROM tracker_semantic_progress
            WHERE tracker_id = ?
        ';

        return $this->getDB()->row($sql, $tracker_id);
    }

    public function save(
        int $tracker_id,
        ?int $total_effort,
        ?int $remaining_effort,
        ?string $link_type,
    ): bool {
        $sql    = '
            REPLACE INTO tracker_semantic_progress(
                tracker_id,
                total_effort_field_id,
                remaining_effort_field_id,
                artifact_link_type
            ) VALUES (?, ?, ?, ?)';
        $result = $this->getDB()->run($sql, $tracker_id, $total_effort, $remaining_effort, $link_type);

        return $result !== null;
    }

    public function delete(int $tracker_id): bool
    {
        $sql    = 'DELETE FROM tracker_semantic_progress WHERE tracker_id = ?';
        $result = $this->getDB()->run($sql, $tracker_id);

        return $result !== null;
    }
}
