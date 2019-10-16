<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use DataAccessObject;

class RecentlyVisitedDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    /**
     * @return bool
     */
    public function save($user_id, $artifact_id, $created_on)
    {
        $user_id     = $this->da->escapeInt($user_id);
        $artifact_id = $this->da->escapeInt($artifact_id);
        $created_on  = $this->da->escapeInt($created_on);

        $sql_update     = "INSERT INTO plugin_tracker_recently_visited(user_id, artifact_id, created_on)
                VALUES ($user_id, $artifact_id, $created_on)
                ON DUPLICATE KEY UPDATE created_on=$created_on";
        $has_been_saved = $this->update($sql_update);
        if (! $has_been_saved) {
            throw new \RuntimeException('Recently updated was to saved');
        }

        $sql_clean_history     = "DELETE FROM plugin_tracker_recently_visited WHERE user_id = $user_id AND created_on <= (
                                    SELECT created_on FROM (
                                      SELECT created_on FROM plugin_tracker_recently_visited WHERE user_id = $user_id ORDER BY created_on DESC LIMIT 1 OFFSET 30
                                    ) oldest_entry_to_keep
                                  )";
        $has_history_been_cleaned = $this->update($sql_clean_history);

        if (! $has_history_been_cleaned) {
            throw new \RuntimeException('Recently updated was not cleaned');
        }

        return true;
    }

    /**
     * @return \DataAccessResult|false
     */
    public function searchVisitByUserId($user_id, $maximum_visits)
    {
        $user_id        = $this->da->escapeInt($user_id);
        $maximum_visits = $this->da->escapeInt($maximum_visits);

        $sql = "SELECT artifact_id, created_on
                FROM plugin_tracker_recently_visited
                WHERE user_id = $user_id
                ORDER BY created_on DESC
                LIMIT $maximum_visits";

        return $this->retrieve($sql);
    }

    public function deleteVisitByUserId($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "DELETE FROM plugin_tracker_recently_visited WHERE user_id = $user_id";

        $this->update($sql);
    }

    public function deleteVisitByArtifactId($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "DELETE FROM plugin_tracker_recently_visited
                WHERE artifact_id = $artifact_id";

        $this->update($sql);
    }
}
