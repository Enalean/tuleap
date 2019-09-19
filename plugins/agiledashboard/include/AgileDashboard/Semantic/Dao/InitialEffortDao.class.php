<?php
/**
* Copyright Enalean (c) 2013-2016. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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


class AgileDashboard_Semantic_Dao_InitialEffortDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return mixed either false if error or object DataAccessResult
     */
    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT *
                FROM plugin_agiledashboard_semantic_initial_effort
                WHERE tracker_id = $tracker_id";

        return $this->retrieve($sql);
    }

    /**
     * @return bool true if success
     */
    public function save($tracker_id, $field_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);

        $sql = "REPLACE INTO plugin_agiledashboard_semantic_initial_effort
                    (tracker_id, field_id)
                VALUES
                    ($tracker_id, $field_id)";

        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function delete($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "DELETE FROM plugin_agiledashboard_semantic_initial_effort WHERE tracker_id = $tracker_id";

        return $this->update($sql);
    }
}
