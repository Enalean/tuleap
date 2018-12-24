<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Admin;

use Tuleap\DB\DataAccessObject;

class AdminDao extends DataAccessObject
{
    public function enableTimetrackingForTracker($tracker_id)
    {
        $sql = 'REPLACE INTO plugin_timetracking_enabled_trackers
                VALUES (?)';

        $this->getDB()->run($sql, $tracker_id);
    }

    public function disableTimetrackingForTracker($tracker_id)
    {
        $sql = 'DELETE FROM plugin_timetracking_enabled_trackers
                WHERE tracker_id = ?';

        $this->getDB()->run($sql, $tracker_id);
    }

    public function isTimetrackingEnabledForTracker($tracker_id)
    {
        $sql = 'SELECT NULL
                FROM plugin_timetracking_enabled_trackers
                WHERE tracker_id = ?';

        $this->getDB()->run($sql, $tracker_id);

        return $this->foundRows() > 0;
    }

    public function getProjectstWithEnabledTimetracking($limit, $offset)
    {
        $sql = 'SELECT DISTINCT groups.group_id
                FROM plugin_timetracking_enabled_trackers
                INNER JOIN tracker AS tracker
                        ON tracker.id = plugin_timetracking_enabled_trackers.tracker_id
                 INNER JOIN groups AS groups
                        ON groups.group_id = tracker.group_id
                WHERE groups.status = "A"
                ORDER BY groups.group_name
                LIMIT ?
                OFFSET ?';

        return $this->getDB()->run($sql, $limit, $offset);
    }

    public function getProjectTrackersWithEnabledTimetracking($project_id, $limit, $offset)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_timetracking_enabled_trackers
                INNER JOIN tracker AS tracker
                        ON tracker.id = plugin_timetracking_enabled_trackers.tracker_id
                WHERE tracker.group_id = ?
                LIMIT ?, ?';

        return $this->getDB()->run($sql, $project_id, $offset, $limit);
    }
}
