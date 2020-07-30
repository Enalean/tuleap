<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\PlannableItems;

use Tuleap\DB\DataAccessObject;

class PlannableItemsTrackersDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{project_id:int, tracker_ids:string}>
     */
    public function getPlannableItemsTrackerIds(int $project_id): array
    {
        $sql = "SELECT tracker.group_id as project_id, GROUP_CONCAT(tracker.id ORDER BY tracker.id ASC) as tracker_ids
                FROM plugin_multi_project_backlog_plannable_item_trackers
                    INNER JOIN tracker ON (
                        tracker.id = plugin_multi_project_backlog_plannable_item_trackers.contributor_backlog_item_tracker_id
                    )
                WHERE aggregator_project_id = ?
                GROUP BY tracker.group_id";

        return $this->getDB()->run($sql, $project_id);
    }
}
