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

namespace Tuleap\AgileDashboard\FormElement;

use DataAccessObject;

class BurnupCacheDao extends DataAccessObject
{
    public function saveCachedFieldValueAtTimestamp(
        $artifact_id,
        $timestamp,
        $total_effort,
        $team_effort
    ) {
        $artifact_id             = $this->da->escapeInt($artifact_id);
        $timestamp               = $this->da->escapeInt($timestamp);
        $team_effort             = $team_effort === null ? 'NULL' : $this->da->quoteSmart($team_effort);
        $total_effort            = $total_effort === null ? 'NULL' : $this->da->quoteSmart($total_effort);

        $sql = "REPLACE INTO plugin_agiledashboard_tracker_field_burnup_cache
                    (artifact_id, timestamp, total_effort, team_effort)
                    VALUES ($artifact_id, $timestamp, $total_effort, $team_effort)";

        return $this->update($sql);
    }

    public function deleteArtifactCacheValue($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "DELETE FROM plugin_agiledashboard_tracker_field_burnup_cache
                WHERE artifact_id = $artifact_id";

        return $this->update($sql);
    }

    public function getNumberOfCachedDays($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT count(artifact_id) AS cached_days FROM plugin_agiledashboard_tracker_field_burnup_cache
                WHERE artifact_id = $artifact_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchCachedDaysValuesByArtifactId($artifact_id, $start_timestamp)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT timestamp, team_effort, total_effort
                FROM plugin_agiledashboard_tracker_field_burnup_cache
                WHERE artifact_id = $artifact_id
                AND timestamp >= $start_timestamp";

        return $this->retrieve($sql);
    }
}
