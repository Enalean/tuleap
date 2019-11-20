<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Cardwall_Column;
use Tuleap\DB\DataAccessObject;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

class FreestyleMappingDao extends DataAccessObject
{
    public function searchMappedField(TaskboardTracker $taskboard_tracker): ?int
    {
        $sql = "SELECT field_id
            FROM plugin_cardwall_on_top_column_mapping_field
            WHERE cardwall_tracker_id = ?
                  AND tracker_id = ?";
        return $this->getDB()->cell(
            $sql,
            $taskboard_tracker->getMilestoneTrackerId(),
            $taskboard_tracker->getTrackerId()
        ) ?: null;
    }

    public function doesFreestyleMappingExist(TaskboardTracker $taskboard_tracker): bool
    {
        $sql = "SELECT 1
            FROM plugin_cardwall_on_top_column_mapping_field
            WHERE cardwall_tracker_id = ?
                AND tracker_id = ?";
        return $this->getDB()->exists(
            $sql,
            $taskboard_tracker->getMilestoneTrackerId(),
            $taskboard_tracker->getTrackerId()
        );
    }

    public function searchMappedFieldValuesForColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column
    ): array {
        $sql = "SELECT mapped_value.value_id
            FROM plugin_cardwall_on_top_column_mapping_field AS mapping_field
                 INNER JOIN plugin_cardwall_on_top_column_mapping_field_value AS mapped_value
            ON (mapping_field.cardwall_tracker_id = mapped_value.cardwall_tracker_id
                AND mapped_value.tracker_id = mapping_field.tracker_id)
            WHERE mapping_field.cardwall_tracker_id = ?
                  AND mapping_field.tracker_id = ?
                  AND mapped_value.column_id = ?";
        return $this->getDB()->run(
            $sql,
            $taskboard_tracker->getMilestoneTrackerId(),
            $taskboard_tracker->getTrackerId(),
            $column->getId()
        );
    }
}
