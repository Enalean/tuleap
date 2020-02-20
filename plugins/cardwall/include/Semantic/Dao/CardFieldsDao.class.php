<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
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

class Cardwall_Semantic_Dao_CardFieldsDao extends DataAccessObject implements Tracker_Semantic_IRetrieveSemanticDARByTracker
{
    /**
     * @return mixed either false if error or object DataAccessResult
     */
    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT *
                FROM plugin_cardwall_semantic_cardfields
                WHERE tracker_id = $tracker_id
                ORDER BY rank";

        return $this->retrieve($sql);
    }

    /**
     * @return bool true if success
     */
    public function add($tracker_id, $field_id, $rank)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);
        $rank       = $this->da->escapeInt($this->prepareRanking('plugin_cardwall_semantic_cardfields', 0, $tracker_id, $rank, 'id', 'tracker_id'));
        $sql = "REPLACE INTO plugin_cardwall_semantic_cardfields (tracker_id, field_id, rank)
                VALUES ($tracker_id, $field_id, $rank)";

        return $this->update($sql);
    }


    /**
     * @return bool true if success
     */
    public function remove($tracker_id, $field_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);

        $sql = "DELETE FROM plugin_cardwall_semantic_cardfields
                WHERE tracker_id = $tracker_id
                AND field_id = $field_id";

        return $this->update($sql);
    }
}
