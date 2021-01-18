<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\DB\DataAccessObject;

class ProgramIncrementsDAO extends DataAccessObject
{
    /**
     * @psalm-return array{id:int}[]
     */
    public function searchOpenProgramIncrements(int $program_id): array
    {
        $sql = 'SELECT artifact.id
                FROM tracker_artifact AS artifact
                JOIN tracker_changeset ON (artifact.last_changeset_id = tracker_changeset.id)
                -- get open artifacts
                JOIN (
                    tracker_semantic_status AS status
                    JOIN tracker_changeset_value AS status_changeset ON (status.field_id = status_changeset.field_id)
                    JOIN tracker_changeset_value_list AS status_value ON (status_changeset.id = status_value.changeset_value_id)
                ) ON (artifact.tracker_id = status.tracker_id AND tracker_changeset.id = status_changeset.changeset_id)
                WHERE status.open_value_id = status_value.bindvalue_id AND artifact.tracker_id IN (
                    SELECT program_increment_tracker_id
                    FROM plugin_scaled_agile_plan
                    JOIN tracker ON (tracker.id = plugin_scaled_agile_plan.program_increment_tracker_id)
                    WHERE tracker.group_id = ?
                )';

        return $this->getDB()->run($sql, $program_id);
    }
}
