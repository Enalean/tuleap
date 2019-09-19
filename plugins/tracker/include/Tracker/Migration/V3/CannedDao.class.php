<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Migration_V3_CannedDao extends DataAccessObject
{

    public function create($tv3_id, $tv5_id)
    {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);
        $sql = "INSERT INTO tracker_canned_response(tracker_id, title, body)
                SELECT $tv5_id,
                    REPLACE(REPLACE(title, '&gt;', '>'), '&lt;', '<'),
                    REPLACE(REPLACE(body, '&gt;', '>'), '&lt;', '<')
                FROM artifact_canned_responses
                WHERE group_artifact_id = $tv3_id";
        $this->update($sql);
    }
}
